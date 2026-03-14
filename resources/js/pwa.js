// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
// Enregistrement du Service Worker PWA et gestion des prompts
import { registerSW } from 'virtual:pwa-register';

// Variable globale pour le prompt d'installation
window.deferredPwaPrompt = null;

// Enregistrement du SW avec gestion des mises à jour
const updateSW = registerSW({
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
