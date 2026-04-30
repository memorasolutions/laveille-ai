<div id="newsletter-sticky-bar" x-data="{ dismissed: false, checkDismissal() { const dismissedAt = localStorage.getItem('newsletter_sticky_dismissed_at'); if (dismissedAt && (Date.now() - parseInt(dismissedAt)) < 30*24*60*60*1000) { this.dismissed = true; } } }" x-init="checkDismissal()" x-show="!dismissed" x-cloak style="position: fixed; top: 0; left: 0; right: 0; z-index: 9999; height: 44px; padding: 8px 16px; background: linear-gradient(to right, #0B7285, #095a69); color: white; font-size: 0.875rem; box-sizing: border-box;">
    <div style="display: flex; justify-content: space-between; align-items: center; height: 100%;">
        <div class="sticky-bar-text" style="display: flex; align-items: center; gap: 6px; white-space: nowrap;">
            <span aria-hidden="true">📬</span>
            <span>{{ __('Rejoignez 50+ pros - Veille IA Québec hebdo, 5 min/semaine') }}</span>
        </div>
        <div>
            <a href="#newsletter-hero" style="background-color: #F97316; color: white; text-decoration: none; padding: 4px 12px; border-radius: 4px; font-weight: 600; white-space: nowrap;">{{ __('S’inscrire gratuit') }}</a>
        </div>
        <button type="button" aria-label="{{ __('Fermer la barre') }}" @click="dismissed = true; localStorage.setItem('newsletter_sticky_dismissed_at', Date.now().toString());" style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
            &times;
        </button>
    </div>
</div>
<style>
    @media (max-width: 768px) {
        #newsletter-sticky-bar .sticky-bar-text span:not(:first-child) { display: none; }
        #newsletter-sticky-bar .sticky-bar-text { gap: 4px; }
    }
</style>
