@props(['articleSelector' => 'article.lv-post-body, article[role="article"], main'])

<div role="progressbar"
     aria-valuemin="0"
     aria-valuemax="100"
     aria-valuenow="0"
     aria-label="Progression de lecture"
     class="lv-reading-progress"
     style="position:fixed; top:0; left:0; width:100%; height:3px; z-index:9999; background:transparent; pointer-events:none;">
    <div class="lv-reading-progress-fill"
         style="background:var(--c-accent,#9A2A06); transform-origin:left center; transform:scaleX(0); height:100%; will-change:transform; transition:transform 50ms linear;"
         aria-hidden="true"></div>
</div>

<script>
(function () {
    'use strict';
    if (typeof window === 'undefined') return;
    function init() {
        var progress = document.querySelector('.lv-reading-progress');
        var fill = document.querySelector('.lv-reading-progress-fill');
        if (!progress || !fill) return;
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            progress.style.display = 'none';
            return;
        }
        var selector = @json($articleSelector);
        var article = document.querySelector(selector);
        if (!article) return;
        var ticking = false;
        function update() {
            var rect = article.getBoundingClientRect();
            var articleTop = rect.top;
            var articleHeight = rect.height;
            var winH = window.innerHeight;
            var scrolled = -articleTop;
            var total = Math.max(1, articleHeight - winH);
            var ratio = Math.min(1, Math.max(0, scrolled / total));
            fill.style.transform = 'scaleX(' + ratio + ')';
            progress.setAttribute('aria-valuenow', Math.round(ratio * 100));
            ticking = false;
        }
        window.addEventListener('scroll', function () {
            if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
        }, { passive: true });
        window.addEventListener('resize', function () {
            if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
        }, { passive: true });
        update();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
