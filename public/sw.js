// Service worker v4 — nettoyage SILENCIEUX des caches
// Migration sans rechargement : vide ses caches puis se dé-enregistre.
// AUCUN client.navigate() (évitait un rechargement forcé de tous les onglets).
const CACHE_VERSION = 'laveille-v4-cleanup';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((names) => Promise.all(names.map((name) => caches.delete(name))))
            .then(() => self.registration.unregister())
    );
});
