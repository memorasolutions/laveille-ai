'use strict';
(function () {
    const STORAGE_KEY = 'anonymiseur_trust_accepted_v1';
    function isAccepted() {
        try { return localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) { return false; }
    }
    function setAccepted(v) {
        try { localStorage.setItem(STORAGE_KEY, v ? '1' : '0'); } catch (e) {}
    }
    function setExpanded(toggle, banner, expanded) {
        if (!toggle || !banner) return;
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        banner.classList.toggle('is-collapsed', !expanded);
    }
    document.addEventListener('DOMContentLoaded', () => {
        const banner = document.getElementById('infoBanner');
        const toggle = document.getElementById('lvTrustToggle');
        const accept = document.getElementById('lvTrustAccept');
        if (!banner || !toggle) return;
        setExpanded(toggle, banner, !isAccepted());
        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            setExpanded(toggle, banner, !expanded);
        });
        if (accept) {
            accept.addEventListener('click', () => {
                setAccepted(true);
                setExpanded(toggle, banner, false);
                if (typeof window.showToast === 'function') {
                    window.showToast('Garantie confidentialité comprise. Cliquez 🛡️ pour réafficher au besoin.', 'success');
                }
            });
        }
    });
    window.AnonymiseurTrust = { isAccepted, setAccepted };
})();
