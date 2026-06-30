<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $sigle . ' — Désambiguïsation — ' . config('app.name'))
@section('meta_description', 'Le sigle ' . $sigle . ' a plusieurs significations. Choisissez la définition qui vous correspond.')
@section('og_type', 'article')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $sigle,
        'breadcrumbItems' => [__('Acronymes éducation'), $sigle],
    ])
@endsection

@push('styles')
<style>
    .acr-disamb-wrapper { padding: 10px 0 60px; min-height: 60vh; }

    .acr-disamb-back {
        display: inline-flex; align-items: center; margin: 20px 0;
        color: var(--c-primary); font-weight: 600; text-decoration: none; transition: transform 0.2s;
    }
    .acr-disamb-back:hover { transform: translateX(-5px); text-decoration: none; color: var(--c-primary); }
    .acr-disamb-back svg { margin-right: 8px; width: 18px; height: 18px; }

    .acr-disamb-header {
        text-align: center; margin-bottom: 36px; padding: 32px 20px 0;
    }
    .acr-disamb-sigle {
        font-family: var(--f-heading); font-size: 2.8rem; font-weight: 900;
        color: var(--c-primary); margin: 0 0 8px; line-height: 1.1;
    }
    .acr-disamb-subtitle {
        font-size: 1.15rem; color: #6B7280; margin: 0 0 6px; max-width: 540px; margin-left: auto; margin-right: auto;
    }
    .acr-disamb-count {
        display: inline-block; background: #F0F9FF; border: 1px solid #BAE6FD;
        color: #0369A1; border-radius: 9999px; padding: 4px 14px;
        font-size: 0.9rem; font-weight: 600; margin-top: 4px;
    }

    /* Grille Bootstrap + responsive */
    .acr-disamb-grid { margin-top: 8px; }

    /* Carte de désambiguïsation */
    .acr-disamb-card {
        display: flex; flex-direction: column; justify-content: space-between;
        background: #fff; border-radius: var(--r-base); padding: 28px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #E5E7EB;
        border-top: 4px solid var(--c-primary); height: 100%;
        transition: box-shadow 0.25s, transform 0.2s;
        margin-bottom: 24px;
    }
    .acr-disamb-card:hover { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12); transform: translateY(-2px); }

    .acr-disamb-icon { font-size: 2.2rem; line-height: 1; margin-bottom: 10px; }
    .acr-disamb-card-sigle {
        font-family: var(--f-heading); font-size: 1.4rem; font-weight: 800;
        color: var(--c-primary); margin: 0 0 4px;
    }
    .acr-disamb-card-full {
        font-size: 1.0rem; color: var(--c-dark); font-weight: 600;
        margin: 0 0 10px; line-height: 1.4;
    }
    .acr-disamb-card-badge {
        display: inline-block; padding: 3px 10px; border-radius: 50px;
        font-size: 0.78rem; font-weight: 600; color: #fff; margin-bottom: 10px;
        background: var(--c-primary);
    }
    .acr-disamb-card-excerpt {
        font-size: 0.95rem; color: #4B5563; line-height: 1.6; margin-bottom: 18px;
        flex: 1;
    }
    .acr-disamb-card-link {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--c-primary); color: #fff; padding: 9px 18px;
        border-radius: var(--r-btn); font-weight: 600; font-size: 0.9rem;
        text-decoration: none; transition: background 0.2s; align-self: flex-start;
    }
    .acr-disamb-card-link:hover { background: var(--c-dark); color: #fff; text-decoration: none; }
    .acr-disamb-card-link svg { width: 15px; height: 15px; }

    .acr-disamb-info {
        background: #F8FAFC; border: 1px solid #E5E7EB; border-radius: var(--r-base);
        padding: 16px 20px; margin-top: 32px; font-size: 0.9rem; color: #6B7280;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="acr-disamb-wrapper">
    <div class="container">

        {{-- Lien retour --}}
        <div class="row">
            <div class="col-xs-12">
                <a href="{{ route('acronyms.index') }}" class="acr-disamb-back" aria-label="{{ __('Retour aux acronymes') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('Retour aux acronymes') }}
                </a>
            </div>
        </div>

        {{-- En-tête --}}
        <div class="row">
            <div class="col-xs-12">
                <header class="acr-disamb-header">
                    <p class="acr-disamb-sigle">{{ $sigle }}</p>
                    <h1 class="acr-disamb-subtitle">
                        Ce sigle a plusieurs significations. Choisissez celle qui correspond à votre recherche.
                    </h1>
                    <span class="acr-disamb-count">{{ $fiches->count() }} définitions disponibles</span>
                </header>
            </div>
        </div>

        {{-- Grille des fiches --}}
        <div class="row acr-disamb-grid">
            @foreach($fiches as $fiche)
                @php
                    $ficheLocale = app()->getLocale();
                    $ficheSlug = $fiche->getTranslation('slug', $ficheLocale, false) ?: $fiche->slug;
                    $ficheFull = $fiche->getTranslation('full_name', $ficheLocale, false) ?: $fiche->full_name;
                    $ficheDesc = $fiche->getTranslation('one_sentence_answer', $ficheLocale, false)
                        ?: $fiche->getTranslation('description', $ficheLocale, false)
                        ?: $fiche->description;
                    $ficheExcerpt = \Illuminate\Support\Str::limit(strip_tags((string) $ficheDesc), 160);
                    $ficheIcon = $fiche->icon ?: '🎓';
                    $ficheUrl = route('acronyms.show', $ficheSlug);
                    $ficheCat = $fiche->category;
                    $ficheCatColor = $ficheCat ? ($ficheCat->color ?? 'var(--c-primary)') : 'var(--c-primary)';
                @endphp
                <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                    <article class="acr-disamb-card">
                        <div>
                            <div class="acr-disamb-icon" aria-hidden="true">{{ $ficheIcon }}</div>
                            <p class="acr-disamb-card-sigle">{{ $fiche->acronym }}</p>
                            <p class="acr-disamb-card-full">{{ $ficheFull }}</p>

                            @if($ficheCat)
                                <span class="acr-disamb-card-badge" style="background: {{ $ficheCatColor }};">
                                    {{ $ficheCat->icon }} {{ $ficheCat->name }}
                                </span>
                            @elseif($fiche->domain)
                                <span class="acr-disamb-card-badge">{{ $fiche->domain }}</span>
                            @endif

                            @if($ficheExcerpt)
                                <p class="acr-disamb-card-excerpt">{{ $ficheExcerpt }}</p>
                            @endif
                        </div>

                        <a href="{{ $ficheUrl }}"
                           class="acr-disamb-card-link"
                           aria-label="{{ __('Voir la définition de') }} {{ $fiche->acronym }} — {{ $ficheFull }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            {{ __('Voir la définition') }}
                        </a>
                    </article>
                </div>
            @endforeach
        </div>

        {{-- Note informative --}}
        <div class="row">
            <div class="col-xs-12">
                <div class="acr-disamb-info">
                    Vous cherchez un autre sigle ? <a href="{{ route('acronyms.index') }}">Consulter tous les acronymes de l'éducation</a>.
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
{{-- Schema.org JSON-LD : ItemList (désambiguïsation) + BreadcrumbList --}}
@php
    $_items = [];
    $__pos = 1;
    foreach ($fiches as $__fiche) {
        $__loc = app()->getLocale();
        $__slug = $__fiche->getTranslation('slug', $__loc, false) ?: $__fiche->slug;
        $__full = $__fiche->getTranslation('full_name', $__loc, false) ?: $__fiche->full_name;
        $_items[] = [
            '@type' => 'ListItem',
            'position' => $__pos++,
            'name' => $sigle . ' — ' . $__full,
            'url' => route('acronyms.show', $__slug),
        ];
    }
    $_itemList = [
        '@type' => 'ItemList',
        'name' => $sigle . ' — Désambiguïsation',
        'description' => 'Le sigle ' . $sigle . ' a ' . $fiches->count() . ' significations dans le domaine de l\'éducation au Québec.',
        'itemListElement' => $_items,
    ];
    $_breadcrumb = [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('Accueil'), 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __('Acronymes éducation'), 'item' => route('acronyms.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $sigle],
        ],
    ];
    $_jsonLd = ['@context' => 'https://schema.org', '@graph' => [$_itemList, $_breadcrumb]];
@endphp
<script type="application/ld+json">{!! json_encode($_jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
