// Service Worker minimal — cache-first /@slug + posts, network-first dynamic
const CACHE_NAME = 'laveille-authors-v1';
const STATIC_CACHE = ['/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_CACHE))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET + non-HTTPS
    if (event.request.method !== 'GET' || !url.protocol.startsWith('http')) {
        return;
    }

    // Cache-first pour mini-sites + posts auteurs
    if (url.pathname.startsWith('/@') || url.pathname === '/manifest.webmanifest') {
        event.respondWith(
            caches.match(event.request).then((cached) =>
                cached ||
                fetch(event.request).then((response) => {
                    if (response.ok) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    }
                    return response;
                }).catch(() => cached)
            )
        );
    }
});
