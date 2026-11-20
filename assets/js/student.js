const translate = window.appTranslate || (key => key);

function closeNotifications() {
  const panel = document.getElementById('notifPanel');
  panel?.classList.remove('show');
  panel?.setAttribute('aria-hidden', 'true');
  document.querySelector('.stud-main')?.classList.remove('notifications-open');
  document.body.classList.remove('notifications-view-open');
  document.querySelector('.notif-btn')?.setAttribute('aria-expanded', 'false');
}

function updateNotificationViewBounds() {
  const main = document.querySelector('.stud-main');
  const header = document.querySelector('.stud-topbar');
  const notifBtn = document.querySelector('.notif-btn');
  if (!main || !header) return;

  main.style.setProperty('--notification-top', `${header.getBoundingClientRect().bottom}px`);
  if (notifBtn) {
    const btnRect = notifBtn.getBoundingClientRect();
    const rightOffset = Math.max(12, window.innerWidth - btnRect.right);
    main.style.setProperty('--notification-right', `${rightOffset}px`);
  }
}

function updateNotificationViewMode(isOpen) {
  const isMobile = window.matchMedia('(max-width: 768px)').matches;
  document.querySelector('.stud-main')?.classList.toggle('notifications-open', isOpen && isMobile);
  document.body.classList.toggle('notifications-view-open', isOpen && isMobile);
}

function toggleNotif() {
  const panel = document.getElementById('notifPanel');
  const isOpen = panel?.classList.toggle('show') ?? false;
  panel?.setAttribute('aria-hidden', String(!isOpen));
  updateNotificationViewMode(isOpen);
  document.querySelector('.notif-btn')?.setAttribute('aria-expanded', String(isOpen));
  if (isOpen) {
    window.scrollTo(0, 0);
    updateNotificationViewBounds();
    document.getElementById('profilePanel')?.classList.remove('show');
  }
  document.querySelector('.profile[role="button"]')?.setAttribute('aria-expanded', 'false');
}
function toggleProfile() {
  const panel = document.getElementById('profilePanel');
  const isOpen = panel?.classList.toggle('show') ?? false;
  document.querySelector('.profile[role="button"]')?.setAttribute('aria-expanded', String(isOpen));
  closeNotifications();
}

window.addEventListener('resize', () => {
  if (document.getElementById('notifPanel')?.classList.contains('show')) {
    updateNotificationViewMode(true);
    updateNotificationViewBounds();
  }
});
document.addEventListener('click', (e) => {
  if (!e.target.closest('.profile')) {
    document.getElementById('profilePanel')?.classList.remove('show');
    document.querySelector('.profile[role="button"]')?.setAttribute('aria-expanded', 'false');
  }
});
document.querySelector('.profile[role="button"]')?.addEventListener('keydown', (event) => {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault();
    toggleProfile();
  }
});

const searchForm = document.querySelector('.searchbar.wide');
if (searchForm) {
  const searchInput = searchForm.querySelector('input[name="q"]');
  const equipmentCards = Array.from(document.querySelectorAll('.equip-card'));
  let emptyMessage = document.querySelector('.equip-grid .empty');

  if (!emptyMessage) {
    emptyMessage = document.createElement('p');
    emptyMessage.className = 'empty';
    emptyMessage.textContent = translate('No equipment matches your search.');
    emptyMessage.hidden = true;
    document.querySelector('.equip-grid')?.appendChild(emptyMessage);
  }

  searchInput.addEventListener('input', () => {
    const query = searchInput.value.trim().toLowerCase();
    let visibleCount = 0;

    equipmentCards.forEach((card) => {
      const matches = card.dataset.search.toLowerCase().includes(query);
      card.hidden = !matches;
      if (matches) visibleCount++;
    });

    emptyMessage.hidden = visibleCount > 0;
    emptyMessage.textContent = query
      ? translate('No equipment matches your search.')
      : translate('No equipment yet.');
  });
}

