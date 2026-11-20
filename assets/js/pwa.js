const pwaScript = document.currentScript;
const appBase = new URL('../../', pwaScript.src);
const installButton = document.createElement('button');
const installHelp = document.createElement('div');
let installPrompt = null;

installButton.type = 'button';
installButton.className = 'pwa-install-button';
installButton.textContent = 'Install app';
installButton.hidden = true;
installButton.setAttribute('aria-label', 'Install ACLC Inventory app');

installHelp.className = 'pwa-install-help';
installHelp.setAttribute('role', 'status');
installHelp.setAttribute('aria-live', 'polite');
installHelp.hidden = true;
document.body.append(installButton, installHelp);
if (document.querySelector('.sidebar')) document.body.classList.add('has-admin-nav');
if (document.querySelector('.stud-topbar .stud-right')) document.body.classList.add('has-student-nav');

const isStandalone = window.matchMedia('(display-mode: standalone)').matches
  || window.navigator.standalone === true;
const isAppleMobile = /iPhone|iPad|iPod/.test(navigator.userAgent)
  || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

if (!isStandalone && isAppleMobile) installButton.hidden = false;

window.addEventListener('beforeinstallprompt', event => {
  event.preventDefault();
  installPrompt = event;
  installButton.hidden = false;
});

window.addEventListener('appinstalled', () => {
  installPrompt = null;
  installButton.hidden = true;
  installHelp.hidden = true;
});

installButton.addEventListener('click', async () => {
  if (!installPrompt) {
    installHelp.textContent = 'To install, tap the Share button in your browser, then choose “Add to Home Screen”.';
    installHelp.hidden = false;
    return;
  }

  installPrompt.prompt();
  const choice = await installPrompt.userChoice;
  installPrompt = null;
  if (choice.outcome === 'accepted') installButton.hidden = true;
});

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register(new URL('service-worker.js', appBase))
    .catch(error => console.error('Unable to register the offline app support:', error));
}
