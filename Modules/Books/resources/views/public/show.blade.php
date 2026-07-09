{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@section('title', $book->title . ' - ' . __('Livres') . ' - ' . config('app.name'))
@section('meta_description', safe_excerpt($book->one_sentence_answer ?? strip_tags($book->excerpt ?? ''), 160))
@section('og_type', 'book')
@section('page_noindex', true)
{{-- ↑ Défense en profondeur : le middleware BooksUnderConstruction bloque déjà l'accès public (503) tant que
     config('books.under_construction') est vrai. --}}
@php
    $_bkOgPath = $book->cover_image ? preg_replace('/-cover-600\.jpg$/', '-og-1200x630.jpg', $book->cover_image) : null;
@endphp
@if($_bkOgPath && file_exists(public_path($_bkOgPath)))
    @section('og_image', asset($_bkOgPath))
@endif

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $book->title,
        'breadcrumbItems' => [__('Livres'), $book->title],
    ])
@endsection

@push('styles')
<style>
    .bk-show-wrapper { padding: 10px 0 60px; }

    .bk-hero {
        display: flex; gap: 40px; flex-wrap: wrap; align-items: flex-start;
        background: #fff; border-radius: var(--r-base); padding: 40px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border-top: 4px solid var(--c-primary);
        margin-bottom: 32px;
    }
    .bk-hero-cover { flex: 0 0 260px; max-width: 260px; margin: 0 auto; }
    .bk-hero-cover img {
        width: 100%; height: auto; border-radius: 8px;
        box-shadow: 0 8px 24px rgba(11,114,133,0.2); display: block;
    }
    .bk-hero-body { flex: 1 1 340px; min-width: 280px; }
    .bk-hero-genre {
        font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em;
        color: var(--c-primary); font-weight: 700; margin: 0 0 10px;
    }
    .bk-hero-title {
        font-family: var(--f-heading); font-size: 2.1rem; font-weight: 800;
        color: var(--c-dark); margin: 0 0 10px; line-height: 1.2;
    }
    .bk-hero-subtitle { font-size: 1.08rem; color: #4B5563; margin: 0 0 20px; line-height: 1.5; }
    .bk-hero-ctas { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 8px; }
    .bk-hero-ctas .core-btn--secondary { --core-btn-padding-y: 0.92rem; }

    {{-- #refonte-cta-2026-07-09 : hero compact - nom d'auteur discret + lien texte vers le
         1er bloc CTA (retiré du hero pour laisser "Pourquoi lire" apparaître avant tout achat,
         cf. recherche pp_search 2026-07 : livre conceptuel d'auteur peu connu = argumentaire
         avant vente). Style calqué sur .bk-back-link (même ratio de contraste AAA déjà validé). --}}
    .bk-hero-author { font-size: 0.95rem; color: #4B5563; margin: 0 0 16px; }
    .bk-hero-author strong { color: var(--c-dark); }
    .bk-hero-buy-link {
        display: inline-flex; align-items: center; gap: 6px;
        color: var(--c-primary); font-weight: 600; font-size: 0.92rem;
        text-decoration: underline; text-underline-offset: 3px;
    }
    .bk-hero-buy-link:hover { color: var(--c-accent, #9A2A06); }
    .bk-hero-buy-link:focus-visible {
        outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 2px;
    }

    {{-- Ancre du 1er bloc CTA - ciblée par le lien discret du hero et par le bandeau sticky mobile. --}}
    #bk-cta-primary { scroll-margin-top: 90px; }

    {{-- Bandeau CTA sticky mobile uniquement (<=640px, même seuil que .bk-series-nav-grid
         plus bas sur cette page) : garantit un accès permanent à l'achat malgré le nouvel
         ordre (bénéfices + CTA1 avant le reste). Couleurs #fff sur var(--c-primary) = 9.35:1
         (AAA), même paire déjà validée pour .toc-skip (audit WCAG 2026-07-02). Cible tactile
         pleine largeur, min-height 44px (WCAG 2.5.5 AAA). --}}
    .bk-sticky-cta { display: none; }
    @media (max-width: 640px) {
        .bk-sticky-cta {
            display: block; position: fixed; left: 0; right: 0; bottom: 0; z-index: 1200;
            background: var(--c-primary); padding: 10px 16px;
            padding-bottom: calc(10px + env(safe-area-inset-bottom, 0px));
            box-shadow: 0 -4px 12px rgba(0,0,0,0.18);
        }
        .bk-sticky-cta-link {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            min-height: 44px; text-decoration: none !important; color: #fff;
        }
        .bk-sticky-cta-text {
            font-size: 0.82rem; font-weight: 600; overflow: hidden;
            text-overflow: ellipsis; white-space: nowrap; color: #fff;
        }
        .bk-sticky-cta-action {
            font-family: var(--f-heading); font-weight: 800; font-size: 0.92rem;
            flex-shrink: 0; color: #fff;
        }
        .bk-sticky-cta-link:focus-visible {
            outline: 3px solid #fff; outline-offset: -3px;
        }
        .bk-show-wrapper { padding-bottom: 84px; }
        {{-- Le widget global "Gérer les témoins" (.cc-fab, z-index 9990, fixed bottom-left)
             chevauche notre bandeau sticky à ce seuil - décalage scopé à cette page/breakpoint
             uniquement (:has), zéro couplage avec le composant cookies partagé. --}}
        body:has(.bk-sticky-cta) .cc-fab { bottom: 74px; }
    }

    .bk-proof-banner {
        display: flex; gap: 24px; flex-wrap: wrap; justify-content: center;
        background: #F8FAFC; border: 1px solid #E5E7EB; border-radius: var(--r-base);
        padding: 18px 24px; margin-bottom: 32px; text-align: center;
    }
    .bk-proof-item { font-size: 0.92rem; color: #4B5563; }
    .bk-proof-item strong { color: var(--c-dark); display: block; font-size: 1.05rem; font-family: var(--f-heading); }

    .bk-section { background: #fff; border-radius: var(--r-base); padding: 32px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #E5E7EB; }
    .bk-section-title {
        font-family: var(--f-heading); font-size: 1.35rem; font-weight: 700;
        margin: 0 0 18px; color: var(--c-dark); display: flex; align-items: center; gap: 8px;
    }

    .bk-benefits { list-style: none; margin: 0; padding: 0; }
    .bk-benefits li {
        position: relative; padding-left: 30px; margin-bottom: 14px;
        line-height: 1.6; color: #374151; font-size: 1rem;
    }
    .bk-benefits li::before {
        content: '✓'; position: absolute; left: 0; top: 0; color: var(--c-primary); font-weight: 800;
    }

    .bk-excerpt-box {
        background: #F0FDFA; border-left: 4px solid var(--c-primary);
        border-radius: 6px; padding: 24px; font-style: italic; color: var(--c-dark);
        font-size: 1.05rem; line-height: 1.7;
    }

    .bk-toc-item { border-bottom: 1px solid #E5E7EB; padding: 14px 0; }
    .bk-toc-item:last-child { border-bottom: none; }
    .bk-toc-part { font-weight: 700; color: var(--c-dark); margin: 0 0 4px; font-size: 1rem; }
    .bk-toc-chapters { color: #6B7280; font-size: 0.92rem; margin: 0; line-height: 1.5; }

    .bk-author-box { display: flex; gap: 18px; align-items: center; flex-wrap: wrap; }
    .bk-author-avatar {
        flex: 0 0 64px; width: 64px; height: 64px; border-radius: 50%;
        background: var(--c-primary); color: #fff; display: flex; align-items: center;
        justify-content: center; font-weight: 800; font-size: 1.4rem; font-family: var(--f-heading);
    }
    .bk-author-text { flex: 1 1 240px; color: #4B5563; line-height: 1.6; font-size: 0.98rem; }
    .bk-author-text strong { color: var(--c-dark); }

    .bk-faq-item { border-bottom: 1px solid #E5E7EB; padding: 12px 0; }
    .bk-faq-item summary { font-weight: 600; cursor: pointer; padding: 4px 0; color: var(--c-dark); }
    .bk-faq-item div { padding: 10px 0 4px; color: #4B5563; line-height: 1.6; }

    .bk-cta-block {
        text-align: center; background: linear-gradient(135deg, #F0FDFA 0%, #ECFEFF 100%);
        border: 1px solid #BAE6FD; border-radius: var(--r-base); padding: 36px 24px; margin-top: 8px;
    }
    .bk-cta-block h2 { font-family: var(--f-heading); font-size: 1.4rem; color: var(--c-dark); margin: 0 0 18px; }
    .bk-cta-block .bk-hero-ctas { justify-content: center; }

    .bk-back-link {
        display: inline-flex; align-items: center; margin: 20px 0;
        color: var(--c-primary); font-weight: 600; text-decoration: none; transition: transform 0.2s;
    }
    .bk-back-link:hover { transform: translateX(-5px); text-decoration: none; color: var(--c-primary); }
    .bk-back-link svg { margin-right: 8px; width: 18px; height: 18px; }

    /* Navigation inter-tomes (trilogie Nexus Neural) — même pattern visuel que le bandeau série de /livres. */
    .bk-series-nav {
        background: linear-gradient(135deg, #F0FDFA 0%, #ECFEFF 100%);
        border: 1px solid #BAE6FD; border-radius: var(--r-base); padding: 28px; margin-bottom: 24px;
    }
    .bk-series-nav-title {
        font-family: var(--f-heading); font-size: 1.15rem; font-weight: 800;
        color: var(--c-dark); margin: 0 0 18px; text-align: center;
    }
    .bk-series-nav-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    .bk-series-tome {
        background: #fff; border-radius: 10px; border: 1px solid #E5E7EB;
        padding: 16px; text-align: center; text-decoration: none !important;
        color: inherit; transition: all 0.25s; display: block;
    }
    .bk-series-tome:hover { transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); text-decoration: none; }
    .bk-series-tome img {
        width: 100%; max-width: 110px; height: auto; border-radius: 6px; margin: 0 auto 10px;
        box-shadow: 0 4px 12px rgba(11,114,133,0.15); display: block;
    }
    .bk-series-tome-num {
        display: inline-block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.05em; color: var(--c-primary); margin-bottom: 4px;
    }
    .bk-series-tome-title { font-family: var(--f-heading); font-size: 0.92rem; font-weight: 700; color: var(--c-dark); margin: 0; line-height: 1.3; }
    .bk-series-tome--current {
        border: 2px solid var(--c-primary); box-shadow: 0 0 0 3px rgba(11,114,133,0.08);
        cursor: default; position: relative;
    }
    .bk-series-tome--current:hover { transform: none; box-shadow: 0 0 0 3px rgba(11,114,133,0.08); }
    .bk-series-tome--current .bk-series-tome-num {
        color: var(--c-accent);
    }
    .bk-series-tome--current-badge {
        display: inline-block; margin-top: 6px; font-size: 0.68rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.05em; color: var(--c-accent);
    }

    @media (max-width: 640px) {
        .bk-series-nav-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 767px) {
        .bk-hero { padding: 24px; }
        .bk-hero-title { font-size: 1.6rem; }
        .bk-section { padding: 22px; }
        /* CTA papier/Kindle au-dessus de la ligne de flottaison mobile : le corps (titre + CTA)
           passe avant la couverture dans l'ordre visuel, et la couverture est réduite. Conserve
           l'ordre DOM d'origine (SEO/lecteurs d'écran), seul l'ordre visuel flex change. */
        .bk-hero-body { order: 1; }
        .bk-hero-cover { order: 2; max-width: 190px; }
    }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
<div class="bk-show-wrapper">
    <div class="container">
        <div class="row">
            <div class="col-lg-9 offset-lg-1">

                <a href="{{ route('books.index') }}" class="bk-back-link" aria-label="{{ __('Retour aux livres') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('Retour aux livres') }}
                </a>

                @php
                    $_isFiction = ! empty($book->series_slug);
                    // CTA primaire papier pour les essais, Kindle pour la fiction (lectorat thriller/SF, lecture rapide).
                    $_primaryFormat = $_isFiction ? 'kindle' : 'paperback';
                    $_seriesTomes = $_isFiction
                        ? \Modules\Books\Models\Book::where('series_slug', $book->series_slug)->orderBy('series_position')->get()
                        : collect();
                @endphp

                {{-- Hero --}}
                <div class="bk-hero">
                    <div class="bk-hero-cover">
                        <picture>
                            @php
                                $_webp = $book->cover_image ? preg_replace('/\.jpg$/', '.webp', $book->cover_image) : null;
                            @endphp
                            @if($_webp && file_exists(public_path($_webp)))
                                <source type="image/webp" srcset="{{ asset($_webp) }}">
                            @endif
                            <img
                                src="{{ asset($book->cover_image) }}"
                                alt="{{ $book->title }} - {{ __('couverture du livre par Stéphane Lapointe') }}"
                                loading="eager" decoding="async" width="600" height="903"
                            >
                        </picture>
                    </div>
                    <div class="bk-hero-body">
                        @if($book->genre)
                            <p class="bk-hero-genre">{{ $book->genre }}</p>
                        @endif
                        <h1 class="bk-hero-title">{{ $book->title }}</h1>
                        @if($book->subtitle)
                            <p class="bk-hero-subtitle">{{ $book->subtitle }}</p>
                        @endif

                        <p class="bk-hero-author">{{ __('par') }} <strong>Stéphane Lapointe</strong></p>

                        @if($book->amazon_url_paperback || $book->amazon_url_kindle)
                            <a href="#bk-cta-primary" class="bk-hero-buy-link">{{ __("Voir les options d'achat") }} ↓</a>
                        @endif
                    </div>
                </div>

                {{-- Navigation inter-tomes (trilogie Nexus Neural) --}}
                @if($_isFiction && $_seriesTomes->count() > 1)
                    <div class="bk-series-nav" aria-labelledby="bk-series-nav-title">
                        <h2 class="bk-series-nav-title" id="bk-series-nav-title">{{ __('Trilogie Nexus Neural') }}</h2>
                        <div class="bk-series-nav-grid">
                            @foreach($_seriesTomes as $_tome)
                                @php $_isCurrent = $_tome->id === $book->id; @endphp
                                @if($_isCurrent)
                                    <div class="bk-series-tome bk-series-tome--current" aria-current="page">
                                        <img
                                            src="{{ asset($_tome->cover_image) }}"
                                            alt="{{ $_tome->title }} - {{ __('couverture') }}"
                                            loading="lazy" decoding="async" width="220" height="332"
                                        >
                                        <span class="bk-series-tome-num">{{ __('Tome') }} {{ $_tome->series_position }}</span>
                                        <p class="bk-series-tome-title">{{ $_tome->title }}</p>
                                        <span class="bk-series-tome--current-badge">{{ __('Vous consultez ce tome') }}</span>
                                    </div>
                                @else
                                    <a
                                        href="{{ route('books.show', $_tome->slug) }}"
                                        class="bk-series-tome"
                                        aria-label="{{ __('Voir') }} {{ __('Tome') }} {{ $_tome->series_position }} — {{ $_tome->title }}"
                                    >
                                        <img
                                            src="{{ asset($_tome->cover_image) }}"
                                            alt="{{ $_tome->title }} - {{ __('couverture') }}"
                                            loading="lazy" decoding="async" width="220" height="332"
                                        >
                                        <span class="bk-series-tome-num">{{ __('Tome') }} {{ $_tome->series_position }}</span>
                                        <p class="bk-series-tome-title">{{ $_tome->title }}</p>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- #refonte-cta-2026-07-09 : conteneur scanné par x-fronttheme::table-of-contents
                     (h2 internes -> ancres Pourquoi lire / Acheter / Extrait / Structure / Auteur /
                     FAQ). La navigation inter-tomes ci-dessus et le CTA final plus bas restent
                     volontairement HORS de ce conteneur (pas des sections de lecture). --}}
                <div id="bk-toc-content">

                {{-- Ce que vous allez apprendre / pourquoi lire ce livre --}}
                @if(! empty($book->benefits) && is_array($book->benefits))
                    <div class="bk-section">
                        <h2 class="bk-section-title">✅ {{ $_isFiction ? __('Pourquoi lire ce tome') : __('Ce que vous allez apprendre') }}</h2>
                        <ul class="bk-benefits">
                            @foreach($book->benefits as $benefit)
                                <li>{{ $benefit }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- 1er bloc CTA principal (retiré du hero - cf. bk-hero-buy-link) --}}
                @if($book->amazon_url_paperback || $book->amazon_url_kindle)
                    <div class="bk-cta-block" id="bk-cta-primary">
                        <h2>{{ __('Acheter') }}</h2>
                        <div class="bk-hero-ctas">
                            @if($book->amazon_url_paperback)
                                <x-core::button
                                    :href="$book->amazon_url_paperback"
                                    :variant="$_primaryFormat === 'paperback' ? 'primary' : 'secondary'"
                                    size="lg"
                                    target="_blank"
                                    rel="noopener sponsored"
                                    aria-label="{{ __('Acheter la version papier sur Amazon') }} — {{ $book->title }} ({{ __('nouvel onglet') }})"
                                >
                                    {{ __('Acheter la version papier sur Amazon') }}
                                </x-core::button>
                            @endif
                            @if($book->amazon_url_kindle)
                                <x-core::button
                                    :href="$book->amazon_url_kindle"
                                    :variant="$_primaryFormat === 'kindle' ? 'primary' : 'secondary'"
                                    size="lg"
                                    target="_blank"
                                    rel="noopener sponsored"
                                    aria-label="{{ __('Acheter la version Kindle sur Amazon') }} — {{ $book->title }} ({{ __('nouvel onglet') }})"
                                >
                                    {{ __('Acheter la version Kindle sur Amazon') }}
                                </x-core::button>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Sommaire flottant par ancres - composant DRY existant (réutilisé tel quel,
                     déjà en place sur blog/show et academy/dashboard). --}}
                <x-fronttheme::table-of-contents content-selector="#bk-toc-content" title="{{ __('Sommaire') }}" />

                {{-- Bandeau preuve sobre : faits seulement, pas de faux avis --}}
                @php
                    $_pageCount = null; // Non stocké en base pour l'instant (colonne absente du modèle Book) — omis volontairement plutôt qu'inventé.
                @endphp
                <div class="bk-proof-banner">
                    @if($book->date_published)
                        <div class="bk-proof-item">
                            <strong>{{ $book->date_published->locale('fr_CA')->translatedFormat('j F Y') }}</strong>
                            {{ __('date de publication') }}
                        </div>
                    @endif
                    @if($book->price_paperback)
                        <div class="bk-proof-item">
                            <strong>{{ number_format((float) $book->price_paperback, 2, ',', ' ') }} $ CAD</strong>
                            {{ __('broché') }}
                        </div>
                    @endif
                    @if($book->price_kindle)
                        <div class="bk-proof-item">
                            <strong>{{ number_format((float) $book->price_kindle, 2, ',', ' ') }} $ CAD</strong>
                            Kindle
                        </div>
                    @endif
                    @if($_isFiction && $book->series_position)
                        <div class="bk-proof-item">
                            <strong>{{ __('Tome') }} {{ $book->series_position }}</strong>
                            {{ __('trilogie Nexus Neural') }}
                        </div>
                    @endif
                </div>

                {{-- Extrait --}}
                @if(! empty($book->excerpt))
                    <div class="bk-section">
                        <h2 class="bk-section-title">📖 {{ __('Extrait') }}</h2>
                        <div class="bk-excerpt-box">{{ $book->excerpt }}</div>
                    </div>
                @endif

                {{-- Structure / table des matières --}}
                @if(! empty($book->toc_summary) && is_array($book->toc_summary))
                    <div class="bk-section">
                        <h2 class="bk-section-title">📑 {{ __('Structure du livre') }}</h2>
                        @foreach($book->toc_summary as $part)
                            @if(is_array($part) && ! empty($part['part']))
                                <div class="bk-toc-item">
                                    <p class="bk-toc-part">{{ $part['part'] }}</p>
                                    @if(! empty($part['chapters']))
                                        <p class="bk-toc-chapters">{{ $part['chapters'] }}</p>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- À propos de l'auteur --}}
                @if(! empty($book->author_bio_short))
                    <div class="bk-section">
                        <h2 class="bk-section-title">✍️ {{ __("À propos de l'auteur") }}</h2>
                        <div class="bk-author-box">
                            <div class="bk-author-avatar" aria-hidden="true">SL</div>
                            <p class="bk-author-text"><strong>Stéphane Lapointe</strong> — {{ $book->author_bio_short }}</p>
                        </div>
                    </div>
                @endif

                {{-- FAQ (AEO/GEO) --}}
                @if(! empty($book->faq) && is_array($book->faq))
                    <div class="bk-section">
                        <h2 class="bk-section-title">❓ {{ __('Questions fréquentes') }}</h2>
                        @foreach($book->faq as $qa)
                            @if(is_array($qa) && ! empty($qa['question']) && ! empty($qa['answer']))
                                <details class="bk-faq-item">
                                    <summary>{{ $qa['question'] }}</summary>
                                    <div>{{ $qa['answer'] }}</div>
                                </details>
                            @endif
                        @endforeach
                    </div>
                @endif

                </div>{{-- /#bk-toc-content --}}

                {{-- 2e bloc CTA (final) --}}
                <div class="bk-cta-block">
                    <h2>{{ __('Envie de le lire ?') }}</h2>
                    <div class="bk-hero-ctas">
                        @if($book->amazon_url_paperback)
                            <x-core::button
                                :href="$book->amazon_url_paperback"
                                :variant="$_primaryFormat === 'paperback' ? 'primary' : 'secondary'"
                                size="lg"
                                target="_blank"
                                rel="noopener sponsored"
                                aria-label="{{ __('Acheter la version papier sur Amazon') }} — {{ $book->title }} ({{ __('nouvel onglet') }})"
                            >
                                {{ __('Acheter la version papier sur Amazon') }}
                            </x-core::button>
                        @endif
                        @if($book->amazon_url_kindle)
                            <x-core::button
                                :href="$book->amazon_url_kindle"
                                :variant="$_primaryFormat === 'kindle' ? 'primary' : 'secondary'"
                                size="lg"
                                target="_blank"
                                rel="noopener sponsored"
                                aria-label="{{ __('Acheter la version Kindle sur Amazon') }} — {{ $book->title }} ({{ __('nouvel onglet') }})"
                            >
                                {{ __('Acheter la version Kindle sur Amazon') }}
                            </x-core::button>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- CTA sticky mobile (<=640px) : accès permanent à l'achat malgré le nouvel ordre. --}}
@php
    $_stickyUrl = $_primaryFormat === 'paperback'
        ? ($book->amazon_url_paperback ?: $book->amazon_url_kindle)
        : ($book->amazon_url_kindle ?: $book->amazon_url_paperback);
@endphp
@if($_stickyUrl)
    <div class="bk-sticky-cta">
        <a
            href="#bk-cta-primary"
            class="bk-sticky-cta-link"
            aria-label="{{ __('Voir les options d\'achat') }} — {{ $book->title }}"
        >
            <span class="bk-sticky-cta-text">{{ $book->title }}</span>
            <span class="bk-sticky-cta-action">{{ __('Acheter') }} →</span>
        </a>
    </div>
@endif
</section>
@endsection

@push('scripts')
<script type="application/ld+json">{!! json_encode(\Modules\Books\Services\BookSchemaService::buildGraph($book), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
@endpush
