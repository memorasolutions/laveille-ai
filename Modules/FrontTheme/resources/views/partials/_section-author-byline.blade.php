{{-- S90 #82 byline section-level (mono-auteur EEAT 2026 pattern Korben/NN/g) --}}
{{-- Remplace la byline avatar+nom répétée sur chaque carte de listing. --}}
{{-- EEAT préservé : Schema.org Person + page article + sitemap auteur inchangés. --}}
<div class="section-author-byline" itemscope itemtype="https://schema.org/Person">
    <a href="{{ route('author.show', 'stephane-lapointe') }}" class="section-author-byline-link" rel="author">
        <img src="{{ asset('images/logo-avatar.png') }}"
             alt="{{ __('Avatar') }} {{ trans('fronttheme::authors.stephane-lapointe.name') }}"
             loading="lazy" decoding="async" width="36" height="36"
             class="section-author-byline-avatar" itemprop="image">
        <span class="section-author-byline-text">
            <span class="section-author-byline-prefix">{{ __('Par') }}</span>
            <span class="section-author-byline-name" itemprop="name">{{ trans('fronttheme::authors.stephane-lapointe.name') }}</span>
            <span class="section-author-byline-cta">— {{ __('Tous les articles') }} →</span>
        </span>
    </a>
</div>
