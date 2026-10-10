{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Chargement différé des unités AdSense (anti-CLS, Core Web Vitals). À inclure UNE seule fois,
     en pied de page. N'émet rien s'il n'y a aucun emplacement AdSense différé sur la page. --}}
<style>
  /* Effondrement propre : une cellule dont l'annonce n'est pas servie ne laisse ni cadre ni étiquette. */
  [data-lv-ad-cell].lv-ad-off, ins.adsbygoogle[data-ad-status="unfilled"] { display: none !important; }
</style>
<script>
(function () {
  'use strict';
  if (window.__lvAdsLazyInit) { return; }
  window.__lvAdsLazyInit = true;

  // Cellules (data-lv-ad-cell) : visibles seulement tant qu'une annonce peut encore arriver (espace
  // réservé anti-CLS pendant le chargement), retirées si AdSense n'est pas chargé (pas de consentement),
  // si l'annonce est « unfilled », ou si rien n'est servi 10 s après la demande (bloqueur de pub).
  function collapseCells() {
    var cells = document.querySelectorAll('[data-lv-ad-cell]');
    if (!cells.length) { return; }
    Array.prototype.forEach.call(cells, function (cell) {
      var ins = cell.querySelector('ins.adsbygoogle');
      if (!ins) { cell.classList.add('lv-ad-off'); return; }
      if (!window.__lvAdsenseLoaded) { cell.classList.add('lv-ad-off'); return; }
      function check() {
        var st = ins.getAttribute('data-ad-status');
        if (st === 'filled') { cell.classList.remove('lv-ad-off'); return true; }
        if (st === 'unfilled') { cell.classList.add('lv-ad-off'); return true; }
        return false;
      }
      var timer = null;
      function arm() {
        if (ins.getAttribute('data-lv-pushed') && !timer) {
          timer = setTimeout(function () { if (!check()) { cell.classList.add('lv-ad-off'); } }, 10000);
        }
      }
      new MutationObserver(function () { arm(); check(); }).observe(ins, { attributes: true, attributeFilter: ['data-ad-status', 'data-lv-pushed'] });
      arm(); check();
    });
  }

  function init() {
    // Après le chargement + 1,5 s : laisse au consentement (retour d'un visiteur déjà consentant) le temps
    // de charger AdSense avant de juger qu'il est absent.
    var runCollapse = function () { setTimeout(collapseCells, 1500); };
    if (document.readyState === 'complete') { runCollapse(); } else { window.addEventListener('load', runCollapse); }
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
