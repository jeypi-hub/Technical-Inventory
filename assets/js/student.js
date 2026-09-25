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

// stagger entrance for equipment cards
document.querySelectorAll('.equip-card').forEach((c, i) => {
  c.style.animationDelay = (i * 60) + 'ms';
});
