'use strict';

// 2026-06-01 : CACHE_NAME bumpé (était figé v1-45-0 → servait éternellement l'ancien CSS/JS en
// cache-first, masquant tous les déploiements ; cause du « popup à gauche » persistant côté users
// ayant le SW installé). Désormais : network-first pour le CSS/JS/HTML (toujours la dernière version,
// fallback cache hors-ligne). Bumper ce nom à chaque changement d'assets anonymiseur.
const CACHE_NAME = 'anonymiseur-v1-63-20';
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
        // Network-first : on sert TOUJOURS la dernière version si le réseau répond (évite de figer
        // un ancien CSS/JS), et on retombe sur le cache uniquement hors-ligne (PWA préservée).
        event.respondWith(
            fetch(request).then((resp) => {
                if (resp && resp.status === 200) {
                    const clone = resp.clone();
                    caches.open(CACHE_NAME).then((c) => c.put(request, clone).catch(() => null));
                }
                return resp;
            }).catch(() => caches.match(request).then((cached) => cached || caches.match('/outils/anonymiseur')))
        );
    }
});
