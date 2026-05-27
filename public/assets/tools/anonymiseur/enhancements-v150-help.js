'use strict';
(function () {
    function open(modal) { if (modal) modal.classList.add('active'); }
    function close(modal) { if (modal) modal.classList.remove('active'); }

    function activateHelpTab(tabBtn) {
        const tabs = document.querySelectorAll('.lv-help-tab');
        const panels = document.querySelectorAll('.lv-help-panel');
        const target = tabBtn.getAttribute('data-help-tab');
        tabs.forEach(t => {
            const isActive = t === tabBtn;
            t.classList.toggle('is-active', isActive);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            t.setAttribute('tabindex', isActive ? '0' : '-1');
        });
        panels.forEach(p => {
            const isMatch = p.id === 'lvhelp-panel-' + target;
            p.classList.toggle('is-active', isMatch);
            if (isMatch) p.removeAttribute('hidden');
            else p.setAttribute('hidden', '');
        });
        try { tabBtn.focus(); } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('maskModeHelpModal');
        const btn = document.getElementById('btnMaskModeHelp');
        const closeX = document.getElementById('closeMaskHelpModal');
        const closeFooter = document.getElementById('closeMaskHelpModalFooter');
        if (!modal || !btn) return;

        btn.addEventListener('click', () => open(modal));
        closeX && closeX.addEventListener('click', () => close(modal));
        closeFooter && closeFooter.addEventListener('click', () => close(modal));
        modal.addEventListener('click', (e) => { if (e.target === modal) close(modal); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) close(modal);
        });

        // Tabs : click + arrow keys (ARIA pattern WAI tablist)
        const tabs = Array.from(document.querySelectorAll('.lv-help-tab'));
        tabs.forEach((t, idx) => {
            t.addEventListener('click', () => activateHelpTab(t));
            t.addEventListener('keydown', (e) => {
                let next = null;
                if (e.key === 'ArrowRight') next = tabs[(idx + 1) % tabs.length];
                else if (e.key === 'ArrowLeft') next = tabs[(idx - 1 + tabs.length) % tabs.length];
                else if (e.key === 'Home') next = tabs[0];
                else if (e.key === 'End') next = tabs[tabs.length - 1];
                if (next) { e.preventDefault(); activateHelpTab(next); }
            });
        });

        // Sync l'onglet ouvert avec le mode actuellement sélectionné dans #maskMode
        btn.addEventListener('click', () => {
            const select = document.getElementById('maskMode');
            if (!select) return;
            const tabForCurrent = document.querySelector('.lv-help-tab[data-help-tab="' + select.value + '"]');
            if (tabForCurrent) activateHelpTab(tabForCurrent);
        });
    });
})();
