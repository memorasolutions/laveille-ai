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
// LES FEUILLES ET SCRIPTS DU SITE NE PASSENT PLUS PAR LE CACHE DU SERVICE WORKER (#2590).
// Mesuré en production le 2026-09-15 : le cache runtime contenait QUATRE versions de
// /css/charte.css en même temps, dont une d'avant le correctif. La page appliquait donc une
// feuille périmée alors que le réseau servait la bonne - vérifié par trois voies indépendantes
// (curl, fetch no-store, interception réseau) qui renvoyaient toutes le fichier à jour.
//
// C'est ce qui a fait échouer DEUX correctifs de typographie de suite (v1.287.3 et v1.287.6) :
// le CSS était juste et bien déployé, il n'atteignait simplement pas le navigateur. Et rien ne
// pouvait le révéler côté serveur, puisque le serveur, lui, répondait correctement.
//
// Ces fichiers portent déjà un cache-bust `?v=` dérivé de la version ET un `Cache-Control`
// de 30 jours : le cache HTTP du navigateur assure la relecture hors ligne. Une couche de cache
// applicative par-dessus n'ajoute donc rien - elle ne fait que retenir des versions mortes,
// sans limite d'ancienneté et sans aucun signal. La règle vaut pour TOUT visiteur déjà venu,
// pas seulement pour le menu qui a servi à découvrir le défaut.
const isSiteStylesheetOrScript = ({ url }) =>
    url.pathname.startsWith('/css/') || url.pathname.startsWith('/js/');

const isAdminRequest = ({ url }) => url.pathname.startsWith('/admin');
const isLivewireRequest = ({ url }) => url.pathname.startsWith('/livewire/');
const isCrossOriginRequest = ({ url }) => url.origin !== self.location.origin;

for (const method of ['GET', 'POST']) {
    registerRoute(isAdminRequest, new NetworkOnly(), method);
    registerRoute(isLivewireRequest, new NetworkOnly(), method);
    registerRoute(isCrossOriginRequest, new NetworkOnly(), method);
    registerRoute(isSiteStylesheetOrScript, new NetworkOnly(), method);
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

    // 2026-09-21 : une IMAGE reçoit une DERNIÈRE chance par le réseau avant d'abandonner.
    // Signalé par le fondateur, console d'un article : « The FetchEvent for
    // .../images/bd/ia-emplois-2030/alarme.jpg resulted in a network error response ». Ce
    // message est produit par le Response.error() ci-dessous. Or les deux images visées
    // répondaient parfaitement en direct (200, image/jpeg, 138 352 et 145 047 octets) : c'est
    // donc la stratégie CacheFirst qui avait échoué, pas le serveur.
    // Le défaut est silencieux PAR CONSTRUCTION : le lecteur voit une image cassée, et le
    // serveur ne voit jamais passer la requête - il n'y a donc rien à trouver dans les
    // journaux. Sans ce repli, un échec de CACHE devient un échec DÉFINITIF de l'image, alors
    // qu'un simple passage réseau l'aurait servie.
    // Aucune régression possible : on ajoute une tentative là où il n'y avait qu'un abandon.
    // Si le réseau est réellement injoignable, on retombe sur le même Response.error() qu'avant.
    if (event.request.destination === 'image') {
        try {
            const reponse = await fetch(event.request);
            if (reponse && reponse.ok) {
                return reponse;
            }
        } catch (e) {
            // réseau réellement injoignable : on tombe dans le Response.error() ci-dessous
        }
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

// PURGE DU PASSIF DÉJÀ STOCKÉ CHEZ LES VISITEURS (#2590).
// Fermer la source ne vide pas ce qui est déjà en cache : sans cette purge, un visiteur déjà
// venu garderait ses feuilles périmées jusqu'à ce qu'une expiration les retire - or le handler
// par défaut n'en avait aucune, donc jamais. On retire uniquement les entrées /css/ et /js/ des
// caches du service worker ; aucune autre entrée n'est touchée, et aucune donnée utilisateur ne
// vit dans ces caches (ce sont des fichiers statiques publics, rechargés au prochain accès).
self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            for (const nom of await caches.keys()) {
                const cache = await caches.open(nom);

                for (const requete of await cache.keys()) {
                    const chemin = new URL(requete.url).pathname;

                    if (chemin.startsWith('/css/') || chemin.startsWith('/js/')) {
                        await cache.delete(requete);
                    }
                }
            }
        })()
    );
});

// Handler par défaut - Network First
setDefaultHandler(new NetworkFirst());
