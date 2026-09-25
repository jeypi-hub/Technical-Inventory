// Lightweight canvas bar chart — no external libs.
(function () {
  const canvas = document.getElementById('chart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const data = JSON.parse(canvas.dataset.months || '{}');
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  const dpr = window.devicePixelRatio || 1;
  const parentW = canvas.parentElement.clientWidth - 20;
  canvas.width  = parentW * dpr;
  canvas.height = 260 * dpr;
  canvas.style.width  = parentW + 'px';
  canvas.style.height = '260px';
  ctx.scale(dpr, dpr);

  const w = parentW, h = 260, padX = 30, padY = 30;
  const plotW = w - padX * 2, plotH = h - padY * 2;

  const values = [];
  for (let m = 1; m <= 12; m++) {
    values.push(data[m]?.borrowed || 0, data[m]?.returned || 0);
  }
  const maxVal = Math.max(3, ...values);
  const groupW = plotW / 12;
  const barW = Math.min(10, groupW / 3);

  ctx.strokeStyle = '#e5e7eb';
  ctx.beginPath(); ctx.moveTo(padX, h - padY); ctx.lineTo(w - padX, h - padY); ctx.stroke();

  ctx.fillStyle = '#6b6f80'; ctx.font = '11px Segoe UI'; ctx.textAlign = 'center';
  for (let m = 0; m < 12; m++) {
    const cx = padX + groupW * m + groupW / 2;
    ctx.fillText(months[m], cx, h - padY + 16);

    const b = data[m + 1]?.borrowed || 0;
    const r = data[m + 1]?.returned || 0;

    const bh = (b / maxVal) * plotH;
    const rh = (r / maxVal) * plotH;

    // borrowed bar
    ctx.fillStyle = '#2726c4';
    ctx.fillRect(cx - barW - 2, h - padY - bh, barW, bh);
    // returned bar
    ctx.fillStyle = '#16a34a';
    ctx.fillRect(cx + 2, h - padY - rh, barW, rh);

    if (b) { ctx.fillStyle = '#2726c4'; ctx.fillText(b, cx - barW / 2 - 2, h - padY - bh - 4); }
    if (r) { ctx.fillStyle = '#16a34a'; ctx.fillText(r, cx + barW / 2 + 2, h - padY - rh - 4); }
  }
})();
