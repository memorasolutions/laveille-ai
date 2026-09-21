// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
// ACTION: RETRAIT du service worker PWA, 2026-09-21
// SELF: remplacement complet d'un fichier existant
// RAISON: ce fichier re-enregistrait le service worker de precache a CHAQUE page, y compris
//         apres le correctif v1.287.7 qui croyait l'avoir neutralise. C'est la cause commune
//         aux deux defauts mesures separement - images cassees (corrigees en v1.292.3) et
//         ressources prechargees perdues. Les deux venaient du meme service worker toujours
//         actif, jamais du contenu des pages.
//
// Ce fichier ne pose plus AUCUN nouvel enregistrement. Il fait seulement disparaitre celui qui
// existe deja chez les visiteurs qui l'ont.
//
// Un service worker deja installe RESTE actif tant qu'il n'est pas desenregistre explicitement :
// retirer l'appel a registerSW() ne suffit pas pour ceux qui l'ont deja. La desinstallation
// reelle se fait par le fichier qu'il pointe encore - resources/js/sw-source.js sert desormais,
// a la meme adresse, un service worker de retrait qui se desenregistre et purge ses caches des
// son activation. Le bloc ci-dessous force la detection immediate de cette mise a jour plutot
// que d'attendre la verification automatique du navigateur, qui peut prendre jusqu'a 24 heures.
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(registrations => {
        for (const registration of registrations) {
            try {
                // /sw-authors.js (mini-sites /@slug, portee /@) est un service worker DISTINCT
                // et VOULU, hors perimetre de ce retrait. Seule une inscription mal portee a la
                // racine - incident deja documente - est nettoyee ici.
                if (
                    registration.scope === window.location.origin + '/' &&
                    registration.active &&
                    registration.active.scriptURL.endsWith('/sw-authors.js')
                ) {
                    registration.unregister();
                    continue;
                }

                // Toute autre inscription active a la racine est l'ex-service worker vite-pwa,
                // ou un residu anterieur. On force une verification immediate : le fichier
                // qu'elle pointe est maintenant le service worker de retrait, qui se
                // desenregistre et purge ses propres caches de lui-meme une fois active.
                registration.update().catch(() => {});
            } catch (e) {
                // Une inscription illisible ne doit jamais casser la page : on l'ignore.
            }
        }
    }).catch(() => {});
}

// Invite d'installation native : cablage conserve tel quel. Sans service worker actif portant un
// ecouteur 'fetch', Chrome ne considere plus le site comme installable et cet evenement ne se
// declenchera simplement plus. C'est une CONSEQUENCE acceptee du retrait, pas un defaut - et le
// cablage reste en place pour le jour ou l'installabilite serait reintroduite.
window.deferredPwaPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    window.deferredPwaPrompt = e;
    window.dispatchEvent(new CustomEvent('pwa-install-available'));
});

window.addEventListener('appinstalled', () => {
    window.deferredPwaPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa-installed'));
});

window.pwaInstall = async () => {
    if (!window.deferredPwaPrompt) return false;
    window.deferredPwaPrompt.prompt();
    const { outcome } = await window.deferredPwaPrompt.userChoice;
    window.deferredPwaPrompt = null;
    return outcome === 'accepted';
};

window.pwaIsInstalled = () =>
    window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
