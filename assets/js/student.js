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
  if (!main || !header) return;

  main.style.setProperty('--notification-top', `${header.getBoundingClientRect().bottom}px`);
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
    document.querySelector('.equip-grid').appendChild(emptyMessage);
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
  const requestTile = document.getElementById('requestTile');
  const unavailableTile = document.getElementById('unavailableTile');
  const requestQuantity = document.getElementById('requestQuantity');
  const addItemToCartButton = document.getElementById('addItemToCart');
  const cartDialog = document.getElementById('borrowCartDialog');
  const cartItemsList = document.getElementById('borrowCartItems');
  const cartEmptyMessage = document.getElementById('borrowCartEmpty');
  const cartHiddenItems = document.getElementById('borrowCartHiddenItems');
  const cartCount = document.getElementById('borrowCartCount');
  const submitCartButton = document.getElementById('submitBorrowCart');
  const cartItems = [];
  let selectedEquipment = null;
  let formRevealTimer;

  const quantityInCart = (name) =>
    cartItems.find((item) => item.name === name)?.quantity ?? 0;

  const syncAddToCartButton = () => {
    if (!selectedEquipment) return;
    const remaining = selectedEquipment.available - quantityInCart(selectedEquipment.name);
    requestQuantity.max = String(Math.max(1, remaining));
    addItemToCartButton.disabled = remaining <= 0;
    addItemToCartButton.textContent = remaining > 0 ? translate('Add to Request') : translate('All available added');
  };

  const renderCart = () => {
    cartItemsList.replaceChildren();
    cartHiddenItems.replaceChildren();
    cartEmptyMessage.hidden = cartItems.length > 0;
    submitCartButton.disabled = cartItems.length === 0;

    const totalQuantity = cartItems.reduce((total, item) => total + item.quantity, 0);
    cartCount.textContent = String(totalQuantity);
    cartCount.hidden = totalQuantity === 0;
    document.getElementById('openBorrowCart').setAttribute(
      'aria-label',
      translate(
        totalQuantity === 1 ? 'Open borrow request, {count} item' : 'Open borrow request, {count} items',
        { count: totalQuantity }
      )
    );

    cartItems.forEach((item, index) => {
      const row = document.createElement('li');
      row.className = 'borrow-cart-item';

      const details = document.createElement('div');
      details.className = 'borrow-cart-item-details';
      const name = document.createElement('strong');
      name.textContent = item.name;
      const stock = document.createElement('small');
      stock.textContent = translate('{count} available', { count: item.available });
      details.append(name, stock);

      const controls = document.createElement('div');
      controls.className = 'borrow-cart-item-controls';
      const decrease = document.createElement('button');
      decrease.type = 'button';
      decrease.dataset.cartAction = 'decrease';
      decrease.dataset.index = String(index);
      decrease.setAttribute('aria-label', translate('Decrease {name} quantity', { name: item.name }));
      decrease.textContent = '−';
      decrease.disabled = item.quantity <= 1;
      const quantity = document.createElement('span');
      quantity.textContent = String(item.quantity);
      const increase = document.createElement('button');
      increase.type = 'button';
      increase.dataset.cartAction = 'increase';
      increase.dataset.index = String(index);
      increase.setAttribute('aria-label', translate('Increase {name} quantity', { name: item.name }));
      increase.textContent = '+';
      increase.disabled = item.quantity >= item.available;
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'borrow-cart-remove';
      remove.dataset.cartAction = 'remove';
      remove.dataset.index = String(index);
      remove.textContent = translate('Remove');
      controls.append(decrease, quantity, increase, remove);
      row.append(details, controls);
      cartItemsList.appendChild(row);

      const equipmentIdInput = document.createElement('input');
      equipmentIdInput.type = 'hidden';
      equipmentIdInput.name = 'equipment_id[]';
      equipmentIdInput.value = String(item.equipmentId);
      const itemQuantityInput = document.createElement('input');
      itemQuantityInput.type = 'hidden';
      itemQuantityInput.name = 'quantity[]';
      itemQuantityInput.value = String(item.quantity);
      cartHiddenItems.append(equipmentIdInput, itemQuantityInput);
    });

    syncAddToCartButton();
  };

  cartItemsList.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-cart-action]');
    if (!button) return;
    const index = Number(button.dataset.index);
    const item = cartItems[index];
    if (!item) return;

    if (button.dataset.cartAction === 'increase' && item.quantity < item.available) {
      item.quantity++;
    } else if (button.dataset.cartAction === 'decrease' && item.quantity > 1) {
      item.quantity--;
    } else if (button.dataset.cartAction === 'remove') {
      cartItems.splice(index, 1);
    }
    renderCart();
  });

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

  document.querySelectorAll('.item-open').forEach((button) => {
    button.addEventListener('click', () => {
      const available = button.dataset.status === 'available';
      const image = button.dataset.image;
      selectedEquipment = {
        id: Number(button.dataset.id),
        name: button.dataset.name,
        available: Number(button.dataset.available)
      };

      document.getElementById('detailName').textContent = button.dataset.name;
      document.getElementById('detailStatus').textContent = available ? translate('Available') : translate('Unavailable');
      document.getElementById('detailStock').textContent =
        translate('{count} available', { count: button.dataset.available });
      requestQuantity.setCustomValidity('');
      requestQuantity.value = '1';
      syncAddToCartButton();
      detailImageTile.classList.toggle('has-image', Boolean(image));
      detailImage.hidden = !image;
      detailImage.src = image;
      requestTile.hidden = !available;
      unavailableTile.hidden = available;
      itemDialog.classList.remove('show-form');
      itemDialog.showModal();

      window.clearTimeout(formRevealTimer);
      if (available) {
        formRevealTimer = window.setTimeout(() => {
          if (itemDialog.open) itemDialog.classList.add('show-form');
        }, 420);
      }
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

  document.getElementById('openBorrowCart').addEventListener('click', () => {
    closeNotifications();
    document.getElementById('profilePanel').classList.remove('show');
    cartDialog.showModal();
  });
  document.getElementById('closeBorrowCart').addEventListener('click', () => cartDialog.close());
  cartDialog.addEventListener('cancel', (event) => event.preventDefault());
}

const headerScannerDialog = document.getElementById('headerScannerDialog');
if (headerScannerDialog) {
  const openScannerButton = document.getElementById('openHeaderScanner');
  const closeScannerButton = document.getElementById('closeHeaderScanner');
  const retryScannerButton = document.getElementById('retryHeaderScanner');
  const scannerVideo = document.getElementById('headerScannerVideo');
  const scannerStatus = document.getElementById('headerScannerStatus');
  const scannerResult = document.getElementById('headerScannerResult');
  const scannerSerial = document.getElementById('headerScannerSerial');
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

  const scanHeaderFrame = async (run) => {
    if (run !== scannerRun || !scannerStream) return;

    if (scannerVideo.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
      try {
        const codes = await scannerDetector.detect(scannerVideo);
        if (run !== scannerRun) return;
        const code = codes.find((entry) => entry.rawValue.trim());
        if (code) {
          scannerSerial.textContent = code.rawValue.trim();
          scannerResult.hidden = false;
          setHeaderScannerStatus(translate('QR code scanned.'));
          stopHeaderScanner();
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
      scannerTimer = window.setTimeout(() => scanHeaderFrame(run), 180);
    }
  };

  const startHeaderScanner = async () => {
    stopHeaderScanner();
    const run = scannerRun;
    scannerResult.hidden = true;
    scannerSerial.textContent = '';
    retryScannerButton.hidden = true;
    setHeaderScannerStatus(translate('Starting camera…'));

    if (!('BarcodeDetector' in window)) {
      setHeaderScannerStatus(translate('QR scanning is not supported by this browser.'), true);
      return;
    }
    if (!navigator.mediaDevices?.getUserMedia) {
      setHeaderScannerStatus(translate('Camera access is unavailable in this browser.'), true);
      return;
    }

    try {
      scannerDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
    } catch (error) {
      setHeaderScannerStatus(translate('Could not start the QR scanner: {error}', { error: error.message }), true);
      return;
    }

    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        audio: false,
        video: { facingMode: { ideal: 'environment' } }
      });
      if (run !== scannerRun || !headerScannerDialog.open) {
        stream.getTracks().forEach((track) => track.stop());
        return;
      }

      scannerStream = stream;
      scannerVideo.srcObject = stream;
      await scannerVideo.play();
      if (run !== scannerRun) return;
      setHeaderScannerStatus(translate('Point the camera at an item QR code.'));
      scanHeaderFrame(run);
    } catch (error) {
      if (run !== scannerRun) return;
      stopHeaderScanner();
      retryScannerButton.hidden = false;
      const message = error.name === 'NotAllowedError'
        ? translate('Camera access was denied. Allow camera access and try again.')
        : translate('Could not start the camera: {error}', { error: error.message });
      setHeaderScannerStatus(message, true);
    }
  };

  openScannerButton.addEventListener('click', () => {
    closeNotifications();
    document.getElementById('profilePanel').classList.remove('show');
    headerScannerDialog.showModal();
    startHeaderScanner();
  });
  closeScannerButton.addEventListener('click', () => headerScannerDialog.close());
  retryScannerButton.addEventListener('click', startHeaderScanner);
  headerScannerDialog.addEventListener('cancel', (event) => event.preventDefault());
  headerScannerDialog.addEventListener('close', stopHeaderScanner);
}

const dashboardActionUrl = new URL(window.location.href);
const dashboardAction = dashboardActionUrl.searchParams.get('open');
if (dashboardAction) {
  dashboardActionUrl.searchParams.delete('open');
  window.history.replaceState(null, '', `${dashboardActionUrl.pathname}${dashboardActionUrl.search}${dashboardActionUrl.hash}`);

  if (dashboardAction === 'borrow') {
    document.getElementById('borrowCartDialog')?.showModal();
  } else if (dashboardAction === 'scanner') {
    document.getElementById('openHeaderScanner')?.click();
  }
}

// stagger entrance for equipment cards
document.querySelectorAll('.equip-card').forEach((c, i) => {
  c.style.animationDelay = (i * 60) + 'ms';
});
