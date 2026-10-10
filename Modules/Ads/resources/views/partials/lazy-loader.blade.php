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

  // Cellules (data-lv-ad-cell) : en attente (hors flux, voir CSS de la page) jusqu'a ce que l'annonce soit
  // SERVIE (data-ad-status=filled -> lv-ad-ok, la cellule entre dans la grille). Retiree entierement
  // (lv-ad-off : cellule + etiquette) si AdSense n'est pas charge (pas de consentement), si l'annonce est
  // « unfilled », ou si rien n'est servi 6 s apres la demande (bloqueur de pub, faible inventaire).
  function watchCell(cell) {
    var ins = cell.querySelector('ins.adsbygoogle');
    if (!ins) { cell.classList.add('lv-ad-off'); return; }
    function check() {
      var st = ins.getAttribute('data-ad-status');
      if (st === 'filled') { cell.classList.remove('lv-ad-off'); cell.classList.add('lv-ad-ok'); return true; }
      if (st === 'unfilled') { cell.classList.remove('lv-ad-ok'); cell.classList.add('lv-ad-off'); return true; }
      return false;
    }
    new MutationObserver(check).observe(ins, { attributes: true, attributeFilter: ['data-ad-status'] });
    setTimeout(function () { if (!check()) { cell.classList.add('lv-ad-off'); } }, 6000);
  }

  function collapseCells() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-lv-ad-cell]'), function (cell) {
      if (!window.__lvAdsenseLoaded) { cell.classList.add('lv-ad-off'); return; }
      watchCell(cell);
    });
  }

  // Contenu ajouté après coup (défilement infini) : une cellule d'annonce clonée est poussée et surveillée.
  document.addEventListener('lv:content-appended', function (e) {
    var node = e.detail && e.detail.node;
    if (!node || !node.matches || !node.matches('[data-lv-ad-cell]')) { return; }
    var ins = node.querySelector('ins.adsbygoogle');
    if (!window.__lvAdsenseLoaded || !ins) { node.classList.add('lv-ad-off'); return; }
    ins.setAttribute('data-lv-pushed', '1');
    try { (window.adsbygoogle = window.adsbygoogle || []).push({}); } catch (err) { /* sans effet sur la page */ }
    watchCell(node);
  });

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

    // Les unites en cellule (hors flux tant que non servies) ne peuvent pas etre « vues » : poussees d'emblee.
    var observable = [];
    Array.prototype.forEach.call(nodes, function (el) {
      if (el.closest('[data-lv-ad-cell]')) { push(el); } else { observable.push(el); }
    });
    nodes = observable;

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
