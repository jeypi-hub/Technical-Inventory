const CACHE_NAME = 'aclc-inventory-static-v2';
const APP_BASE = new URL('./', self.location).pathname;
const PRECACHE_URLS = [
  'offline.html',
  'manifest.webmanifest',
  'assets/css/admin_dashboard.css',
  'assets/css/admin_login.css',
  'assets/css/history.css',
  'assets/css/landing.css',
  'assets/css/manage_equipment.css',
  'assets/css/pwa.css',
  'assets/css/student_dashboard.css',
  'assets/css/student_login.css',
  'assets/js/admin.js',
  'assets/js/charts.js',
  'assets/js/pwa.js',
  'assets/js/student.js',
  'assets/images/aclc_logo.png',
  'assets/images/pwa-192.png',
  'assets/images/pwa-512.png'
].map(path => `${APP_BASE}${path}`);

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(key => key.startsWith('aclc-inventory-static-') && key !== CACHE_NAME)
          .map(key => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== 'GET' || url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(async () => {
        const offlinePage = await caches.match(`${APP_BASE}offline.html`);
        return offlinePage || Response.error();
      })
    );
    return;
  }

  if (PRECACHE_URLS.includes(url.pathname)) {
    event.respondWith(
      caches.match(request).then(cached => cached || fetch(request))
    );
  }
});
