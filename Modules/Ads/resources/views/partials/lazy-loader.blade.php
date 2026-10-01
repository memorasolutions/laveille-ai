{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Chargement différé des unités AdSense (anti-CLS, Core Web Vitals). À inclure UNE seule fois,
     en pied de page. N'émet rien s'il n'y a aucun emplacement AdSense différé sur la page. --}}
<script>
(function () {
  'use strict';
  if (window.__lvAdsLazyInit) { return; }
  window.__lvAdsLazyInit = true;

  function init() {
    var nodes = document.querySelectorAll('.lv-adsense[data-lv-lazy]');
    if (!nodes.length) { return; }

    function push(el) {
      if (el.getAttribute('data-lv-pushed')) { return; }
      el.setAttribute('data-lv-pushed', '1');
      try {
        // Tolérant : si adsbygoogle.js n'est pas encore chargé (consentement en attente),
        // le tableau se met en file et sera traité au chargement.
        (window.adsbygoogle = window.adsbygoogle || []).push({});
      } catch (e) { /* une unité en échec ne doit jamais casser la page */ }
    }

    if (!('IntersectionObserver' in window)) {
      Array.prototype.forEach.call(nodes, push);
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          io.unobserve(entry.target);
          push(entry.target);
        }
      });
    }, { rootMargin: '400px 0px' });

    Array.prototype.forEach.call(nodes, function (el) { io.observe(el); });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
