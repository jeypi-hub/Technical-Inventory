
function toggleNotif() {
  document.getElementById('notifPanel')?.classList.toggle('show');
  document.getElementById('profilePanel')?.classList.remove('show');
}
function toggleProfile() {
  document.getElementById('profilePanel')?.classList.toggle('show');
  document.getElementById('notifPanel')?.classList.remove('show');
}
document.addEventListener('click', (e) => {
  if (!e.target.closest('.notif'))   document.getElementById('notifPanel')?.classList.remove('show');
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
    availability.textContent = `${group.available} available`;
    const title = document.createElement('h2');
    title.id = 'catalogModalTitle';
    title.textContent = group.name;
    catalogModalImage.append(shade, availability, title);
    catalogModal.setAttribute('aria-labelledby', title.id);

    catalogModalStatus.textContent = group.available === group.quantity
      ? 'Available'
      : group.available > 0 ? 'Partially available' : 'Unavailable';

    catalogModalItems.replaceChildren();
    group.items.forEach(item => {
      const row = document.createElement('article');
      row.className = 'catalog-unit-card';
      const info = document.createElement('div');
      info.className = 'catalog-unit-info';
      const label = document.createElement('span');
      label.className = 'catalog-unit-label';
      label.textContent = 'Serial number';
      const serial = document.createElement('strong');
      serial.textContent = item.serial_number;
      const status = document.createElement('span');
      status.className = `status ${item.status}`;
      status.textContent = item.status.charAt(0).toUpperCase() + item.status.slice(1);
      info.append(label, serial, status);

      const remove = document.createElement('a');
      remove.className = 'link-del';
      remove.href = `actions.php?do=delete_equipment&id=${encodeURIComponent(item.id)}`;
      remove.textContent = 'Delete';
      remove.addEventListener('click', event => {
        if (!confirm('Delete this equipment?')) event.preventDefault();
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
  }
});

setInterval(() => {
  const isModalOpen = document.getElementById('returnModal')?.classList.contains('show');
  const isCatalogModalOpen = catalogModal?.classList.contains('show');
  const isAddEquipmentModalOpen = addEquipmentModal?.classList.contains('show');
  const isNotifOpen = document.getElementById('notifPanel')?.classList.contains('show');
  const isProfileOpen = document.getElementById('profilePanel')?.classList.contains('show');
  
  if (!isModalOpen && !isCatalogModalOpen && !isAddEquipmentModalOpen && !isNotifOpen && !isProfileOpen) {
    location.reload();
  }
}, 60000);