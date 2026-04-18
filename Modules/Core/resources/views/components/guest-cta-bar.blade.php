{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@php
    $excludedRoutes = request()->is(
        'user/*',
        'dashboard*',
        'login*',
        'register*',
        'magic-link*',
        'admin*',
        'privacy-policy*',
        'terms-of-use*',
        'cookie-policy*',
        'rights-request*',
        'boutique/panier*',
        'boutique/paiement*',
        'boutique/commander*',
        'boutique/confirmation*',
        'boutique/suivi*',
        'boutique/mes-commandes*',
        'auth/*',
        'otp*'
    );
@endphp

@guest
    @if(\Modules\Settings\Facades\Settings::get('guest_cta.enabled', false))
        @if(!$excludedRoutes)
            <div
                role="complementary"
                aria-label="Avantages d’un compte membre"
                id="guest-cta-bar"
                class="gcb"
                x-data="guestCtaBar()"
                x-show="show"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="transform translate-y-full opacity-0"
                x-transition:enter-end="transform translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="transform translate-y-0 opacity-100"
                x-transition:leave-end="transform translate-y-full opacity-0"
            >
                <div class="gcb__inner">
                    <div class="d-none d-md-flex align-items-center gcb__badges">
                        <span class="gcb__badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l.77.161A4.002 4.002 0 0 1 10.5 6c0 .628-.134 1.223-.379 1.758L8 13l-2.121-5.242A4.002 4.002 0 0 1 5.5 6c0-.628.134-1.223.379-1.758L8 1.918z"/>
                            </svg>
                            Alertes IA
                        </span>
                        <span class="gcb__badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M8 1.314C12.438-3.248 20.574 4.748 16 8c-2.736 2.03-4.77 3.54-6 4.692V1.314z"/>
                            </svg>
                            Favoris
                        </span>
                        <span class="gcb__badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                            Listes
                        </span>
                    </div>
                    <div class="d-md-none gcb__mobile-text">
                        Compte gratuit : alertes, favoris, listes
                    </div>
                    <a href="{{ route('magic-login.request') }}" class="gcb__cta">Créer mon compte</a>
                    <button
                        type="button"
                        class="gcb__close"
                        aria-label="Fermer la barre d’avantages, réapparaîtra dans 30 jours"
                        @click="dismiss"
                    >
                        &times;
                    </button>
                </div>
            </div>

            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('guestCtaBar', () => ({
                        show: false,
                        init() {
                            const consentGiven = document.cookie.split(';').some((item) => item.trim().startsWith('consent_v1='));
                            if (!consentGiven) return;

                            const dismissedUntil = localStorage.getItem('laveille_guest_cta_v1_dismissed_until');
                            const now = Date.now();
                            if (dismissedUntil && parseInt(dismissedUntil, 10) > now) return;

                            setTimeout(() => {
                                this.show = true;
                            }, 1200);
                        },
                        dismiss() {
                            const thirtyDays = 30 * 86400000;
                            localStorage.setItem('laveille_guest_cta_v1_dismissed_until', (Date.now() + thirtyDays).toString());
                            this.show = false;
                            if (typeof window.gtag === 'function') {
                                window.gtag('event', 'guest_cta_dismissed', { event_category: 'engagement' });
                            }
                        }
                    }));
                });
            </script>

            <style>
                .gcb { position: fixed; left: 0; right: 0; bottom: 0; z-index: 985; background: rgba(26, 26, 26, 0.94); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-top: 1px solid rgba(99, 102, 241, 0.3); padding: 12px 0; color: #fff; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
                @media (max-width: 991px) { .gcb { bottom: 60px; } }
                .gcb__inner { max-width: 1200px; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 0 16px; margin: 0 auto; }
                .gcb__badges { display: flex; gap: 14px; font-size: 0.85rem; }
                .gcb__badge { display: inline-flex; align-items: center; gap: 4px; color: #fff; }
                .gcb__badge svg { color: #6366f1; flex-shrink: 0; }
                .gcb__mobile-text { font-size: 0.85rem; flex: 1; }
                .gcb__cta { display: inline-flex; align-items: center; background: #6366f1; color: #fff; font-weight: 600; font-size: 0.9rem; padding: 10px 22px; border-radius: 24px; text-decoration: none; white-space: nowrap; min-height: 44px; transition: all 0.2s ease; flex-shrink: 0; }
                .gcb__cta:hover { filter: brightness(1.12); transform: translateY(-1px); color: #fff; text-decoration: none; }
                .gcb__cta:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
                .gcb__close { background: transparent; border: none; color: rgba(255, 255, 255, 0.7); font-size: 1.4rem; cursor: pointer; min-width: 44px; min-height: 44px; line-height: 1; border-radius: 8px; flex-shrink: 0; transition: all 0.2s; }
                .gcb__close:hover, .gcb__close:focus-visible { color: #fff; background: rgba(255, 255, 255, 0.1); outline: 2px solid #fff; outline-offset: 2px; }
                @media (prefers-reduced-motion: reduce) { .gcb { transition: none !important; animation: none !important; } }
                [x-cloak] { display: none !important; }
                @media (max-width: 640px) { .gcb__inner { gap: 8px; padding: 0 10px; } .gcb__cta { padding: 8px 16px; font-size: 0.85rem; } .gcb__close { font-size: 1.2rem; } }
            </style>
        @endif
    @endif
@endguest
