{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', __('Livres') . ' - ' . __("Livres de Stéphane Lapointe : essais IA et thriller SF") . ' · ' . config('app.name'))
@section('meta_description', __("Découvrez les livres de Stéphane Lapointe : deux essais pratiques sur l'IA (conformité PME, parentalité numérique) et la trilogie techno-thriller Nexus Neural, disponibles en broché et Kindle sur Amazon."))
@section('page_noindex', true)
{{-- ↑ Défense en profondeur : le middleware BooksUnderConstruction bloque déjà l'accès public (503) tant que
     config('books.under_construction') est vrai. Ce noindex évite en plus toute indexation accidentelle si un
     crawler passait malgré tout (ex. superadmin qui partage un lien avant le lancement officiel). --}}

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Livres')])
@endsection

@push('styles')
<style>
    .bk-index-wrapper { padding: 10px 0 60px; }
    .bk-index-intro { max-width: 760px; margin: 0 auto 40px; text-align: center; }
    .bk-index-intro h1 {
        font-family: var(--f-heading); font-size: 2.4rem; font-weight: 800;
        color: var(--c-dark); margin: 0 0 14px; line-height: 1.2;
    }
    .bk-index-intro p { font-size: 1.08rem; line-height: 1.65; color: #4B5563; margin: 0; }

    .bk-section-title {
        font-family: var(--f-heading); font-size: 1.5rem; font-weight: 700;
        margin: 0 0 20px; color: var(--c-dark); padding-left: 14px; position: relative;
    }
    .bk-section-title::before {
        content: ''; position: absolute; left: 0; top: 4px; bottom: 4px;
        width: 4px; background: var(--c-primary); border-radius: 2px;
    }

    .bk-card {
        display: flex; gap: 20px; background: #fff; border-radius: var(--r-base);
        border: 1px solid #E5E7EB; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        padding: 24px; margin-bottom: 28px; text-decoration: none !important;
        color: inherit; transition: all 0.25s;
    }
    .bk-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); }
    .bk-card-cover { flex: 0 0 140px; max-width: 140px; }
    .bk-card-cover img {
        width: 100%; height: auto; border-radius: 6px;
        box-shadow: 0 4px 12px rgba(11,114,133,0.15); display: block;
    }
    .bk-card-body { flex: 1 1 auto; min-width: 0; }
    .bk-card-genre {
        font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em;
        color: var(--c-primary); font-weight: 700; margin: 0 0 6px;
    }
    .bk-card-title {
        font-family: var(--f-heading); font-size: 1.25rem; font-weight: 800;
        color: var(--c-dark); margin: 0 0 6px; line-height: 1.25;
    }
    .bk-card-subtitle { font-size: 0.95rem; color: #6B7280; margin: 0 0 12px; line-height: 1.45; }
    .bk-card-meta { display: flex; flex-wrap: wrap; gap: 8px 16px; font-size: 0.85rem; color: #6B7280; margin-bottom: 12px; }
    .bk-card-price { font-weight: 700; color: var(--c-dark); }
    .bk-card-link { color: var(--c-primary); font-weight: 700; font-size: 0.9rem; }

    .bk-series-banner {
        background: linear-gradient(135deg, #F0FDFA 0%, #ECFEFF 100%);
        border: 1px solid #BAE6FD; border-radius: var(--r-base);
        padding: 28px; margin-bottom: 48px;
    }
    .bk-series-banner-header { text-align: center; max-width: 640px; margin: 0 auto 24px; }
    .bk-series-banner-header h2 {
        font-family: var(--f-heading); font-size: 1.6rem; font-weight: 800;
        color: var(--c-dark); margin: 0 0 10px;
    }
    .bk-series-banner-header p { font-size: 0.98rem; color: #4B5563; margin: 0; line-height: 1.55; }
    .bk-series-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px;
    }
    .bk-tome-card {
        background: #fff; border-radius: 10px; border: 1px solid #E5E7EB;
        padding: 16px; text-align: center; text-decoration: none !important;
        color: inherit; transition: all 0.25s;
    }
    .bk-tome-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); }
    .bk-tome-card img {
        width: 100%; max-width: 130px; height: auto; border-radius: 6px; margin: 0 auto 12px;
        box-shadow: 0 4px 12px rgba(11,114,133,0.15); display: block;
    }
    .bk-tome-num {
        display: inline-block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.05em; color: var(--c-primary); margin-bottom: 4px;
    }
    .bk-tome-title { font-family: var(--f-heading); font-size: 1rem; font-weight: 700; color: var(--c-dark); margin: 0 0 6px; line-height: 1.3; }
    .bk-tome-price { font-size: 0.85rem; color: #6B7280; }

    @media (max-width: 640px) {
        .bk-card { flex-direction: column; }
        .bk-card-cover { max-width: 120px; margin: 0 auto; }
        .bk-index-intro h1 { font-size: 1.9rem; }
    }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
<div class="bk-index-wrapper">
    <div class="container">

        <div class="bk-index-intro">
            <h1>{{ __('Livres') }}</h1>
            <p>{{ __("Deux essais pratiques sur l'intelligence artificielle et une trilogie techno-thriller, écrits par Stéphane Lapointe, fondateur de MEMORA solutions. Disponibles en broché et Kindle sur Amazon.") }}</p>
        </div>

        @php
            $essays = $books->whereNull('series_slug')->sortBy('sort_order');
            $nexusTomes = $books->where('series_slug', 'nexus-neural')->sortBy('series_position');
        @endphp

        @if($essays->isNotEmpty())
            <h2 class="bk-section-title">{{ __('Essais') }}</h2>
            @foreach($essays as $book)
                <a href="{{ route('books.show', $book->slug) }}" class="bk-card" aria-label="{{ __('Voir le livre') }} {{ $book->title }}">
                    <div class="bk-card-cover">
                        <img
                            src="{{ asset($book->cover_image) }}"
                            alt="{{ $book->title }} - {{ __('couverture') }}"
                            loading="lazy" decoding="async" width="300" height="452"
                        >
                    </div>
                    <div class="bk-card-body">
                        @if($book->genre)
                            <p class="bk-card-genre">{{ $book->genre }}</p>
                        @endif
                        <h3 class="bk-card-title">{{ $book->title }}</h3>
                        @if($book->subtitle)
                            <p class="bk-card-subtitle">{{ Str::limit($book->subtitle, 140) }}</p>
                        @endif
                        <div class="bk-card-meta">
                            @if($book->price_paperback)
                                <span class="bk-card-price">{{ __('Broché') }} {{ number_format((float) $book->price_paperback, 2, ',', ' ') }} $ CAD</span>
                            @endif
                            @if($book->price_kindle)
                                <span class="bk-card-price">Kindle {{ number_format((float) $book->price_kindle, 2, ',', ' ') }} $ CAD</span>
                            @endif
                        </div>
                        <span class="bk-card-link">{{ __('Découvrir ce livre') }} →</span>
                    </div>
                </a>
            @endforeach
        @endif

        @if($nexusTomes->isNotEmpty())
            <h2 class="bk-section-title">{{ __('Fiction') }}</h2>
            <div class="bk-series-banner">
                <div class="bk-series-banner-header">
                    <h2>{{ __('Trilogie Nexus Neural') }}</h2>
                    <p>{{ __("Techno-thriller de science-fiction ancré à Montréal. Étienne Marchant s'implante une interface cerveau-machine qui décuple ses capacités, au prix de son humanité. Contenu réservé à un public adulte (18+).") }}</p>
                </div>
                <div class="bk-series-grid">
                    @foreach($nexusTomes as $tome)
                        <a href="{{ route('books.show', $tome->slug) }}" class="bk-tome-card" aria-label="{{ __('Voir') }} {{ $tome->title }}">
                            <img
                                src="{{ asset($tome->cover_image) }}"
                                alt="{{ $tome->title }} - {{ __('couverture') }}"
                                loading="lazy" decoding="async" width="260" height="392"
                            >
                            <span class="bk-tome-num">{{ __('Tome') }} {{ $tome->series_position }}</span>
                            <h3 class="bk-tome-title">{{ $tome->title }}</h3>
                            @if($tome->price_kindle)
                                <p class="bk-tome-price">Kindle {{ number_format((float) $tome->price_kindle, 2, ',', ' ') }} $ CAD</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
</section>
@endsection
