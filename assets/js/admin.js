const translate = window.appTranslate || (key => key);

function closeNotifications() {
  const panel = document.getElementById('notifPanel');
  panel?.classList.remove('show');
  panel?.setAttribute('aria-hidden', 'true');
  document.querySelector('.notif-btn')?.setAttribute('aria-expanded', 'false');
}

function updateNotificationViewBounds() {
  const main = document.querySelector('.main');
  const header = document.querySelector('.stud-topbar');
  const notifBtn = document.querySelector('.notif-btn');
  if (!main || !header) return;
  main.style.setProperty('--notification-top', `${header.getBoundingClientRect().bottom + 6}px`);
  if (notifBtn) {
    const btnRect = notifBtn.getBoundingClientRect();
    const rightOffset = Math.max(12, window.innerWidth - btnRect.right);
    main.style.setProperty('--notification-right', `${rightOffset}px`);
  }
}

function toggleNotif() {
  const panel = document.getElementById('notifPanel');
  const isOpen = panel?.classList.toggle('show') ?? false;
  panel?.setAttribute('aria-hidden', String(!isOpen));
  document.querySelector('.notif-btn')?.setAttribute('aria-expanded', String(isOpen));
  if (isOpen) {
    updateNotificationViewBounds();
    document.getElementById('profilePanel')?.classList.remove('show');
  }
}
function toggleProfile() {
  const panel = document.getElementById('profilePanel');
  const isOpen = panel?.classList.toggle('show') ?? false;
  document.querySelector('.profile[role="button"]')?.setAttribute('aria-expanded', String(isOpen));
  closeNotifications();
}
window.addEventListener('resize', () => {
  if (document.getElementById('notifPanel')?.classList.contains('show')) {
    updateNotificationViewBounds();
  }
});
document.addEventListener('click', (e) => {
  if (!e.target.closest('.notif') && !e.target.closest('#notifPanel')) closeNotifications();
  if (!e.target.closest('.profile')) document.getElementById('profilePanel')?.classList.remove('show');
});

let currentReturnId = null;
function markReturn(id) {
  currentReturnId = id;
  document.getElementById('returnModal')?.classList.add('show');
}
function closeModal() {
  document.getElementById('returnModal')?.classList.remove('show');
  currentReturnId = null;
}
document.querySelectorAll('.condition-choices button').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!currentReturnId) return;
    const fd = new FormData();
    fd.append('do', 'mark_return');
    fd.append('id', currentReturnId);
    fd.append('condition', btn.dataset.c);
    const res = await fetch('actions.php', { method: 'POST', body: fd });
    if (res.ok) location.reload();
  });
});

// QR Code Modal for Approved Requests
const qrDisplayModal = document.getElementById('qrDisplayModal');
function openQrModal(btn) {
  if (!qrDisplayModal || !btn?.dataset.qr) return;
  const payload = JSON.parse(btn.dataset.qr);
  const svgContainer = document.getElementById('qrModalSvgContainer');
  const tokenText = document.getElementById('qrModalTokenText');
  const detailsText = document.getElementById('qrModalDetailsText');

  if (svgContainer && typeof window.generateQrSvg === 'function') {
    svgContainer.innerHTML = window.generateQrSvg(payload.qr_token, 230);
  }
  if (tokenText) {
    tokenText.textContent = payload.qr_token;
  }
  if (detailsText) {
    detailsText.textContent = `${payload.equipment_name} (${payload.serial_number}) × ${payload.quantity} · ${payload.student_name} (${payload.student_uid}) · Duration: ${payload.duration_label}`;
  }
  qrDisplayModal.classList.add('show');
  qrDisplayModal.setAttribute('aria-hidden', 'false');
}

function closeQrModal() {
  if (!qrDisplayModal) return;
  qrDisplayModal.classList.remove('show');
  qrDisplayModal.setAttribute('aria-hidden', 'true');
}

qrDisplayModal?.addEventListener('click', (event) => {
  if (event.target === qrDisplayModal) closeQrModal();
});

