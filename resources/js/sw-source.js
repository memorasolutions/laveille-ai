// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
// Service Worker source pour vite-plugin-pwa (injectManifest)
import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute, setCatchHandler, setDefaultHandler } from 'workbox-routing';
import { NetworkFirst, CacheFirst, StaleWhileRevalidate, NetworkOnly } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { BackgroundSyncPlugin } from 'workbox-background-sync';
import { skipWaiting, clientsClaim } from 'workbox-core';

// Activation immédiate
skipWaiting();
clientsClaim();

// Precaching automatique (injecté par vite-plugin-pwa au build)
precacheAndRoute(self.__WB_MANIFEST);

// --- Exclusions prioritaires (passthrough réseau pur, AUCUNE interception) ---
// Le scope du SW est élargi à "/" (site-wide) - sans ces exclusions, le SW capte
// aussi /admin/* et les appels AJAX Livewire (POST /livewire/update, utilisés par
// tout composant interactif dont l'admin), les enveloppant dans le background sync
// POST ci-dessous -> latence perçue à chaque clic (ex. sélection multiple sur
// /admin/users). Ces 3 routes DOIVENT rester AVANT les routes de cache (Workbox
// utilise la première route qui matche).
//
// IMPORTANT : Workbox classe les routes par méthode HTTP (registerRoute sans 3e
// argument n'enregistre la route que pour GET). Sans le enregistrement explicite
// ci-dessous pour POST, une requête POST vers /admin, /livewire/ ou un domaine
// externe ne matchait AUCUNE de ces 3 exclusions et tombait dans la route
// générique "POST -> background sync" plus bas, qui la mettait en file d'attente
// pour rejeu automatique en cas d'échec réseau (jamais l'intention voulue pour
// ces requêtes). On enregistre donc chaque exclusion pour GET et POST.
const isAdminRequest = ({ url }) => url.pathname.startsWith('/admin');
const isLivewireRequest = ({ url }) => url.pathname.startsWith('/livewire/');
const isCrossOriginRequest = ({ url }) => url.origin !== self.location.origin;

for (const method of ['GET', 'POST']) {
    registerRoute(isAdminRequest, new NetworkOnly(), method);
    registerRoute(isLivewireRequest, new NetworkOnly(), method);
    registerRoute(isCrossOriginRequest, new NetworkOnly(), method);
}

// --- Stratégies de cache runtime ---

// Pages HTML - Network First (toujours chercher le réseau d'abord)
registerRoute(
    ({ request }) => request.mode === 'navigate',
    new NetworkFirst({
        cacheName: 'pages-cache',
        plugins: [
            new ExpirationPlugin({ maxEntries: 50, maxAgeSeconds: 24 * 60 * 60 }),
        ],
    })
);

// Assets compilés /build/ - Cache First (immutables après build)
registerRoute(
    ({ url }) => url.pathname.startsWith('/build/'),
    new CacheFirst({
        cacheName: 'assets-cache',
        plugins: [
            new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 30 * 24 * 60 * 60 }),
        ],
    })
);

// Images - Cache First
registerRoute(
    ({ request }) => request.destination === 'image',
    new CacheFirst({
        cacheName: 'images-cache',
        plugins: [
            new ExpirationPlugin({ maxEntries: 60, maxAgeSeconds: 7 * 24 * 60 * 60 }),
        ],
    })
);

// Appels API - Stale While Revalidate
registerRoute(
    ({ url }) => url.pathname.startsWith('/api/'),
    new StaleWhileRevalidate({
        cacheName: 'api-cache',
        plugins: [
            new ExpirationPlugin({ maxEntries: 50, maxAgeSeconds: 5 * 60 }),
        ],
    })
);

// Page hors ligne en fallback
setCatchHandler(async ({ event }) => {
    if (event.request.destination === 'document') {
        return caches.match('/offline') || Response.error();
    }
    return Response.error();
});

// --- Background Sync pour les formulaires POST hors ligne ---
const bgSyncPlugin = new BackgroundSyncPlugin('offline-forms', {
    maxRetentionTime: 24 * 60, // 24 heures en minutes
});

registerRoute(
    ({ request }) => request.method === 'POST',
    new NetworkOnly({ plugins: [bgSyncPlugin] }),
    'POST'
);

// --- Web Push Notifications ---
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();
    const options = {
        body: data.body || '',
        icon: data.icon || '/icons/icon-192x192.png',
        badge: '/icons/icon-192x192.png',
        data: { url: data.data?.url || '/' },
        actions: data.actions || [],
    };

    event.waitUntil(self.registration.showNotification(data.title || 'Notification', options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});

// Handler par défaut - Network First
setDefaultHandler(new NetworkFirst());
