const CACHE_NAME = 'avazonia-premium-v5';
const ASSETS_TO_PRECACHE = [
  '/',
  '/public/css/styles.css',
  '/public/assets/img/logo.png',
  '/public/assets/img/logo2-rounded.png'
];

// Install Event
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Pre-caching core assets');
      return cache.addAll(ASSETS_TO_PRECACHE);
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
            console.log('[SW] Clearing old cache');
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch Event - Network First with Cache Fallback
self.addEventListener('fetch', (event) => {
  // We only handle GET requests
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        // Skip caching HTML pages (they depend on cookies like language)
        const ct = networkResponse.headers.get('content-type') || '';
        const isHTML = ct.includes('text/html');
        // NEVER cache install-critical files; they must always update from network
        const url = event.request.url;
        const mustRevalidate = url.includes('/manifest.webmanifest')
            || url.includes('/apple-touch-startup-image')
            || url.includes('/icon-maskable')
            || url.includes('/apple-splash-');
        // If successful and not HTML, cache it
        if (networkResponse && networkResponse.status === 200 && !isHTML && !mustRevalidate) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
                cache.put(event.request, responseToCache);
            });
        }
        return networkResponse;
      })
      .catch(() => {
        // If network fails (offline), try the cache
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) return cachedResponse;
          
          // Fallback for navigation requests
          if (event.request.mode === 'navigate') {
            return caches.match('/');
          }
        });
      })
  );
});
