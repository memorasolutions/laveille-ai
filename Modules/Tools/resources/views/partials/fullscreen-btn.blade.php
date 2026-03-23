{{-- Composant plein écran réutilisable — API Fullscreen native
     Usage: @include('tools::partials.fullscreen-btn')
     Le bouton doit être DANS un élément avec la classe "tool-fullscreen-target"
     Ou passer un targetId: @include('tools::partials.fullscreen-btn', ['targetId' => '#mon-id'])
--}}
<button type="button"
        class="js-tool-fullscreen-btn"
        data-fullscreen-target="{{ $targetId ?? '' }}"
        title="{{ __('Plein écran') }}"
        aria-label="{{ __('Plein écran') }}"
        style="border-radius: var(--r-btn, 4px); border: 1px solid #dee2e6; padding: 4px 8px; font-size: 0.85rem; background: #fff; color: #333; cursor: pointer; line-height: 1; display: inline-flex; align-items: center; justify-content: center;">
    <svg class="icon-expand" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: block;">
        <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
    </svg>
    <svg class="icon-compress" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
        <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/>
    </svg>
</button>

@push('scripts')
@once
<style>
.tool-fullscreen-target.is-fullscreen { background: #fff; overflow-y: auto; padding: 20px; width: 100%; height: 100%; }
:fullscreen { background: #fff; overflow-y: auto; }
:-webkit-full-screen { background: #fff; overflow-y: auto; }
</style>
<script>
(function() {
    if (window.toolFullscreenInitialized) return;
    window.toolFullscreenInitialized = true;

    document.addEventListener('DOMContentLoaded', function() {
        document.body.addEventListener('click', function(e) {
            var btn = e.target.closest('.js-tool-fullscreen-btn');
            if (!btn) return;

            var targetId = btn.getAttribute('data-fullscreen-target');
            var target = targetId ? document.querySelector(targetId) : btn.closest('.tool-fullscreen-target');

            if (!target) { console.warn('Fullscreen: aucune cible .tool-fullscreen-target trouvée'); return; }

            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                if (target.requestFullscreen) target.requestFullscreen();
                else if (target.webkitRequestFullscreen) target.webkitRequestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
                else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
            }
        });

        function onFsChange() {
            var fsEl = document.fullscreenElement || document.webkitFullscreenElement;
            document.querySelectorAll('.js-tool-fullscreen-btn').forEach(function(btn) {
                btn.querySelector('.icon-expand').style.display = 'block';
                btn.querySelector('.icon-compress').style.display = 'none';
                btn.title = 'Plein écran';
            });
            document.querySelectorAll('.tool-fullscreen-target').forEach(function(el) {
                el.classList.remove('is-fullscreen');
            });
            if (fsEl) {
                fsEl.classList.add('is-fullscreen');
                var activeBtn = fsEl.querySelector('.js-tool-fullscreen-btn');
                if (activeBtn) {
                    activeBtn.querySelector('.icon-expand').style.display = 'none';
                    activeBtn.querySelector('.icon-compress').style.display = 'block';
                    activeBtn.title = 'Quitter le plein écran';
                }
            }
        }

        document.addEventListener('fullscreenchange', onFsChange);
        document.addEventListener('webkitfullscreenchange', onFsChange);
    });
})();
</script>
@endonce
@endpush
