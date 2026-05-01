<section id="newsletter-hero" class="newsletter-hero-banner" style="padding: 48px 24px; background: linear-gradient(to bottom, #FFF8F1, #FFFDF9); scroll-margin-top: 100px;">
    <div style="max-width: 800px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 1.8rem; margin-bottom: 12px;">{{ __('Veille IA Québec - chaque dimanche dans votre boîte') }}</h2>
        <p style="font-size: 1.1rem; margin-bottom: 24px;">{{ __('Rejoignez 50+ professionnels qui suivent la transformation IA du Québec - 5 min de lecture hebdo, 0 spam, gratuit') }}</p>
        <form action="{{ route('newsletter.subscribe') }}" method="POST" style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
            @csrf
            <input type="hidden" name="source" value="hero-homepage">
            <input type="email" name="email" required aria-label="{{ __('Votre adresse e-mail') }}" style="flex: 1; min-width: 280px; padding: 12px 16px; border: 1px solid #ccc; border-radius: 6px; font-size: 1rem;">
            <button type="submit" style="background-color: #F97316; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1rem; white-space: nowrap;">{{ __('S’inscrire') }}</button>
        </form>
        <p style="font-size: 0.875rem; color: #666;">{{ __('Conforme Loi 25 + RGPD - Désabonnement 1-clic') }}</p>
    </div>
</section>
