'use strict';

/**
 * Anonymiseur Settings v1.47.14 — réglages « Standard » appliqués par défaut + bouton Réinitialiser.
 * Remplace l'ancien système de 3 cartes presets (supprimé) : le panneau de réglages est
 * désormais l'unique zone, toujours visible, pré-réglée sur les recommandations IA.
 */
(function () {
    const DEFAULTS = {
        maskMode: 'pseudo',
        confidence: 0.6,
        encryption: false
    };

    function resetToRecommended() {
        const maskModeSelect = document.getElementById('maskMode');
        const confidenceInput = document.getElementById('confidenceThreshold');
        const encryptionCheckbox = document.getElementById('encryptionEnabled');

        if (maskModeSelect) {
            maskModeSelect.value = DEFAULTS.maskMode;
            maskModeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (confidenceInput) {
            confidenceInput.value = String(DEFAULTS.confidence);
            confidenceInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        if (encryptionCheckbox && encryptionCheckbox.checked) {
            encryptionCheckbox.checked = false;
            encryptionCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (typeof window.showToast === 'function') {
            window.showToast('Réglages réinitialisés aux recommandés.', 'info');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const resetButton = document.getElementById('resetRecommended');
        if (resetButton) {
            resetButton.addEventListener('click', resetToRecommended);
        }
    });

    window.AnonymiseurSettings = {
        resetToRecommended: resetToRecommended,
        DEFAULTS: DEFAULTS
    };
})();