const historySearchForm = document.querySelector('.student-history-search');
if (historySearchForm) {
  const historySearchInput = historySearchForm.querySelector('input[name="q"]');
  let historySearchTimer;

  historySearchInput.addEventListener('input', () => {
    window.clearTimeout(historySearchTimer);
    historySearchTimer = window.setTimeout(() => {
      historySearchForm.requestSubmit();
    }, 500);
  });
}

const teacherInput = document.getElementById('borrowCartTeacher');
const purposeInput = document.getElementById('borrowCartPurpose');
const cartRequestForm = document.getElementById('borrowCartForm');
const cartStatus = document.getElementById('borrowCartStatus');
const hasDigits = (value) => /[0-9]/.test(value);
const showNumericInputMessage = (label) => {
  cartStatus.textContent = translate('Numbers are not allowed in {field}.', { field: label });
};

if (cartRequestForm && cartStatus) {
  const restrictedInputs = [
    { input: purposeInput, label: 'Purpose' },
    { input: teacherInput, label: "Teacher's Name" }
  ].filter(({ input }) => input);

  restrictedInputs.forEach(({ input, label }) => {
    input.addEventListener('keydown', (event) => {
      if (/^[0-9]$/.test(event.key)) {
        event.preventDefault();
        showNumericInputMessage(label);
      }
    });

    input.addEventListener('beforeinput', (event) => {
      if (event.data && hasDigits(event.data)) {
        event.preventDefault();
        showNumericInputMessage(label);
      }
    });

    input.addEventListener('paste', (event) => {
      const pastedText = event.clipboardData?.getData('text') || '';
      if (!hasDigits(pastedText)) return;

      event.preventDefault();
      input.setRangeText(
        pastedText.replace(/[0-9]/g, ''),
        input.selectionStart,
        input.selectionEnd,
        'end'
      );
      input.dispatchEvent(new Event('input', { bubbles: true }));
      showNumericInputMessage(label);
    });

    input.addEventListener('input', () => {
      const originalValue = input.value;
      const caret = input.selectionStart;
      const filteredValue = originalValue.replace(/[0-9]/g, '');

      if (originalValue !== filteredValue) {
        const digitsBeforeCaret = (originalValue.slice(0, caret).match(/[0-9]/g) || []).length;
        input.value = filteredValue;
        const nextCaret = Math.max(0, caret - digitsBeforeCaret);
        input.setSelectionRange(nextCaret, nextCaret);
        showNumericInputMessage(label);
      } else {
        cartStatus.textContent = '';
      }
    });
  });

  cartRequestForm.addEventListener('submit', (event) => {
    const invalidInput = restrictedInputs.find(({ input }) => hasDigits(input.value));
    if (!invalidInput) return;

    event.preventDefault();
    showNumericInputMessage(invalidInput.label);
    invalidInput.input.reportValidity();
  });
}

if (teacherInput) {
  const suggestionList = document.getElementById('cartTeacherSuggestions');
  const teacherNames = [
    'mrs. myr',
    'mr. relan',
    'ms. gako',
    'mr. noel',
    'mr.dada',
    'mr. donald',
    'mrs. malang'
  ];

  const closeSuggestions = () => {
    suggestionList.hidden = true;
    teacherInput.setAttribute('aria-expanded', 'false');
  };

  teacherInput.addEventListener('input', () => {
    const query = teacherInput.value.trim().toLowerCase();
    suggestionList.replaceChildren();

    if (!query) {
      closeSuggestions();
      return;
    }

    teacherNames
      .filter((name) => name.includes(query))
      .forEach((name) => {
        const option = document.createElement('button');
        option.type = 'button';
        option.className = 'teacher-suggestion';
        option.setAttribute('role', 'option');
        option.dataset.name = name;
        option.textContent = name;
        suggestionList.appendChild(option);
      });

    suggestionList.hidden = suggestionList.childElementCount === 0;
    teacherInput.setAttribute('aria-expanded', String(!suggestionList.hidden));
  });

  suggestionList.addEventListener('pointerdown', (event) => {
    if (event.target.closest('.teacher-suggestion')) event.preventDefault();
  });
  suggestionList.addEventListener('click', (event) => {
    const option = event.target.closest('.teacher-suggestion');
    if (!option) return;
    teacherInput.value = option.dataset.name;
    closeSuggestions();
    teacherInput.focus();
  });
  teacherInput.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeSuggestions();
    if (event.key === 'ArrowDown' && !suggestionList.hidden) {
      event.preventDefault();
      suggestionList.querySelector('.teacher-suggestion')?.focus();
    }
  });
  document.addEventListener('pointerdown', (event) => {
    if (!event.target.closest('.teacher-autocomplete')) closeSuggestions();
  });
}

