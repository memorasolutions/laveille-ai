'use strict';
/**
 * enhancements-v149-trust.js — Sprint S131 #16 (simplifié).
 * Le bandeau « 100 % local » est désormais rendu via le composant officiel
 * <x-core::accordion id="anonymTrust"> (qui gère l'ouverture/fermeture au clic).
 * Ce module ne garde QUE la persistance « J'ai compris, ne plus afficher » :
 * visiteur récurrent ayant accepté → bandeau replié au chargement ; le bouton
 * « J'ai compris » mémorise le choix + replie + toast.
 */
(function () {
    const STORAGE_KEY = 'anonymiseur_trust_accepted_v1';
    function isAccepted() {
        try { return localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) { return false; }
    }
    function setAccepted(v) {
        try { localStorage.setItem(STORAGE_KEY, v ? '1' : '0'); } catch (e) {}
    }
    function setExpanded(expanded) {
        const trigger = document.getElementById('anonymTrust-trigger');
        const panel = document.getElementById('anonymTrust-panel');
        if (!trigger || !panel) return;
        trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        if (expanded) { panel.removeAttribute('hidden'); } else { panel.setAttribute('hidden', ''); }
    }
    document.addEventListener('DOMContentLoaded', () => {
        if (isAccepted()) setExpanded(false);
        const accept = document.getElementById('lvTrustAccept');
        if (accept) {
            accept.addEventListener('click', () => {
                setAccepted(true);
                setExpanded(false);
                if (typeof window.showToast === 'function') {
                    window.showToast('Garantie confidentialité comprise. Cliquez l\'entête pour réafficher au besoin.', 'success');
                }
            });
        }
    });
    window.AnonymiseurTrust = { isAccepted, setAccepted };
})();
