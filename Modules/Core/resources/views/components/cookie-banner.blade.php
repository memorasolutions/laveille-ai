@if(empty($_COOKIE['lv-cookie-consent']))
<div
    x-data="{
        consent: { essential: true, analytics: false, marketing: false },
        showDetails: false,
        hideBanner() {
            document.cookie = 'lv-cookie-consent=' + JSON.stringify(this.consent) + '; path=/; max-age=31536000; SameSite=Lax; Secure';
            window.dispatchEvent(new CustomEvent('lv-cookie-consent-updated', { detail: this.consent }));
            this.$el.remove();
        }
    }"
    x-on:keydown.escape.window="hideBanner()"
    role="dialog"
    aria-modal="false"
    aria-labelledby="lv-cookie-title"
    aria-describedby="lv-cookie-desc"
    class="lv-cookie-banner"
>
    <div class="lv-cookie-inner">
        <div class="lv-cookie-flex">
            <div class="lv-cookie-icon" aria-hidden="true">🍪</div>
            <div class="lv-cookie-body">
                <h2 id="lv-cookie-title" class="lv-cookie-h2">Cookies et confidentialité</h2>
                <p id="lv-cookie-desc" class="lv-cookie-p">
                    Nous utilisons des témoins (cookies) pour faire fonctionner le site (essentiels) et — avec ton consentement — mesurer l'audience (analytics). Tu peux ajuster tes choix à tout moment. Conformément à la <strong>Loi 25 (QC)</strong> + <strong>RGPD (EU)</strong>.
                </p>

                <div x-show="showDetails" x-cloak class="lv-cookie-details">
                    <label class="lv-cookie-toggle">
                        <input type="checkbox" x-model="consent.essential" disabled>
                        <span class="lv-cookie-label"><strong>Essentiels</strong> (toujours activés) — session, sécurité, préférences</span>
                    </label>
                    <label class="lv-cookie-toggle">
                        <input type="checkbox" x-model="consent.analytics">
                        <span class="lv-cookie-label"><strong>Analytics</strong> — Google Analytics 4, Search Console</span>
                    </label>
                    <label class="lv-cookie-toggle">
                        <input type="checkbox" x-model="consent.marketing">
                        <span class="lv-cookie-label"><strong>Marketing</strong> — publicité ciblée, retargeting</span>
                    </label>
                </div>

                <div class="lv-cookie-actions">
                    <button type="button" @click="consent = { essential: true, analytics: false, marketing: false }; hideBanner()" class="lv-cookie-btn-secondary">
                        Tout refuser
                    </button>
                    <button type="button" @click="showDetails = !showDetails" class="lv-cookie-btn-secondary">
                        <span x-text="showDetails ? 'Masquer' : 'Personnaliser'"></span>
                    </button>
                    <button type="button" @click="hideBanner()" x-show="showDetails" class="lv-cookie-btn-primary">
                        Enregistrer mes choix
                    </button>
                    <button type="button" @click="consent = { essential: true, analytics: true, marketing: true }; hideBanner()" x-show="!showDetails" class="lv-cookie-btn-primary">
                        Tout accepter
                    </button>
                </div>

                <a href="{{ url('/page/confidentialite') }}" class="lv-cookie-link" aria-label="Lire notre politique de confidentialité (nouvelle fenêtre)" target="_blank" rel="noopener">
                    Politique de confidentialité →
                </a>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
.lv-cookie-banner { position: fixed; bottom: 0; left: 0; right: 0; z-index: 9999; background: #FFFFFF; border-top: 2px solid #064E5A; box-shadow: 0 -8px 24px rgba(0,0,0,0.12); font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
.lv-cookie-inner { max-width: 1200px; margin: 0 auto; padding: 20px 24px; }
.lv-cookie-flex { display: flex; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.lv-cookie-icon { font-size: 32px; flex-shrink: 0; }
.lv-cookie-body { flex: 1; min-width: 280px; }
.lv-cookie-h2 { color: #064E5A; font-size: 1.125rem; font-weight: 700; margin: 0 0 8px; }
.lv-cookie-p { color: #3A4050; font-size: 0.9375rem; line-height: 1.5; margin: 0 0 16px; max-width: 65ch; }
.lv-cookie-details { margin: 16px 0; padding: 12px 16px; background: #F8FAFB; border-radius: 8px; }
.lv-cookie-toggle { display: flex; align-items: center; gap: 10px; padding: 8px 0; cursor: pointer; }
.lv-cookie-toggle input[type="checkbox"] { width: 24px; height: 24px; min-width: 24px; min-height: 24px; accent-color: #064E5A; cursor: pointer; }
.lv-cookie-toggle input[type="checkbox"]:disabled { opacity: 0.6; cursor: not-allowed; }
.lv-cookie-label { font-size: 0.875rem; color: #3A4050; }
.lv-cookie-actions { display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0 12px; }
.lv-cookie-btn-primary { background: #064E5A; color: #FFFFFF; padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 0.9375rem; cursor: pointer; min-height: 44px; min-width: 44px; }
.lv-cookie-btn-primary:hover { background: #043C45; }
.lv-cookie-btn-primary:focus-visible { outline: 3px solid #9A2A06; outline-offset: 2px; }
.lv-cookie-btn-secondary { background: transparent; color: #064E5A; padding: 10px 20px; border: 2px solid #064E5A; border-radius: 8px; font-weight: 600; font-size: 0.9375rem; cursor: pointer; min-height: 44px; min-width: 44px; }
.lv-cookie-btn-secondary:hover { background: #F0FAFB; }
.lv-cookie-btn-secondary:focus-visible { outline: 3px solid #9A2A06; outline-offset: 2px; }
.lv-cookie-link { display: inline-block; color: #064E5A; font-size: 0.8125rem; text-decoration: underline; min-height: 44px; line-height: 44px; }
.lv-cookie-link:focus-visible { outline: 3px solid #9A2A06; outline-offset: 2px; }
@media (max-width: 640px) {
    .lv-cookie-actions { flex-direction: column; align-items: stretch; }
    .lv-cookie-btn-primary, .lv-cookie-btn-secondary { width: 100%; text-align: center; }
}
</style>
@endif
