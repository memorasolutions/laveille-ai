// Service Worker des mini-sites auteur (/@slug).
//
// #2375 (2026-09-08) - la version v1 servait les documents HTML en CACHE D'ABORD
// (`cached || fetch(...)`), sans aucune revalidation ni durée de vie : une page mise en cache
// une seule fois était resservie INDÉFINIMENT. Un visiteur déjà venu ne voyait donc jamais
// aucune correction apportée à une fiche auteur, et rien dans le code ne pouvait le rattraper.
//
// Depuis : les documents passent en RÉSEAU D'ABORD. Le cache ne sert plus que de filet hors
// ligne, jamais de source par défaut. Le nom du cache change (v1 -> v2) pour que l'étape
// `activate` purge ce que les visiteurs actuels portent déjà.
const CACHE_PREFIXE = 'laveille-authors-';
const CACHE_NAME = CACHE_PREFIXE + 'v2';
const STATIC_CACHE = ['/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_CACHE))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        // Purge BORNÉE à nos propres caches (revue adversariale du 2026-09-08). La version
        // précédente supprimait TOUT cache de l'origine sauf le sien : sur un domaine qui porte
        // plusieurs enregistrements de service worker, elle aurait effacé les caches d'une autre
        // fonctionnalité. Un service worker n'a aucun titre à détruire ce qu'il n'a pas écrit.
        caches.keys().then((keys) =>
            Promise.all(
                keys.filter((k) => k.startsWith(CACHE_PREFIXE) && k !== CACHE_NAME)
                    .map((k) => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

// Prolonge la vie du service worker le temps d'une écriture. Sans cela, le navigateur peut
// l'arrêter dès la réponse rendue et l'écriture n'aboutit jamais. L'appel est protégé : quand la
// réponse a DÉJÀ été rendue (cas du manifeste servi depuis le cache), l'événement n'est plus en
// vol et `waitUntil` lève - l'écriture reste tentée, elle n'est simplement plus garantie.
function prolonger(event, promesse) {
    try {
        event.waitUntil(promesse);
    } catch (e) {
        /* événement clos : rien à prolonger, la promesse suit son cours */
    }
}

// Réseau d'abord : on sert la réponse fraîche et on rafraîchit le cache au passage.
// Le cache n'est consulté QUE si le réseau a échoué (hors ligne, coupure).
function reseauDAbord(event) {
    const request = event.request;
    return fetch(request)
        .then((response) => {
            // `redirected` est écarté volontairement : rejouer depuis un cache une réponse qui a
            // suivi une redirection lève « Response served by service worker has redirected flag
            // set », et la page tombe précisément quand on comptait sur le filet hors ligne.
            if (response.ok && !response.redirected) {
                const copie = response.clone();
                // Le `.catch()` n'est pas décoratif : un quota disque dépassé rejetterait ici une
                // promesse que plus personne ne consomme, donc un rejet non capturé.
                prolonger(event, caches.open(CACHE_NAME)
                    .then((cache) => cache.put(request, copie))
                    .catch(() => {}));
            }
            return response;
        })
        .catch(() => caches.match(request).then((cached) => cached || Promise.reject(new Error('hors ligne'))));
}

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET + non-HTTPS
    if (event.request.method !== 'GET' || !url.protocol.startsWith('http')) {
        return;
    }

    // Une page contrôlée envoie aussi ses sous-ressources d'AUTRES domaines dans ce gestionnaire :
    // sans ce filtre, une adresse tierce dont le chemin commence par /@ passerait par nos règles.
    if (url.origin !== self.location.origin) {
        return;
    }

    // Mini-sites et publications d'auteurs. On ne vise QUE les documents : `/@...` désignait aussi
    // les images, scripts et appels de ces pages, qui n'ont pas le même besoin de fraîcheur.
    // Rappel mesuré le 2026-09-08 : la page répond `max-age=300`, donc « réseau d'abord » signifie
    // « au plus 5 minutes de retard » (la politique voulue du site), non plus « indéfiniment ».
    if (url.pathname.startsWith('/@') && event.request.mode === 'navigate') {
        event.respondWith(reseauDAbord(event));
        return;
    }

    // Le manifeste est un actif quasi immuable : on le sert depuis le cache pour la vitesse,
    // mais on le rafraîchit en arrière-plan pour qu'une mise à jour finisse toujours par arriver.
    // Le `.catch()` n'est PAS décoratif : quand le cache répond, la promesse de rafraîchissement
    // n'est consommée par personne, et son rejet hors ligne deviendrait un rejet non capturé
    // dans le service worker.
    if (url.pathname === '/manifest.webmanifest') {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                const reseau = reseauDAbord(event).catch(() => cached);
                return cached || reseau;
            })
        );
    }
});
