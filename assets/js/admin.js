// ---------- notification & profile panels ----------
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

// ---------- return modal ----------
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

// ---------- dropzone (manage equipment) ----------
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

// ---------- auto refresh dashboard (checks every 60 seconds) ----------
setInterval(() => {
  const isModalOpen = document.getElementById('returnModal')?.classList.contains('show');
  const isNotifOpen = document.getElementById('notifPanel')?.classList.contains('show');
  const isProfileOpen = document.getElementById('profilePanel')?.classList.contains('show');
  
  // Only reload if the user is not actively interacting with menus or modals
  if (!isModalOpen && !isNotifOpen && !isProfileOpen) {
    location.reload();
  }
}, 60000); // 60000ms = 1 minute