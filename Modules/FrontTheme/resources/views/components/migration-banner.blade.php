{{-- Bandeau migration recitcn.ca → laveille.ai (ajouté 2026-04-10, expire automatiquement via localStorage 365 jours) --}}
<div id="migrationBanner" style="display:none;">
    <style>
        #migrationBanner {
            position: sticky;
            top: 0;
            z-index: 9999;
            background: #0B7285;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transform: translateY(-100%);
            transition: transform .4s cubic-bezier(.4,0,.2,1);
        }
        #migrationBanner.is-visible { transform: translateY(0); }
        #migrationBanner.is-closing { transform: translateY(-100%); }
        #migrationBanner .mb-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: .75rem 3rem .75rem 1.25rem;
            position: relative;
            font-size: .95rem;
            line-height: 1.45;
            text-align: center;
        }
        #migrationBanner .mb-inner strong { color: #C3FAE8; }
        #migrationBanner .mb-close {
            position: absolute;
            right: .5rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.35rem;
            cursor: pointer;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background .2s;
            line-height: 1;
            padding: 0;
        }
        #migrationBanner .mb-close:hover { background: rgba(255,255,255,.15); }
        @media (max-width: 640px) {
            #migrationBanner .mb-inner { font-size: .8rem; padding: .6rem 2.5rem .6rem 1rem; }
            #migrationBanner .mb-close { font-size: 1.15rem; right: .25rem; }
        }
    </style>
    <div class="mb-inner">
        <span>
            <strong>recitcn.ca</strong> a déménagé ! Bienvenue sur <strong>laveille.ai</strong>
            — votre nouvelle plateforme de veille technologique avec plus d'outils et de ressources gratuites.
        </span>
        <button class="mb-close" id="migrationBannerClose" aria-label="Fermer">&times;</button>
    </div>
</div>
<script>
(function() {
    var KEY_DISMISS = 'migration_banner_dismissed';
    var KEY_VISITOR = 'recitcn_visitor';
    var DAYS = 365;
    function isDismissed() {
        try { var r = localStorage.getItem(KEY_DISMISS); return r && Date.now() < parseInt(r, 10); } catch(e) { return false; }
    }
    function dismiss() {
        try { localStorage.setItem(KEY_DISMISS, (Date.now() + DAYS * 864e5).toString()); } catch(e) {}
    }
    function isRecitcnVisitor() {
        try {
            if ((document.referrer || '').indexOf('recitcn.ca') !== -1) { localStorage.setItem(KEY_VISITOR, '1'); return true; }
            if (new URLSearchParams(location.search).get('from') === 'recitcn') { localStorage.setItem(KEY_VISITOR, '1'); return true; }
            return localStorage.getItem(KEY_VISITOR) === '1';
        } catch(e) { return false; }
    }
    if (isDismissed() || !isRecitcnVisitor()) return;
    var b = document.getElementById('migrationBanner');
    b.style.display = 'block';
    requestAnimationFrame(function() { requestAnimationFrame(function() { b.classList.add('is-visible'); }); });
    document.getElementById('migrationBannerClose').addEventListener('click', function() {
        b.classList.remove('is-visible');
        b.classList.add('is-closing');
        b.addEventListener('transitionend', function h() { b.removeEventListener('transitionend', h); b.style.display = 'none'; });
        dismiss();
    });
})();
</script>
