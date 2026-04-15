const CACHE_NAME = 'avazonia-v1';
const ASSETS_TO_CACHE = [
  './',
  './public/css/styles.css',
  './public/assets/img/logo.png',
  './public/assets/img/logo.jpg'
];

// Install Event - Pre-cache assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[Service Worker] Pre-caching offline assets');
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// Activate Event - Clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('[Service Worker] Removing old cache', cache);
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch Event - Network First with Cache Fallback for navigation, 
// Cache First for assets
self.addEventListener('fetch', (event) => {
  // Check if it's a navigation request (HTML page)
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          return caches.match('./');
        })
    );
    return;
  }

  // For other assets (CSS, JS, Images), use Cache First strategy
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});
