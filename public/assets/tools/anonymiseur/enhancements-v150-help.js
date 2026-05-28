'use strict';

/**
 * Anonymiseur v1.47.11 — onglets dans la modal d'aide officielle <x-core::help-modal>.
 * Le body HTML (window.HELP_CONTENT['anonym-modes']) est injecté via x-html quand la
 * modal s'ouvre, donc les .lv-help-tab n'existent pas au DOMContentLoaded → on utilise
 * l'event delegation globale sur document (clic + clavier ArrowLeft/Right/Home/End).
 */
(function () {
    function activateTab(tab) {
        const container = tab.closest('.ct-modal-content, .ct-modal-body, body');
        if (!container) return;
        const tabs = container.querySelectorAll('.lv-help-tab');
        const panels = container.querySelectorAll('.lv-help-panel');
        const target = tab.getAttribute('data-help-tab');
        tabs.forEach(t => {
            const active = t === tab;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
            t.setAttribute('tabindex', active ? '0' : '-1');
        });
        panels.forEach(p => {
            const match = p.getAttribute('data-help-panel') === target;
            p.classList.toggle('is-active', match);
            if (match) p.removeAttribute('hidden');
            else p.setAttribute('hidden', '');
        });
        try { tab.focus(); } catch (e) {}
    }

    // Delegation clic
    document.addEventListener('click', (e) => {
        const tab = e.target.closest('.lv-help-tab');
        if (tab) { e.preventDefault(); activateTab(tab); }
    });

    // Delegation clavier (WAI-ARIA tablist)
    document.addEventListener('keydown', (e) => {
        const tab = e.target.closest && e.target.closest('.lv-help-tab');
        if (!tab) return;
        const all = Array.from(tab.parentElement.querySelectorAll('.lv-help-tab'));
        const idx = all.indexOf(tab);
        let next = null;
        if (e.key === 'ArrowRight') next = all[(idx + 1) % all.length];
        else if (e.key === 'ArrowLeft') next = all[(idx - 1 + all.length) % all.length];
        else if (e.key === 'Home') next = all[0];
        else if (e.key === 'End') next = all[all.length - 1];
        if (next) { e.preventDefault(); activateTab(next); }
    });
})();
