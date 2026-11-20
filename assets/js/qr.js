/**
 * Lightweight, dependency-free ISO/IEC 18004 QR Code SVG generator (Versions 1-4, ECC Level L, Byte Mode).
 * Works 100% offline in XAMPP and produces crisp, camera-scannable SVG QR codes.
 */
(function (global) {
  const GF_EXP = new Uint8Array(512);
  const GF_LOG = new Uint8Array(256);
  let x = 1;
  for (let i = 0; i < 255; i++) {
    GF_EXP[i] = x;
    GF_LOG[x] = i;
    x <<= 1;
    if (x & 0x100) x ^= 0x11d;
  }
  for (let i = 255; i < 512; i++) GF_EXP[i] = GF_EXP[i - 255];

  function gfMul(a, b) {
    return a === 0 || b === 0 ? 0 : GF_EXP[GF_LOG[a] + GF_LOG[b]];
  }

  function rsGeneratorPoly(degree) {
    let poly = [1];
    for (let i = 0; i < degree; i++) {
      const next = new Array(poly.length + 1).fill(0);
      const root = GF_EXP[i];
      for (let j = 0; j < poly.length; j++) {
        next[j] ^= poly[j];
        next[j + 1] ^= gfMul(poly[j], root);
      }
      poly = next;
    }
    return poly;
  }

  function rsEncode(data, ecLen) {
    const gen = rsGeneratorPoly(ecLen);
    const rem = new Array(ecLen).fill(0);
    for (let i = 0; i < data.length; i++) {
      const factor = data[i] ^ rem[0];
      rem.shift();
      rem.push(0);
      if (factor !== 0) {
        for (let j = 0; j < ecLen; j++) {
          rem[j] ^= gfMul(gen[j + 1], factor);
        }
      }
    }
    return rem;
  }

  // Version table for ECC Level L (versions 1..4)
  const VERSIONS = [
    null,
    { size: 21, dataBytes: 19, ecBytes: 7, align: [] },
    { size: 25, dataBytes: 34, ecBytes: 10, align: [6, 18] },
    { size: 29, dataBytes: 55, ecBytes: 15, align: [6, 22] },
    { size: 33, dataBytes: 80, ecBytes: 20, align: [6, 26] }
  ];

  function pickVersion(byteLen) {
    for (let v = 1; v <= 4; v++) {
      if (byteLen + 2 <= VERSIONS[v].dataBytes) return v;
    }
    return 4;
  }

  function buildCodewords(text) {
    const bytes = Array.from(new TextEncoder().encode(text));
    const ver = pickVersion(bytes.length);
    const info = VERSIONS[ver];
    const maxData = info.dataBytes;
    const payload = bytes.slice(0, maxData - 2);

    const bits = [];
    const pushBits = (val, len) => {
      for (let i = len - 1; i >= 0; i--) bits.push((val >> i) & 1);
    };

    pushBits(0b0100, 4); // Byte mode
    pushBits(payload.length, 8);
    for (const b of payload) pushBits(b, 8);

    const maxBits = maxData * 8;
    const term = Math.min(4, maxBits - bits.length);
    pushBits(0, term);
    while (bits.length % 8 !== 0) bits.push(0);

    const dataCw = [];
    for (let i = 0; i < bits.length; i += 8) {
      let b = 0;
      for (let j = 0; j < 8; j++) b = (b << 1) | bits[i + j];
      dataCw.push(b);
    }
    const pads = [0xec, 0x11];
    let p = 0;
    while (dataCw.length < maxData) {
      dataCw.push(pads[p % 2]);
      p++;
    }

    const ecCw = rsEncode(dataCw, info.ecBytes);
    return { ver, info, codewords: dataCw.concat(ecCw) };
  }

  function createMatrix(ver, info, codewords) {
    const size = info.size;
    const modules = Array.from({ length: size }, () => new Array(size).fill(false));
    const isFunc = Array.from({ length: size }, () => new Array(size).fill(false));

    const setFunc = (r, c, val) => {
      if (r >= 0 && r < size && c >= 0 && c < size) {
        modules[r][c] = Boolean(val);
        isFunc[r][c] = true;
      }
    };

    const drawFinder = (r0, c0) => {
      for (let dr = -1; dr <= 7; dr++) {
        for (let dc = -1; dc <= 7; dc++) {
          const r = r0 + dr, c = c0 + dc;
          if (r < 0 || r >= size || c < 0 || c >= size) continue;
          const inOuter = dr >= 0 && dr <= 6 && dc >= 0 && dc <= 6 && (dr === 0 || dr === 6 || dc === 0 || dc === 6);
          const inInner = dr >= 2 && dr <= 4 && dc >= 2 && dc <= 4;
          setFunc(r, c, inOuter || inInner);
        }
      }
    };

    drawFinder(0, 0);
    drawFinder(0, size - 7);
    drawFinder(size - 7, 0);

    for (let i = 8; i < size - 8; i++) {
      setFunc(6, i, i % 2 === 0);
      setFunc(i, 6, i % 2 === 0);
    }

    if (info.align.length > 0) {
      for (const ar of info.align) {
        for (const ac of info.align) {
          if ((ar === 6 && ac === 6) || (ar === 6 && ac === size - 7) || (ar === size - 7 && ac === 6)) continue;
          for (let dr = -2; dr <= 2; dr++) {
            for (let dc = -2; dc <= 2; dc++) {
              setFunc(ar + dr, ac + dc, Math.max(Math.abs(dr), Math.abs(dc)) !== 1);
            }
          }
        }
      }
    }

    // Reserve format info areas
    for (let i = 0; i <= 8; i++) {
      if (i !== 6) {
        setFunc(8, i, false);
        setFunc(i, 8, false);
      }
      if (i < 8) {
        setFunc(8, size - 1 - i, false);
        setFunc(size - 1 - i, 8, false);
      }
    }
    setFunc(size - 8, 8, true); // Dark module

    // Place data bits
    const allBits = [];
    for (const cw of codewords) {
      for (let i = 7; i >= 0; i--) allBits.push((cw >> i) & 1);
    }

    let bitIdx = 0;
    let upward = true;
    for (let right = size - 1; right >= 1; right -= 2) {
      if (right === 6) right = 5;
      for (let vert = 0; vert < size; vert++) {
        const r = upward ? size - 1 - vert : vert;
        for (let j = 0; j < 2; j++) {
          const c = right - j;
          if (!isFunc[r][c]) {
            const dark = bitIdx < allBits.length ? Boolean(allBits[bitIdx++]) : false;
            // Apply mask 0: (r + c) % 2 === 0
            modules[r][c] = ((r + c) % 2 === 0) ? !dark : dark;
          }
        }
      }
      upward = !upward;
    }

    // Format info for ECC Level L (01) and Mask 0 (000) -> precomputed BCH format bits: 0x77c4
    const formatBits = 0x77c4;
    const fBit = (i) => ((formatBits >> i) & 1) !== 0;

    for (let i = 0; i <= 5; i++) setFunc(8, i, fBit(i));
    setFunc(8, 7, fBit(6));
    setFunc(8, 8, fBit(7));
    setFunc(7, 8, fBit(8));
    for (let i = 9; i < 15; i++) setFunc(14 - i, 8, fBit(i));

    for (let i = 0; i < 8; i++) setFunc(8, size - 1 - i, fBit(i));
    for (let i = 8; i < 15; i++) setFunc(size - 15 + i, 8, fBit(i));

    return { size, modules };
  }

  function renderQrSvg(text, pixelSize = 220) {
    const { ver, info, codewords } = buildCodewords(String(text || ''));
    const { size, modules } = createMatrix(ver, info, codewords);
    const margin = 4;
    const total = size + margin * 2;
    let path = '';
    for (let r = 0; r < size; r++) {
      for (let c = 0; c < size; c++) {
        if (modules[r][c]) {
          path += `M${c + margin},${r + margin}h1v1h-1z`;
        }
      }
    }
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${total} ${total}" width="${pixelSize}" height="${pixelSize}" shape-rendering="crispEdges" role="img" aria-label="QR Code"><rect width="100%" height="100%" fill="#ffffff"/><path d="${path}" fill="#101122"/></svg>`;
  }

  global.generateQrSvg = renderQrSvg;
})(window);
