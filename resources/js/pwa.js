// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
// Enregistrement du Service Worker PWA et gestion des prompts
import { registerSW } from 'virtual:pwa-register';

// Variable globale pour le prompt d'installation
window.deferredPwaPrompt = null;

// Le back-office (/admin) n'a pas besoin du précache PWA : on N'ENREGISTRE PAS
// le SW sur ces routes (évite la tempête SW + précache + tour Driver.js au 1er login).
const isAdminRoute = /^\/admin(\/|$)/.test(window.location.pathname);

// Nettoyage des anciennes registrations sw-authors.js mal scopées à la racine (incident de scope)
if (!isAdminRoute && 'serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(registrations => {
        for (const registration of registrations) {
            try {
                if (
                    registration.scope === window.location.origin + '/' &&
                    registration.active &&
                    registration.active.scriptURL.endsWith('/sw-authors.js')
                ) {
                    registration.unregister();
                }
            } catch (e) {
                // ignore errors silently
            }
        }
    }).catch(() => {});
}

// Enregistrement du SW avec gestion des mises à jour.
// registerType:'prompt' => AUCUN rechargement automatique ; onNeedRefresh ne fait
// qu'émettre un événement, le reload n'a lieu que via window.pwaUpdate() (action user).
const updateSW = isAdminRoute
    ? () => {}
    : registerSW({
        onNeedRefresh() {
            window.dispatchEvent(new CustomEvent('pwa-update-available'));
        },
        onOfflineReady() {
            window.dispatchEvent(new CustomEvent('pwa-offline-ready'));
        },
        onRegisteredSW(swUrl, registration) {
            // Vérifier les mises à jour toutes les heures
            if (registration) {
                setInterval(() => registration.update(), 60 * 60 * 1000);
            }
        },
    });

// Capture du prompt d'installation natif
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    window.deferredPwaPrompt = e;
    window.dispatchEvent(new CustomEvent('pwa-install-available'));
});

// Nettoyage après installation
window.addEventListener('appinstalled', () => {
    window.deferredPwaPrompt = null;
    window.dispatchEvent(new CustomEvent('pwa-installed'));
});

// Fonctions exposées globalement pour les composants Blade/Alpine
window.pwaUpdate = () => updateSW(true);
window.pwaInstall = async () => {
    if (!window.deferredPwaPrompt) return false;
    window.deferredPwaPrompt.prompt();
    const { outcome } = await window.deferredPwaPrompt.userChoice;
    window.deferredPwaPrompt = null;
    return outcome === 'accepted';
};
window.pwaIsInstalled = () =>
    window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
