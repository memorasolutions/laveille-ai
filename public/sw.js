const CACHE_NAME = 'laveille-v2', OFFLINE_URL = '/offline';
self.addEventListener('install', e => e.waitUntil(caches.open(CACHE_NAME).then(c => c.add(OFFLINE_URL)).then(() => self.skipWaiting())));
self.addEventListener('activate', e => e.waitUntil(caches.keys().then(ks => Promise.all(ks.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))).then(() => self.clients.claim())));
self.addEventListener('fetch', e => {
  const u = new URL(e.request.url);
  if (e.request.method !== 'GET') return;
  if (/^\/(admin|api|webhooks|livewire|login|magic-link)(\/|$)/.test(u.pathname)) return;
  if (/\.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot)$/i.test(u.pathname)) {
    e.respondWith(caches.match(e.request).then(r => r || fetch(e.request).then(res => {
      const c = res.clone(); caches.open(CACHE_NAME).then(cache => cache.put(e.request, c)); return res;
    }).catch(() => caches.match(OFFLINE_URL))));
    return;
  }
  if (e.request.mode === 'navigate') {
    e.respondWith(fetch(e.request).then(res => {
      const c = res.clone(); caches.open(CACHE_NAME).then(cache => cache.put(e.request, c)); return res;
    }).catch(() => caches.match(e.request).then(r => r || caches.match(OFFLINE_URL))));
  }
});