const itemDialog = document.getElementById('itemDialog');
if (itemDialog) {
  const detailImageTile = document.getElementById('detailImageTile');
  const detailImage = document.getElementById('detailImage');
  const detailStatusBreakdown = document.getElementById('detailStatusBreakdown');
  const requestTile = document.getElementById('requestTile');
  const unavailableTile = document.getElementById('unavailableTile');
  const unavailableTitle = document.getElementById('unavailableTitle');
  const requestQuantity = document.getElementById('requestQuantity');
  const addItemToCartButton = document.getElementById('addItemToCart');
  const cartCount = document.getElementById('borrowCartCount');
  const cartStorageKey = document.body.dataset.borrowCartKey;
  const cartItems = [];
  let selectedEquipment = null;
  let formRevealTimer;

  const showCartStorageWarning = (message) => {
    let warning = document.getElementById('borrowCartStorageWarning');
    if (!warning) {
      warning = document.createElement('div');
      warning.id = 'borrowCartStorageWarning';
      warning.className = 'flash';
      document.querySelector('.stud-main')?.prepend(warning);
    }
    if (warning) warning.textContent = translate(message);
  };
  try {
    const savedItems = JSON.parse(localStorage.getItem(cartStorageKey) || '[]');
    if (
      !Array.isArray(savedItems) ||
      savedItems.some((item) =>
        !item ||
        !Number.isSafeInteger(item.equipmentId) ||
        typeof item.name !== 'string' ||
        !Number.isSafeInteger(item.quantity) ||
        !Number.isSafeInteger(item.available)
      )
    ) {
      throw new Error('Saved cart is invalid.');
    }
    cartItems.push(...savedItems);
  } catch {
    showCartStorageWarning('Saved request items could not be loaded. Add equipment again.');
  }

  const quantityInCart = (name) =>
    cartItems.find((item) => item.name === name)?.quantity ?? 0;

  const saveCart = () => {
    try {
      localStorage.setItem(cartStorageKey, JSON.stringify(cartItems));
    } catch {
      showCartStorageWarning('Your request items could not be saved in this browser.');
    }
  };

  const syncAddToCartButton = () => {
    if (!selectedEquipment) return;
    const remaining = selectedEquipment.available - quantityInCart(selectedEquipment.name);
    requestQuantity.max = String(Math.max(1, remaining));
    addItemToCartButton.disabled = remaining <= 0;
    addItemToCartButton.textContent = remaining > 0 ? translate('Add to Request') : translate('All available added');
  };

  const renderCart = () => {
    saveCart();

    const totalQuantity = cartItems.reduce((total, item) => total + item.quantity, 0);
    cartCount.textContent = String(totalQuantity);
    cartCount.hidden = totalQuantity === 0;
    const borrowLink = document.getElementById('openBorrowCart');
    if (borrowLink) {
      borrowLink.setAttribute(
        'aria-label',
        translate(
          totalQuantity === 1 ? 'Open borrow request, {count} item' : 'Open borrow request, {count} items',
          { count: totalQuantity }
        )
      );
    }

    syncAddToCartButton();
  };

  requestQuantity.addEventListener('input', () => requestQuantity.setCustomValidity(''));
  addItemToCartButton.addEventListener('click', () => {
    if (!requestQuantity.reportValidity() || !selectedEquipment) return;
    const quantity = Number(requestQuantity.value);
    const alreadyRequested = quantityInCart(selectedEquipment.name);
    if (quantity > selectedEquipment.available - alreadyRequested) {
      requestQuantity.setCustomValidity(translate('Requested quantity exceeds the remaining available stock.'));
      requestQuantity.reportValidity();
      return;
    }

    const existing = cartItems.find((item) => item.name === selectedEquipment.name);
    if (existing) {
      existing.quantity += quantity;
    } else {
      cartItems.push({
        equipmentId: selectedEquipment.id,
        name: selectedEquipment.name,
        quantity,
        available: selectedEquipment.available
      });
    }
    renderCart();
    itemDialog.close();
  });

  renderCart();

  document.querySelectorAll('.item-open').forEach((button) => {
    button.addEventListener('click', () => {
      const unitStatus = button.dataset.status || 'available';
      const availableCount = Number(button.dataset.available || 0);
      const canBorrow = unitStatus === 'available' && availableCount > 0;
      const image = button.dataset.image;
      selectedEquipment = {
        id: Number(button.dataset.id),
        name: button.dataset.name,
        available: availableCount
      };

      const statusLabels = {
        available: translate('Available'),
        pending: translate('Pending Approval / Pickup'),
        borrowed: translate('Borrowed'),
        overdue: translate('Overdue'),
        lost: translate('Lost'),
        maintenance: translate('Under Maintenance')
      };

      document.getElementById('detailName').textContent = `${button.dataset.name} (${button.dataset.serial || ''})`;
      document.getElementById('detailStatus').textContent = statusLabels[unitStatus] || unitStatus;
      document.getElementById('detailStock').textContent =
        translate('{count} available', { count: availableCount });

      if (detailStatusBreakdown) {
        detailStatusBreakdown.replaceChildren();
        const metrics = [
          { key: 'available', label: 'Available', count: Number(button.dataset.available || 0) },
          { key: 'pending',   label: 'Pending',   count: Number(button.dataset.pending || 0) },
          { key: 'borrowed',  label: 'Borrowed',  count: Number(button.dataset.borrowed || 0) },
          { key: 'overdue',   label: 'Overdue',   count: Number(button.dataset.overdue || 0) },
          { key: 'lost',      label: 'Lost',      count: Number(button.dataset.lost || 0) },
        ];
        metrics.forEach(m => {
          const pill = document.createElement('span');
          pill.className = `bd-pill bd-${m.key}`;
          pill.textContent = `${m.label}: ${m.count}`;
          detailStatusBreakdown.appendChild(pill);
        });
      }

      const unavailableDesc = document.getElementById('unavailableDesc');
      if (unavailableTitle) {
        unavailableTitle.textContent = `${button.dataset.name} (${button.dataset.serial || 'N/A'}) — ${statusLabels[unitStatus] || unitStatus}`;
      }
      if (unavailableDesc) {
        const statusDescriptions = {
          pending: translate('This unit is currently reserved in a pending borrow request awaiting Technical Staff approval or QR code scan.'),
          borrowed: translate('This unit is currently checked out and in active use by a borrower.'),
          overdue: translate('This unit has exceeded its borrow duration and is awaiting return.'),
          lost: translate('This unit was not returned within 24 hours and is marked as lost.'),
          maintenance: translate('This unit is currently under maintenance by Technical Staff.')
        };
        unavailableDesc.textContent = statusDescriptions[unitStatus] || translate('Check back later or select an available unit.');
      }

      requestQuantity.setCustomValidity('');
      requestQuantity.value = '1';
      syncAddToCartButton();
      detailImageTile.classList.toggle('has-image', Boolean(image));
      detailImage.hidden = !image;
      detailImage.src = image;
      requestTile.hidden = !canBorrow;
      unavailableTile.hidden = canBorrow;
      itemDialog.classList.remove('show-form');
      itemDialog.showModal();

      window.clearTimeout(formRevealTimer);
      formRevealTimer = window.setTimeout(() => {
        if (itemDialog.open) itemDialog.classList.add('show-form');
      }, 220);
    });
  });

  itemDialog.querySelector('.dialog-close').addEventListener('click', () => itemDialog.close());
  itemDialog.addEventListener('cancel', (event) => {
    event.preventDefault();
  });
  itemDialog.addEventListener('close', () => {
    window.clearTimeout(formRevealTimer);
    requestQuantity.value = '1';
    requestQuantity.setCustomValidity('');
    itemDialog.classList.remove('show-form');
  });
}

