'use strict';

const CACHE_NAME = 'anonymiseur-v1-45-0';
const SHELL_URLS = [
    '/outils/anonymiseur',
    '/assets/tools/anonymiseur/app.js',
    '/assets/tools/anonymiseur/styles.css',
    '/assets/tools/anonymiseur/enhancements.js',
    '/assets/tools/anonymiseur/enhancements-v145.js',
    '/assets/tools/anonymiseur/manifest.webmanifest'
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(SHELL_URLS).catch(() => null))
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((names) => Promise.all(
            names.filter((n) => n !== CACHE_NAME && n.startsWith('anonymiseur-'))
                .map((n) => caches.delete(n))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    const isShell = url.pathname === '/outils/anonymiseur'
        || url.pathname.startsWith('/assets/tools/anonymiseur/');
    if (isShell) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((resp) => {
                if (resp && resp.status === 200) {
                    const clone = resp.clone();
                    caches.open(CACHE_NAME).then((c) => c.put(request, clone).catch(() => null));
                }
                return resp;
            }).catch(() => caches.match('/outils/anonymiseur')))
        );
    }
});
