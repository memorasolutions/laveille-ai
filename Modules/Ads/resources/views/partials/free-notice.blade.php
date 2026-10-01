{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Bandeau discret « gratuit grâce à la pub » : visiteurs anonymes seulement, fermable, sans mur.
     DÉSACTIVABLE : config('services.adsense.free_notice') (env ADSENSE_FREE_NOTICE=false) ou module Ads
     absent (le layout l'inclut par @includeIf). Rien n'est rendu non plus s'il n'y a pas de client AdSense,
     pour un membre, ou sur une page @section('no_ads') (aucune pub n'y est servie : le message serait faux).
     Aucune donnée sortante : seul un drapeau localStorage (try/catch) retient la fermeture (Loi 25). --}}
@if(config('services.adsense.free_notice', true) && config('services.adsense.client_id') && auth()->guest() && ! \Illuminate\Support\Facades\View::hasSection('no_ads'))
<style>
    .lv-fn { position: fixed; z-index: 9000; left: 16px; right: 16px; bottom: calc(32px + env(safe-area-inset-bottom, 0px)); display: flex; align-items: center; gap: 12px; flex-wrap: wrap; justify-content: space-between; padding: 12px 16px; background: #064E5A; color: #fff; border-radius: 12px; box-shadow: 0 6px 24px rgba(0, 0, 0, .22); font-size: 15px; line-height: 1.45; }
    .lv-fn[hidden] { display: none; }
    .lv-fn p { margin: 0; flex: 1 1 220px; color: #fff; }
    .lv-fn button { min-height: 44px; min-width: 44px; padding: 8px 18px; background: #fff; color: #064E5A; border: 2px solid #fff; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer; }
    .lv-fn button:hover { background: #E6F2F4; }
    .lv-fn button:focus-visible { outline: 3px solid #FDE68A; outline-offset: 2px; }
    @media (min-width: 768px) { .lv-fn { left: auto; right: 20px; max-width: 460px; } }
    body.lv-fn-on { padding-bottom: 110px; }
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
  box.hidden = false;
  document.body.classList.add('lv-fn-on');
  document.getElementById('lv-free-notice-ok').addEventListener('click', function () {
    box.hidden = true;
    document.body.classList.remove('lv-fn-on');
    try { window.localStorage.setItem(KEY, '1'); } catch (e) { /* ignoré */ }
  });
})();
</script>
@endif
