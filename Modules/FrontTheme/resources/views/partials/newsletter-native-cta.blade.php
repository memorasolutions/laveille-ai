<div class="newsletter-native-cta" data-source="article-mid" style="background-color: #FFF8F1; border-left: 4px solid #0B7285; padding: 24px; border-radius: 8px; margin: 32px auto; max-width: 600px;">
    <h3 style="font-size: 1.3rem; margin-top: 0;">{{ __('Cet article vous a plu ?') }}</h3>
    <p>{{ __('Recevez chaque mercredi notre veille IA pour le milieu québécois - 5 min de lecture, gratuit.') }}</p>
    <form action="{{ route('newsletter.subscribe') }}" method="POST" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
        @csrf
        <input type="hidden" name="source" value="native-article-cta">
        <input type="email" name="email" required aria-label="{{ __('Votre adresse e-mail') }}" style="flex: 1; min-width: 200px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem;">
        <button type="submit" style="background-color: #F97316; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 0.9rem;">{{ __('S’inscrire') }}</button>
    </form>
    <p style="font-size: 0.75rem; color: var(--c-text-muted, #52586a); margin-top: 12px;">{{ __('Double opt-in. Loi 25 / RGPD. Désabonnement 1-clic.') }}</p>
</div>
