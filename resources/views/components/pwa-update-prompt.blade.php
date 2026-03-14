<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Toast de mise à jour PWA - Alpine.js + Bootstrap 5 --}}
<div x-data="{ show: false }"
     x-init="window.addEventListener('pwa-update-available', () => { show = true; })"
     x-show="show"
     x-transition
     class="position-fixed top-0 end-0 m-3"
     style="display: none; z-index: 9999;">
    <div class="bg-success text-white rounded shadow p-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="me-3">
                <h6 class="mb-1 fw-bold">{{ __('Mise à jour disponible') }}</h6>
                <p class="mb-0 small">{{ __('Une nouvelle version est prête.') }}</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-light btn-sm" @click="window.pwaUpdate?.()">
                    {{ __('Mettre à jour') }}
                </button>
                <button class="btn btn-outline-light btn-sm" @click="show = false">
                    &times;
                </button>
            </div>
        </div>
    </div>
</div>