const headerScannerDialog = document.getElementById('headerScannerDialog');
if (headerScannerDialog) {
  const openScannerButton = document.getElementById('openHeaderScanner');
  const closeScannerButton = document.getElementById('closeHeaderScanner');
  const retryScannerButton = document.getElementById('retryHeaderScanner');
  const qrImageFileInput = document.getElementById('qrImageFileInput');
  const scannerVideo = document.getElementById('headerScannerVideo');
  const scannerStatus = document.getElementById('headerScannerStatus');
  const scannerResult = document.getElementById('headerScannerResult');
  const scannerSerial = document.getElementById('headerScannerSerial');
  const manualQrInput = document.getElementById('manualQrToken');
  const scanCanvas = document.createElement('canvas');
  const scanCtx = scanCanvas.getContext('2d', { willReadFrequently: true });
  let scannerStream = null;
  let scannerDetector = null;
  let scannerTimer;
  let scannerRun = 0;

  const setHeaderScannerStatus = (message, isError = false) => {
    scannerStatus.textContent = message;
    scannerStatus.classList.toggle('is-error', isError);
  };

  const stopHeaderScanner = () => {
    scannerRun++;
    window.clearTimeout(scannerTimer);
    if (scannerStream) {
      scannerStream.getTracks().forEach((track) => track.stop());
      scannerStream = null;
    }
    scannerVideo.pause();
    scannerVideo.srcObject = null;
  };

  const activateScannedToken = async (rawValue) => {
    setHeaderScannerStatus(translate('Verifying QR code and starting borrow timer…'));
    try {
      const fd = new FormData();
      fd.append('do', 'scan_qr');
      fd.append('qr_token', rawValue);
      fd.append('ajax', '1');
      const res = await fetch('actions.php', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: fd
      });
      const data = await res.json();
      if (data.ok) {
        setHeaderScannerStatus(data.message || translate('Borrow timer started!'));
        window.setTimeout(() => {
          window.location.href = 'history.php';
        }, 900);
      } else {
        setHeaderScannerStatus(data.message || translate('Could not activate QR code.'), true);
        retryScannerButton.hidden = false;
      }
    } catch (err) {
      if (manualQrInput) {
        manualQrInput.value = rawValue;
        document.getElementById('manualQrForm')?.submit();
      }
    }
  };

  const detectQrFromVideo = async () => {
    if (scannerDetector) {
      try {
        const codes = await scannerDetector.detect(scannerVideo);
        const found = codes.find((entry) => entry.rawValue && entry.rawValue.trim());
        if (found) return found.rawValue.trim();
      } catch {
        // Fall through to jsQR canvas decoder
      }
    }

    if (typeof window.jsQR === 'function' && scanCtx) {
      const vw = scannerVideo.videoWidth;
      const vh = scannerVideo.videoHeight;
      if (vw > 0 && vh > 0) {
        const targetW = Math.min(640, vw);
        const targetH = Math.round((vh / vw) * targetW);
        scanCanvas.width = targetW;
        scanCanvas.height = targetH;
        scanCtx.drawImage(scannerVideo, 0, 0, targetW, targetH);
        const imageData = scanCtx.getImageData(0, 0, targetW, targetH);
        const qr = window.jsQR(imageData.data, targetW, targetH, {
          inversionAttempts: 'dontInvert'
        });
        if (qr && qr.data && qr.data.trim()) {
          return qr.data.trim();
        }
      }
    }
    return null;
  };

  const scanHeaderFrame = async (run) => {
    if (run !== scannerRun || !scannerStream) return;

    if (scannerVideo.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
      try {
        const detectedValue = await detectQrFromVideo();
        if (run !== scannerRun) return;
        if (detectedValue) {
          scannerSerial.textContent = detectedValue;
          scannerResult.hidden = false;
          stopHeaderScanner();
          await activateScannedToken(detectedValue);
          return;
        }
      } catch (error) {
        if (run !== scannerRun) return;
        stopHeaderScanner();
        retryScannerButton.hidden = false;
        setHeaderScannerStatus(translate('Could not read the QR code: {error}', { error: error.message }), true);
        return;
      }
    }

    if (run === scannerRun) {
      scannerTimer = window.setTimeout(() => scanHeaderFrame(run), 160);
    }
  };

  const startHeaderScanner = async () => {
    stopHeaderScanner();
    const run = scannerRun;
    scannerResult.hidden = true;
    scannerSerial.textContent = '';
    retryScannerButton.hidden = true;
    setHeaderScannerStatus(translate('Starting camera…'));

    if ('BarcodeDetector' in window) {
      try {
        scannerDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
      } catch {
        scannerDetector = null;
      }
    }

    if (!navigator.mediaDevices?.getUserMedia) {
      const isInsecureOrigin = window.location.protocol !== 'https:' &&
        window.location.hostname !== 'localhost' &&
        window.location.hostname !== '127.0.0.1';
      setHeaderScannerStatus(
        isInsecureOrigin
          ? translate('Live camera requires HTTPS or localhost. Tap "Scan from Photo / Camera App" or enter the Staff QR Code below.')
          : translate('Live camera is unavailable in this browser. Tap "Scan from Photo / Camera App" or enter the Staff QR Code below.'),
        true
      );
      return;
    }

    try {
      let stream;
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          audio: false,
          video: { facingMode: { ideal: 'environment' } }
        });
      } catch {
        stream = await navigator.mediaDevices.getUserMedia({
          audio: false,
          video: true
        });
      }

      if (run !== scannerRun || !headerScannerDialog.open) {
        stream.getTracks().forEach((track) => track.stop());
        return;
      }

      scannerStream = stream;
      scannerVideo.srcObject = stream;
      await scannerVideo.play();
      if (run !== scannerRun) return;
      setHeaderScannerStatus(translate('Point the camera at the QR code generated by Technical Staff.'));
      scanHeaderFrame(run);
    } catch (error) {
      if (run !== scannerRun) return;
      stopHeaderScanner();
      retryScannerButton.hidden = false;
      const message = error.name === 'NotAllowedError'
        ? translate('Camera access was denied. Allow camera permission in your browser, or use "Scan from Photo / Camera App" below.')
        : translate('Could not start the camera ({error}). Use "Scan from Photo / Camera App" or enter the Staff QR Code below.', { error: error.name || error.message });
      setHeaderScannerStatus(message, true);
    }
  };

  qrImageFileInput?.addEventListener('change', () => {
    const file = qrImageFileInput.files?.[0];
    if (!file) return;
    setHeaderScannerStatus(translate('Reading QR code from image…'));
    const img = new Image();
    const objectUrl = URL.createObjectURL(file);
    img.onload = async () => {
      URL.revokeObjectURL(objectUrl);
      const maxDim = 1024;
      const scale = Math.min(1, maxDim / Math.max(img.width, img.height));
      const w = Math.max(1, Math.round(img.width * scale));
      const h = Math.max(1, Math.round(img.height * scale));
      scanCanvas.width = w;
      scanCanvas.height = h;
      scanCtx.drawImage(img, 0, 0, w, h);

      let foundCode = null;
      if (scannerDetector) {
        try {
          const codes = await scannerDetector.detect(scanCanvas);
          const match = codes.find((c) => c.rawValue && c.rawValue.trim());
          if (match) foundCode = match.rawValue.trim();
        } catch {
          // fallback to jsQR
        }
      }
      if (!foundCode && typeof window.jsQR === 'function') {
        const imageData = scanCtx.getImageData(0, 0, w, h);
        const qr = window.jsQR(imageData.data, w, h);
        if (qr && qr.data && qr.data.trim()) {
          foundCode = qr.data.trim();
        }
      }

      qrImageFileInput.value = '';
      if (foundCode) {
        scannerSerial.textContent = foundCode;
        scannerResult.hidden = false;
        stopHeaderScanner();
        await activateScannedToken(foundCode);
      } else {
        setHeaderScannerStatus(translate('No valid QR code found in that image. Try again or enter the verification code below.'), true);
      }
    };
    img.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      qrImageFileInput.value = '';
      setHeaderScannerStatus(translate('Could not load the selected image.'), true);
    };
    img.src = objectUrl;
  });

  openScannerButton?.addEventListener('click', () => {
    closeNotifications();
    document.getElementById('profilePanel')?.classList.remove('show');
    headerScannerDialog.showModal();
    startHeaderScanner();
  });
  closeScannerButton?.addEventListener('click', () => headerScannerDialog.close());
  retryScannerButton?.addEventListener('click', startHeaderScanner);
  headerScannerDialog.addEventListener('cancel', (event) => event.preventDefault());
  headerScannerDialog.addEventListener('close', stopHeaderScanner);
}

