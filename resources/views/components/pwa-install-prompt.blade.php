<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Bannière d'installation PWA - Alpine.js + Bootstrap 5 --}}
<div x-data="{
    show: false,
    isStandalone: window.matchMedia('(display-mode: standalone)').matches,
    isDismissed: localStorage.getItem('pwa-install-dismissed') &&
                 (Date.now() - parseInt(localStorage.getItem('pwa-install-dismissed'))) < (7 * 24 * 60 * 60 * 1000)
}"
    x-init="
        if (!isStandalone && !isDismissed) {
            window.addEventListener('pwa-install-available', () => { show = true; });
            window.addEventListener('pwa-installed', () => { show = false; });
        }
    "
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:leave="transition ease-in duration-200"
    class="position-fixed bottom-0 start-0 end-0"
    style="display: none; z-index: 9999;">
    <div class="bg-primary text-white rounded-top shadow-lg p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="me-3">
                <h6 class="mb-1 fw-bold">{{ __('Installer l\'application') }}</h6>
                <p class="mb-0 small">{{ __('Accédez rapidement depuis votre écran d\'accueil.') }}</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-sm" @click="window.pwaInstall?.()">
                    {{ __('Installer') }}
                </button>
                <button class="btn btn-outline-light btn-sm"
                        @click="show = false; localStorage.setItem('pwa-install-dismissed', Date.now().toString())">
                    &times;
                </button>
            </div>
        </div>
    </div>
</div>
