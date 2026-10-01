{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Bandeau discret « gratuit grâce à la pub » : visiteurs anonymes seulement, fermable, sans mur.
     DÉSACTIVABLE : config('services.adsense.free_notice') (env ADSENSE_FREE_NOTICE=false) ou module Ads
     absent (le layout l'inclut par @includeIf). Rien n'est rendu non plus s'il n'y a pas de client AdSense,
     pour un membre, ou sur une page @section('no_ads') (aucune pub n'y est servie : le message serait faux).
     Aucune donnée sortante : seul un drapeau localStorage (try/catch) retient la fermeture (Loi 25). --}}
@if(config('services.adsense.free_notice', true) && config('services.adsense.client_id') && auth()->guest() && ! \Illuminate\Support\Facades\View::hasSection('no_ads'))
<style>
    /* Barre fine pleine largeur fixée en bas : ne recouvre rien. Sa hauteur réelle est publiée dans
       --lv-free-notice-h (script ci-dessous) pour que le contenu (padding du body) et les contrôles fixes
       du bas (barre de partage, retour en haut, onglet des témoins) se placent AU-DESSUS d'elle.
       z-index 9991 : au-dessus de l'onglet des témoins (9990), sous le voile et la fenêtre des témoins. */
    .lv-fn { position: fixed; z-index: 9991; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 16px; padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px)); background: #064E5A; color: #fff; box-shadow: 0 -2px 10px rgba(0, 0, 0, .2); font-size: 14px; line-height: 1.35; }
    .lv-fn[hidden] { display: none; }
    #lv-free-notice p { margin: 0; flex: 1 1 auto; min-width: 0; color: #fff; font-size: inherit !important; line-height: inherit !important; }
    .lv-fn button { flex: 0 0 auto; min-height: 44px; min-width: 44px; padding: 6px 12px; background: #fff; color: #064E5A; border: 2px solid #fff; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; }
    .lv-fn button:hover { background: #E6F2F4; }
    .lv-fn button:focus-visible { outline: 3px solid #FDE68A; outline-offset: 2px; }
    @media (max-width: 480px) { .lv-fn { font-size: 13px; gap: 10px; padding-left: 12px; padding-right: 12px; } .lv-fn button { font-size: 13px; } }
    @media (min-width: 768px) { .lv-fn { justify-content: center; font-size: 15px; } #lv-free-notice p { flex: 0 1 auto; } }
    body.lv-fn-on { padding-bottom: calc(var(--lv-share-h, 0px) + var(--lv-free-notice-h, 0px)); }
    body.lv-fn-on .share-bottom { bottom: var(--lv-free-notice-h, 0px) !important; }
    body.lv-fn-on .back-to-top { bottom: calc(15px + var(--lv-free-notice-h, 0px)) !important; }
    body.lv-fn-on .cc-fab { bottom: var(--lv-free-notice-h, 0px); }
    @media (prefers-reduced-motion: no-preference) { body.lv-fn-on .share-bottom, body.lv-fn-on .back-to-top { transition: bottom .2s; } }
</style>
<div class="lv-fn" id="lv-free-notice" role="region" aria-label="{{ __('Information sur la publicité') }}" hidden>
    <p>{{ __('laveille.ai reste gratuit grâce à la publicité. Merci de nous soutenir en la laissant s’afficher.') }}</p>
    <button type="button" id="lv-free-notice-ok">{{ __('J’ai compris') }}</button>
</div>
<script>
(function () {
  'use strict';
  var KEY = 'lv_free_notice_dismissed';
  var box = document.getElementById('lv-free-notice');
  if (!box) { return; }
  try { if (window.localStorage.getItem(KEY)) { return; } } catch (e) { /* stockage indisponible : on affiche */ }
  var root = document.documentElement;
  function mesurer() { root.style.setProperty('--lv-free-notice-h', box.hidden ? '0px' : box.offsetHeight + 'px'); }
  box.hidden = false;
  document.body.classList.add('lv-fn-on');
  mesurer();
  window.addEventListener('resize', mesurer);
  document.getElementById('lv-free-notice-ok').addEventListener('click', function () {
    box.hidden = true;
    document.body.classList.remove('lv-fn-on');
    mesurer();
    window.removeEventListener('resize', mesurer);
    try { window.localStorage.setItem(KEY, '1'); } catch (e) { /* ignoré */ }
  });
})();
</script>
@endif
