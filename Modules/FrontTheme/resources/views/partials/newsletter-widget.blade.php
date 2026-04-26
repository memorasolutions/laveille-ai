@if(Route::has('newsletter.subscribe'))
    @php $variant = $variant ?? 'sidebar'; @endphp
    @if($variant === 'inline-article')
        <div class="newsletter-inline-article" style="margin: 48px auto; max-width: 600px; padding: 24px; background: #F0FAFB; border-radius: 12px; text-align: center;">
            <h3 style="font-size: 1.5rem; margin-bottom: 12px;">{{ __('Restez informé') }}</h3>
            <p style="margin-bottom: 16px;">{{ __('Inscrivez-vous pour recevoir nos derniers articles.') }}</p>
            <form action="{{ route('newsletter.subscribe') }}" method="POST">
                @csrf
                <div style="margin-bottom: 10px;">
                    <input type="email" name="email" class="form-control" placeholder="{{ __('Votre courriel') }}" required style="border-radius: 4px; width: 100%; padding: 8px 12px;" aria-label="{{ __('Courriel pour l\'infolettre') }}" autocomplete="email">
                </div>
                <button type="submit" class="theme-btn" style="width: 100%; border: none; padding: 10px;">{{ __('S\'inscrire') }}</button>
            </form>
        </div>
    @else
        <div class="wpo-contact-widget widget">
            <h2>{{ __('Restez informé') }}</h2>
            <p>{{ __('Inscrivez-vous pour recevoir nos derniers articles.') }}</p>
            <form action="{{ route('newsletter.subscribe') }}" method="POST">
                @csrf
                <div style="margin-bottom: 10px;">
                    <input type="email" name="email" class="form-control" placeholder="{{ __('Votre courriel') }}" required style="border-radius: 4px;" aria-label="{{ __('Courriel pour l\'infolettre') }}" autocomplete="email">
                </div>
                <button type="submit" class="theme-btn" style="width: 100%; border: none; padding: 10px;">{{ __('S\'inscrire') }}</button>
            </form>
        </div>
    @endif
@endif