// Live countdown timer for borrowed / overdue items AND 10-minute QR pickup expiry in Borrow History
function updateLiveTimers() {
  const now = Date.now();
  const badges = document.querySelectorAll('[data-due-at]');
  badges.forEach((badge) => {
    const dueTime = new Date(badge.dataset.dueAt).getTime();
    if (Number.isNaN(dueTime)) return;
    const diffSec = Math.floor((dueTime - now) / 1000);
    if (diffSec > 0) {
      const hrs = Math.floor(diffSec / 3600);
      const mins = Math.floor((diffSec % 3600) / 60);
      const secs = diffSec % 60;
      const timeStr = hrs > 0
        ? `${hrs}h ${String(mins).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s left`
        : `${mins}m ${String(secs).padStart(2, '0')}s left`;
      badge.textContent = timeStr;
      badge.classList.remove('is-overdue');
      badge.classList.add('is-running');
    } else {
      const overSec = Math.abs(diffSec);
      const hrs = Math.floor(overSec / 3600);
      const mins = Math.floor((overSec % 3600) / 60);
      badge.textContent = hrs > 0 ? `OVERDUE by ${hrs}h ${mins}m` : `OVERDUE by ${mins}m`;
      badge.classList.remove('is-running');
      badge.classList.add('is-overdue');
    }
  });

  const pickupBadges = document.querySelectorAll('[data-pickup-expires-at]');
  pickupBadges.forEach((badge) => {
    const expTime = new Date(badge.dataset.pickupExpiresAt).getTime();
    if (Number.isNaN(expTime)) return;
    const diffSec = Math.floor((expTime - now) / 1000);
    if (diffSec > 0) {
      const mins = Math.floor(diffSec / 60);
      const secs = diffSec % 60;
      badge.textContent = `Scan QR within ${String(mins).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s`;
    } else {
      badge.textContent = 'Pickup window (10m) expired — refreshing…';
      badge.classList.remove('awaiting-scan');
      badge.classList.add('is-overdue');
      if (!badge.dataset.reloading) {
        badge.dataset.reloading = '1';
        window.setTimeout(() => window.location.reload(), 1200);
      }
    }
  });
}
if (document.querySelector('[data-due-at], [data-pickup-expires-at]')) {
  updateLiveTimers();
  window.setInterval(updateLiveTimers, 1000);
}

const dashboardActionUrl = new URL(window.location.href);
const dashboardAction = dashboardActionUrl.searchParams.get('open');
if (dashboardAction) {
  dashboardActionUrl.searchParams.delete('open');
  window.history.replaceState(null, '', `${dashboardActionUrl.pathname}${dashboardActionUrl.search}${dashboardActionUrl.hash}`);

  if (dashboardAction === 'scanner') {
    document.getElementById('openHeaderScanner')?.click();
  }
}

// stagger entrance for equipment cards
document.querySelectorAll('.equip-card').forEach((c, i) => {
  c.style.animationDelay = (i * 60) + 'ms';
});
