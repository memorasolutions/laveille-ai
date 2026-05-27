'use strict';
(function () {
    function open(modal) { if (modal) modal.classList.add('active'); }
    function close(modal) { if (modal) modal.classList.remove('active'); }
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
    });
})();
