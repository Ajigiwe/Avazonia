const CACHE_NAME = 'avazonia-v2';
const ASSETS_TO_CACHE = [
  '/',
  '/public/css/styles.css',
  '/public/assets/img/pwa-icon.png',
  '/public/assets/img/logo.jpg'
];

// Install Event
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Version 2 Installed');
      return cache.addAll(ASSETS_TO_CACHE).catch(err => console.warn('Cache addAll error:', err));
    })
  );
});

// Activate Event
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('[Service Worker] Clearing old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  // Simple Network-First strategy for pages
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => {
        return caches.match('/');
      })
    );
    return;
  }

  // Cache-First strategy for assets
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});