// Automatically pop open the QR modal if staff just approved a request (?qr_id=...)
const autoOpenQrBtn = document.querySelector('button[data-auto-open="1"]');
if (autoOpenQrBtn) {
  openQrModal(autoOpenQrBtn);
  const cleanUrl = new URL(window.location.href);
  cleanUrl.searchParams.delete('qr_id');
  window.history.replaceState(null, '', `${cleanUrl.pathname}${cleanUrl.search}${cleanUrl.hash}`);
}

const dz  = document.getElementById('dropzone');
const inp = document.getElementById('imageInput');
if (dz && inp) {
  ['dragover','dragenter'].forEach(e => dz.addEventListener(e, ev => {
    ev.preventDefault(); dz.classList.add('drag');
  }));
  ['dragleave','drop'].forEach(e => dz.addEventListener(e, ev => {
    ev.preventDefault(); dz.classList.remove('drag');
  }));
  dz.addEventListener('drop', ev => {
    if (ev.dataTransfer.files.length) {
      inp.files = ev.dataTransfer.files;
      showName(ev.dataTransfer.files[0].name);
    }
  });
  inp.addEventListener('change', () => inp.files[0] && showName(inp.files[0].name));
  function showName(n) { document.getElementById('fileName').textContent = n; }
}

const addEquipmentModal = document.getElementById('addEquipmentModal');
const openAddEquipment = document.getElementById('openAddEquipment');
const closeAddEquipment = document.getElementById('closeAddEquipment');
const cancelAddEquipment = document.getElementById('cancelAddEquipment');
const equipmentNameInput = document.getElementById('equipmentName');
let addEquipmentTrigger = null;

function openEquipmentAddModal() {
  addEquipmentTrigger = document.activeElement;
  addEquipmentModal.classList.add('show');
  addEquipmentModal.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  equipmentNameInput.focus();
}

function closeEquipmentAddModal() {
  if (!addEquipmentModal?.classList.contains('show')) return;
  addEquipmentModal.classList.remove('show');
  addEquipmentModal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
  addEquipmentTrigger?.focus();
}

openAddEquipment?.addEventListener('click', openEquipmentAddModal);
closeAddEquipment?.addEventListener('click', closeEquipmentAddModal);
cancelAddEquipment?.addEventListener('click', closeEquipmentAddModal);
addEquipmentModal?.addEventListener('click', event => {
  if (event.target === addEquipmentModal) closeEquipmentAddModal();
});

const catalogModal = document.getElementById('catalogModal');
const catalogModalStatus = document.getElementById('catalogModalStatus');
const catalogModalImage = document.getElementById('catalogModalImage');
const catalogModalItems = document.getElementById('catalogModalItems');
const catalogModalClose = document.getElementById('catalogModalClose');
let catalogTrigger = null;

function closeCatalogModal() {
  if (!catalogModal?.classList.contains('show')) return;
  catalogModal.classList.remove('show');
  catalogModal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
  catalogTrigger?.focus();
}

