<div id="newsletter-sticky-bar"
     x-data="{ dismissed: false, checkDismissal() { const d = localStorage.getItem('newsletter_sticky_dismissed_at'); if (d && (Date.now() - parseInt(d)) < 30*24*60*60*1000) { this.dismissed = true; } } }"
     x-init="checkDismissal()"
     x-cloak
     x-show="!dismissed"
     role="region"
     aria-label="{{ __('Bandeau infolettre') }}"
     style="position: relative; width: 100%; height: 36px; background: #FAFAF7; border-bottom: 1px solid #E5E7EB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 16px; display: flex; align-items: center; justify-content: space-between; height: 100%; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
            <span aria-hidden="true" style="color: #0B7285; font-size: 14px; line-height: 1;">▸</span>
            <span class="sticky-badge" style="background: #E0F2FE; color: #0B7285; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 2px 6px; border-radius: 3px; white-space: nowrap;">{{ __('Veille IA') }}</span>
            <span class="sticky-text-full" style="color: #18181B; font-size: 13px; font-weight: 400; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ __('Veille IA sans buzz : pour stratèges québécois.') }}</span>
            <span class="sticky-text-short" style="color: #18181B; font-size: 13px; font-weight: 400; display: none;">{{ __('Veille IA sans buzz') }}</span>
        </div>
        <div style="display: flex; align-items: center; gap: 16px; flex-shrink: 0;">
            <a href="{{ route('home') }}#newsletter-hero" style="color: #0B7285; font-size: 13px; font-weight: 600; text-decoration: underline; text-underline-offset: 2px; transition: color 150ms ease;" onmouseover="this.style.color='#F97316'" onmouseout="this.style.color='#0B7285'">{{ __('Recevoir hebdo →') }}</a>
            <button type="button" @click="dismissed = true; localStorage.setItem('newsletter_sticky_dismissed_at', Date.now().toString());" aria-label="{{ __('Fermer le bandeau infolettre') }}" style="background: none; border: none; color: #6B7280; font-size: 18px; line-height: 1; cursor: pointer; padding: 0 4px; transition: color 150ms ease;" onmouseover="this.style.color='#18181B'" onmouseout="this.style.color='#6B7280'">&times;</button>
        </div>
    </div>
</div>
<style>
    @media (max-width: 640px) {
        #newsletter-sticky-bar .sticky-badge { display: none !important; }
        #newsletter-sticky-bar .sticky-text-full { display: none !important; }
        #newsletter-sticky-bar .sticky-text-short { display: inline !important; }
    }
</style>
