@props(['categorySlug', 'categoryName' => null])

{{-- S90 #43 — Désactivé tant que Phase 3 (cron weekly digest) n'est pas livrée.
     Réactiver via DIRECTORY_CATEGORY_ALERTS_ENABLED=true. --}}
@if(config('directory.category_alerts.enabled', false))

@php
    /**
     * S90 #43 — Bouton d'alerte catégorie (Phase 2 retention).
     *
     * Permet à un user authentifié de souscrire/désinscrire aux nouveautés
     * d'une catégorie. Affiché uniquement quand ?category=X est dans l'URL.
     *
     * Usage : <x-directory::category-alert-button :categorySlug="$slug" :categoryName="$name" />
     */
    $statusUrl = url('/annuaire/categorie/' . $categorySlug . '/alerte/statut');
    $toggleUrl = url('/annuaire/categorie/' . $categorySlug . '/alerte');
    $loginUrl = route('login');
    $currentUrl = url()->full();
@endphp

<div
    x-data="{
        loading: true,
        authenticated: false,
        subscribed: false,
        message: '',
        error: '',
        async fetchStatus() {
            try {
                const res = await fetch(@js($statusUrl), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.authenticated = !!data.authenticated;
                this.subscribed = !!data.subscribed;
            } catch (e) {
                this.error = 'Statut indisponible.';
            } finally {
                this.loading = false;
            }
        },
        async toggle() {
            if (!this.authenticated) {
                window.location.href = @js($loginUrl) + '?redirect_to=' + encodeURIComponent(@js($currentUrl));
                return;
            }
            this.loading = true;
            this.message = '';
            this.error = '';
            try {
                const res = await fetch(@js($toggleUrl), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (data.success) {
                    this.subscribed = !!data.subscribed;
                    this.message = data.message || '';
                    setTimeout(() => { this.message = ''; }, 3500);
                } else {
                    this.error = data.message || 'Erreur';
                    if (data.login_url) window.location.href = data.login_url + '?redirect_to=' + encodeURIComponent(@js($currentUrl));
                }
            } catch (e) {
                this.error = 'Erreur réseau';
            } finally {
                this.loading = false;
            }
        }
    }"
    x-init="fetchStatus()"
    style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;"
>
    <button
        type="button"
        @click="toggle"
        :disabled="loading"
        :aria-pressed="subscribed"
        x-bind:title="subscribed ? '{{ __('Désactiver les alertes hebdomadaires') }}' : '{{ __('Recevoir un récap hebdo des nouveautés de cette catégorie par courriel') }}'"
        style="display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 10px 18px; border-radius: 999px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.15s ease; border: 1.5px solid;"
        :style="subscribed
            ? 'background: var(--c-primary, #064E5A); color: #fff; border-color: var(--c-primary, #064E5A);'
            : 'background: #fff; color: var(--c-primary, #064E5A); border-color: var(--c-primary, #064E5A);'"
    >
        <span x-show="!loading && !subscribed" aria-hidden="true">🔔</span>
        <span x-show="!loading && subscribed" aria-hidden="true">✓</span>
        <span x-show="loading" aria-hidden="true">⏳</span>
        <span x-show="!loading && !authenticated">{{ __("Recevoir les alertes") }}@if($categoryName) — {{ $categoryName }}@endif</span>
        <span x-show="!loading && authenticated && !subscribed">{{ __("M'alerter des nouveautés") }}</span>
        <span x-show="!loading && authenticated && subscribed">{{ __('Alertes actives') }}</span>
        <span x-show="loading">{{ __('Chargement...') }}</span>
    </button>

    <span x-show="message" x-cloak x-transition style="font-size: 13px; color: #14532d; font-weight: 600;" role="status" aria-live="polite" x-text="message"></span>
    <span x-show="error" x-cloak style="font-size: 13px; color: #991b1b; font-weight: 600;" role="alert" x-text="error"></span>
</div>

@endif
