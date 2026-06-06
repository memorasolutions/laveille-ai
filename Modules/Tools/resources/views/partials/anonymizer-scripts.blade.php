{{-- Scripts PARTAGÉS de l'éditeur d'anonymisation (DRY) — à @push('scripts') sur toute page qui
     embarque <x-tools::anonymizer-editor>. Le moteur est 100 % client-side (aucun appel serveur).
     Auteur: MEMORA solutions, https://memora.solutions --}}
{{-- Désinscrit un éventuel ancien Service Worker (outil pré-refonte) --}}
<script>
(() => {
  try {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.getRegistrations().then(registrations => {
        registrations.forEach(r => { if (r.scope.includes('/outils/anonymiseur')) r.unregister(); });
      });
    }
    if (window.caches) {
      caches.keys().then(keys => { keys.forEach(k => { if (/anonym/i.test(k)) caches.delete(k); }); });
    }
  } catch (e) {}
})();
</script>
<script>window.LV_ANON_VERSION = '{{ config('version.semver') }}';</script>
<script src="{{ asset('assets/tools/anonymiseur/anonymizer-core.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/anonymizer-rich.js') }}?v={{ config('version.semver') }}" defer></script>
<script src="{{ asset('assets/tools/anonymiseur/anonymizer-ui.js') }}?v={{ config('version.semver') }}" defer></script>
