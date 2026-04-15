// Service worker v3 — nettoyage forcé des caches
// Les visiteurs avec l'ancien SW verront celui-ci se mettre à jour,
// supprimer tous les caches et se dé-enregistrer.
const CACHE_VERSION = 'laveille-v3-cleanup';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((names) => Promise.all(names.map((name) => caches.delete(name))))
            .then(() => self.registration.unregister())
            .then(() => self.clients.matchAll())
            .then((clients) => clients.forEach((client) => client.navigate(client.url)))
    );
});
