/**
 * enhancements-v152-settings-accordion.js — Sprint S131 #11
 * Panneau « Réglages recommandés » en accordéon, fermé par défaut (progressive disclosure).
 * Le bandeau de réassurance sert d'en-tête cliquable ; le corps (champs + reset) s'ouvre
 * uniquement si l'utilisateur veut personnaliser. Les smart defaults (Standard) restent
 * appliqués que le panneau soit ouvert ou fermé. Classic script, IIFE, sans dépendance.
 */
(function () {
    function initAccordion() {
        const toggle = document.getElementById('anonymiseurSettingsToggle');
        const body = document.getElementById('anonymiseur-settings-body');
        if (!toggle || !body) return;

        toggle.addEventListener('click', function () {
            const isOpen = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!isOpen));
            if (!isOpen) {
                body.removeAttribute('hidden');
            } else {
                body.setAttribute('hidden', '');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAccordion);
    } else {
        initAccordion();
    }
})();
