// Service worker désactivé — nettoyage des caches existants
// Les visiteurs qui ont l'ancien SW verront celui-ci se mettre à jour automatiquement
// et il supprimera tous les caches avant de se dé-enregistrer.

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
