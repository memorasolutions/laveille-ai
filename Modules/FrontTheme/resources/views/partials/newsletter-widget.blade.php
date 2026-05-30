@if(Route::has('newsletter.subscribe'))
    @php $variant = $variant ?? 'sidebar'; @endphp
    @if($variant === 'inline-article')
        <div class="newsletter-inline-article" style="margin: 48px auto; max-width: 600px; padding: 24px; background: #F0FAFB; border-radius: 12px; text-align: center;">
            <h3 style="font-size: 1.5rem; margin-bottom: 12px;">{{ __('Restez informé') }}</h3>
            <p style="margin-bottom: 16px;">{{ __('Inscrivez-vous pour recevoir nos derniers articles.') }}</p>
            <x-fronttheme::newsletter-form layout="stacked" :show-note="false" />
        </div>
    @else
        <div class="wpo-contact-widget widget">
            <h2>{{ __('Restez informé') }}</h2>
            <p>{{ __('Inscrivez-vous pour recevoir nos derniers articles.') }}</p>
            <x-fronttheme::newsletter-form layout="stacked" :show-note="false" />
        </div>
    @endif
@endif
