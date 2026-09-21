// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
// ACTION: service worker de RETRAIT (kill switch), 2026-09-21
// SELF: remplacement complet d'un fichier existant, aucune generation deleguee possible
// RAISON: c'est le SEUL moyen de desinstaller le service worker chez les visiteurs qui l'ont deja
//
// Remplace l'ancien service worker de precache vite-pwa (Workbox, environ 37 Ko, quatre routes
// de cache et un ecouteur 'fetch') A LA MEME ADRESSE (/build/sw-source.js, portee site entier
// via l'en-tete Service-Worker-Allowed de public/.htaccess). C'est ce point qui decide de tout :
// un navigateur qui a deja installe l'ancien service worker verifie periodiquement, et sur ordre
// explicite depuis resources/js/pwa.js, si LE FICHIER A CETTE ADRESSE a change. Retirer
// l'enregistrement du HTML n'aurait rien change pour lui - seul un fichier different a la meme
// adresse declenche une mise a jour, qui remplace l'ancien service worker par celui-ci.
//
// Ne precache RIEN et ne pose AUCUN ecouteur 'fetch' : des l'activation, plus aucune requete
// n'est interceptee, meme avant que unregister() ait fini de s'executer plus bas.
//
// Historique, pour qui se demanderait pourquoi ce fichier existe : le service worker etait
// repute neutralise depuis la version 1.287.7. La mesure du 2026-09-21 a montre qu'il ne l'avait
// jamais ete - la neutralisation n'avait porte que sur public/sw.js et public/service-worker.js,
// deux fichiers que plus personne n'enregistrait, jamais sur celui que Vite enregistre
// reellement. Les correctifs v1.287.7 et v1.292.3 traitaient donc des consequences (CSS et JS
// perimes, images cassees) sans couper la cause.

// Point d'injection exige par vite-plugin-pwa en mode injectManifest : sans lui, le build
// echoue sur « Unable to find a place to inject the manifest ». Le resultat n'est JAMAIS lu -
// globPatterns vaut [] dans vite.config.js, la liste injectee est donc vide.
// Il est affecte a une propriete de self, et non laisse en expression isolee : une expression
// sans effet est supprimee par l'optimiseur avant que Workbox ne cherche le point d'injection,
// ce qui faisait echouer le build (mesure le 2026-09-21).
self.__manifestePrecacheIgnore = self.__WB_MANIFEST;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            // Purge UNIQUEMENT les caches de cet ex-service worker : les quatre noms qu'il
            // utilisait, plus le precache Workbox. Jamais 'laveille-authors-*', qui appartient
            // au service worker distinct des mini-sites auteur (/sw-authors.js, portee /@),
            // hors perimetre de ce retrait.
            const noms = await caches.keys();
            await Promise.all(
                noms
                    .filter((nom) => ['pages-cache', 'assets-cache', 'images-cache', 'api-cache'].includes(nom)
                        || nom.startsWith('workbox-precache'))
                    .map((nom) => caches.delete(nom))
            );

            // AUCUN client.navigate() : pas de rechargement force des onglets deja ouverts,
            // meme convention que celle deja en place dans public/sw.js.
            await self.registration.unregister();
        })()
    );
});