document.querySelectorAll('.equipment-tile').forEach(tile => {
  tile.addEventListener('click', () => {
    const group = JSON.parse(tile.dataset.group);
    catalogTrigger = tile;
    catalogModalImage.replaceChildren();
    if (group.image) {
      const image = document.createElement('img');
      image.src = `../assets/upload/${group.image}`;
      image.alt = group.name;
      catalogModalImage.append(image);
    } else {
      const emptyImage = document.createElement('span');
      emptyImage.className = 'equipment-image-empty';
      emptyImage.setAttribute('aria-hidden', 'true');
      catalogModalImage.append(emptyImage);
    }

    const shade = document.createElement('span');
    shade.className = 'equipment-image-shade';
    shade.setAttribute('aria-hidden', 'true');
    const availability = document.createElement('span');
    availability.className = 'equipment-availability';
    availability.textContent = translate('{count} available', { count: group.available });
    const title = document.createElement('h2');
    title.id = 'catalogModalTitle';
    title.textContent = group.name;
    catalogModalImage.append(shade, availability, title);
    catalogModal.setAttribute('aria-labelledby', title.id);

    catalogModalStatus.textContent = `Available: ${group.available} · Pending: ${group.pending || 0} · Borrowed: ${group.borrowed || 0} · Overdue: ${group.overdue || 0} · Lost: ${group.lost || 0}`;

    catalogModalItems.replaceChildren();
    group.items.forEach(item => {
      const row = document.createElement('article');
      row.className = 'catalog-unit-card';
      const info = document.createElement('div');
      info.className = 'catalog-unit-info';
      const label = document.createElement('span');
      label.className = 'catalog-unit-label';
      label.textContent = translate('Serial number');
      const serial = document.createElement('strong');
      serial.textContent = item.serial_number;
      const status = document.createElement('span');
      status.className = `status ${item.status}`;
      status.textContent = translate(item.status.charAt(0).toUpperCase() + item.status.slice(1));
      info.append(label, serial, status);

      const remove = document.createElement('a');
      remove.className = 'link-del';
      remove.href = `actions.php?do=delete_equipment&id=${encodeURIComponent(item.id)}`;
      remove.textContent = translate('Delete');
      remove.addEventListener('click', event => {
        if (!confirm(translate('Delete this equipment?'))) event.preventDefault();
      });
      row.append(info, remove);
      catalogModalItems.append(row);
    });

    catalogModal.classList.add('show');
    catalogModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    catalogModalClose.focus();
  });
});

catalogModalClose?.addEventListener('click', closeCatalogModal);
catalogModal?.addEventListener('click', event => {
  if (event.target === catalogModal) closeCatalogModal();
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape') {
    closeCatalogModal();
    closeEquipmentAddModal();
    closeQrModal();
  }
});

// Live countdown for Active Checkouts in Staff/Admin portal
function updateAdminTimers() {
  const timers = document.querySelectorAll('.checkout-timer[data-due-at]');
  if (!timers.length) return;
  const now = Date.now();
  timers.forEach((el) => {
    const dueTime = new Date(el.dataset.dueAt).getTime();
    if (Number.isNaN(dueTime)) return;
    const diffSec = Math.floor((dueTime - now) / 1000);
    if (diffSec > 0) {
      const hrs = Math.floor(diffSec / 3600);
      const mins = Math.floor((diffSec % 3600) / 60);
      const secs = diffSec % 60;
      el.textContent = hrs > 0
        ? `Time left: ${hrs}h ${String(mins).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s`
        : `Time left: ${mins}m ${String(secs).padStart(2, '0')}s`;
    } else {
      const overSec = Math.abs(diffSec);
      const hrs = Math.floor(overSec / 3600);
      const mins = Math.floor((overSec % 3600) / 60);
      el.textContent = hrs > 0 ? `OVERDUE by ${hrs}h ${mins}m` : `OVERDUE by ${mins}m`;
      el.closest('.active-row')?.classList.add('overdue-row');
    }
  });
}
if (document.querySelector('.checkout-timer[data-due-at]')) {
  updateAdminTimers();
  window.setInterval(updateAdminTimers, 1000);
}

setInterval(() => {
  const isModalOpen = document.getElementById('returnModal')?.classList.contains('show');
  const isQrModalOpen = qrDisplayModal?.classList.contains('show');
  const isCatalogModalOpen = catalogModal?.classList.contains('show');
  const isAddEquipmentModalOpen = addEquipmentModal?.classList.contains('show');
  const isNotifOpen = document.getElementById('notifPanel')?.classList.contains('show');
  const isProfileOpen = document.getElementById('profilePanel')?.classList.contains('show');
  
  if (!isModalOpen && !isQrModalOpen && !isCatalogModalOpen && !isAddEquipmentModalOpen && !isNotifOpen && !isProfileOpen) {
    location.reload();
  }
}, 60000);
