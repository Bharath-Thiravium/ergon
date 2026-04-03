const CACHE_VERSION = 'ergon-v4';
const STATIC_CACHE  = `${CACHE_VERSION}-static`;
const PAGE_CACHE    = `${CACHE_VERSION}-pages`;
const MAX_PAGE_AGE  = 60 * 60 * 1000; // 1 hour

const STATIC_ASSETS = [
  '/ergon/assets/css/ergon.css',
  '/ergon/assets/css/bootstrap-icons.min.css',
  '/ergon/assets/css/theme-enhanced.css',
  '/ergon/assets/css/utilities-new.css',
  '/ergon/assets/css/instant-theme.css',
  '/ergon/assets/css/responsive-mobile.css',
  '/ergon/assets/css/mobile-dark-theme-fixes.css',
  '/ergon/assets/css/dark-mode-alerts-fix.css',
  '/ergon/assets/css/modal.css',
  '/ergon/assets/css/ergon-overrides.css',
  '/ergon/assets/css/premium-navigation.css',
  '/ergon/assets/js/ergon-core.min.js',
  '/ergon/assets/js/theme-preload.js',
  '/ergon/assets/js/theme-switcher.js',
  '/ergon/assets/js/modal.js',
  '/ergon/assets/js/premium-navigation.js',
  '/ergon/assets/js/mobile-table-cards.js',
  '/ergon/assets/js/table-utils.js',
  '/ergon/assets/fonts/bootstrap-icons.woff2',
  '/ergon/assets/icons/icon-192.png',
  '/ergon/assets/icons/icon-512.png',
  '/ergon/manifest.json',
  '/ergon/offline.html',
];

// ─── Install ─────────────────────────────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// ─── Activate ────────────────────────────────────────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(k => k !== STATIC_CACHE && k !== PAGE_CACHE)
            .map(k => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

// ─── Fetch ───────────────────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  if (request.method !== 'GET') return;
  if (url.pathname.includes('/api/') || url.pathname.includes('/logout')) return;
  if (url.origin !== self.location.origin) return;

  if (isStaticAsset(url.pathname)) {
    // Stale-while-revalidate: serve cache instantly, refresh in background
    event.respondWith(staleWhileRevalidate(request, STATIC_CACHE));
    return;
  }

  if (url.pathname.startsWith('/ergon/')) {
    event.respondWith(networkFirstWithFallback(request));
    return;
  }
});

// ─── Strategies ──────────────────────────────────────────────────────────────
function isStaticAsset(pathname) {
  return /\.(css|js|woff2?|png|jpg|jpeg|svg|ico|webp)(\?.*)?$/.test(pathname);
}

async function staleWhileRevalidate(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);

  const fetchPromise = fetch(request).then(response => {
    if (response.ok) cache.put(request, response.clone());
    return response;
  }).catch(() => null);

  return cached || await fetchPromise || new Response('Asset unavailable', { status: 503 });
}

async function networkFirstWithFallback(request) {
  const cache = await caches.open(PAGE_CACHE);
  try {
    const response = await fetch(request);
    if (response.ok && response.headers.get('content-type')?.includes('text/html')) {
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await cache.match(request);
    if (cached) return cached;
    const offline = await caches.match('/ergon/offline.html');
    return offline || new Response('<h1>You are offline</h1>', {
      headers: { 'Content-Type': 'text/html' }
    });
  }
}
