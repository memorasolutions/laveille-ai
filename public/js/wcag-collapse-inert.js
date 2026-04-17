// Toggle [inert] on Bootstrap .collapse elements so hidden panels leave the tab order (WCAG 2.1.1)
(() => {
    const sync = el => el.classList.contains('show') ? el.removeAttribute('inert') : el.setAttribute('inert', '');
    const observe = el => new MutationObserver(() => sync(el)).observe(el, { attributes: true, attributeFilter: ['class'] });
    const init = el => { sync(el); observe(el); };
    const boot = () => {
        document.querySelectorAll('.collapse').forEach(init);
        new MutationObserver(muts => muts.forEach(m => {
            m.addedNodes.forEach(n => {
                if (n.nodeType !== 1) return;
                if (n.classList?.contains('collapse')) init(n);
                n.querySelectorAll?.('.collapse').forEach(init);
            });
        })).observe(document.body, { childList: true, subtree: true });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
