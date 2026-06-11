<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $acronym->acronym . ' - ' . $acronym->full_name . ' - ' . config('app.name'))
@section('meta_description', __('Signification de') . ' ' . $acronym->acronym . ' : ' . $acronym->full_name . '. ' . Str::limit(strip_tags($acronym->one_sentence_answer ?: $acronym->description), 120))
@section('og_type', 'article')
@if(!empty($acronym->logo_url))
    @section('og_image', str_starts_with($acronym->logo_url, 'http') ? $acronym->logo_url : url($acronym->logo_url).'?v='.($acronym->updated_at?->timestamp ?? '0'))
@endif

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $acronym->acronym,
        'breadcrumbItems' => [__('Acronymes éducation'), $acronym->acronym]
    ])
@endsection

@auth
@include('core::components.admin-bar', [
    'label' => __('Acronyme admin'),
    'actions' => array_filter([
        Route::has('admin.acronyms.edit') ? ['label' => __('Éditer'), 'icon' => 'pencil', 'url' => route('admin.acronyms.edit', $acronym->id)] : null,
        ['divider' => true],
        Route::has('admin.acronyms.destroy') ? ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.acronyms.destroy', $acronym->id), 'method' => 'DELETE', 'confirm' => __('Supprimer cet acronyme ?'), 'danger' => true] : null,
    ]),
])
@if(Route::has('admin.acronyms.edit'))
    @include('core::components.mode-toggle', ['editUrl' => route('admin.acronyms.edit', $acronym->id)])
@endif
@include('core::components.admin-activity-mini', ['model' => $acronym])
@endauth

{{-- Meta AEO/LLM-first 2026 : aide les crawlers IA à citer l'acronyme --}}
@push('head')
<meta name="llm:summary" content="{{ e($acronym->acronym) }} = {{ e($acronym->full_name) }}. {{ e(Str::limit(strip_tags($acronym->one_sentence_answer ?: ($acronym->description ?? '')), 180)) }} (Acronyme éducation Québec)">
<meta name="llm:keywords" content="{{ e($acronym->acronym) }}, {{ e($acronym->full_name) }}, acronyme éducation, sigle, Québec">
<meta name="llm:url" content="{{ route('acronyms.show', $acronym->slug) }}">
@endpush

@push('styles')
<style>
    .acr-show-wrapper { padding: 10px 0 60px; min-height: 60vh; }

    .acr-show-back {
        display: inline-flex; align-items: center; margin: 20px 0;
        color: var(--c-primary); font-weight: 600; text-decoration: none; transition: transform 0.2s;
    }
    .acr-show-back:hover { transform: translateX(-5px); text-decoration: none; color: var(--c-primary); }
    .acr-show-back svg { margin-right: 8px; width: 18px; height: 18px; }

    /* Carte principale pleine largeur (calque glossaire) */
    .acr-show-card {
        background: #fff; border-radius: var(--r-base);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        padding: 40px 45px; margin: 0 auto 50px;
        border-top: 4px solid var(--c-primary);
    }

    /* En-tête centré : logo/icône, titre, sous-titre, badges */
    .acr-show-icon { font-size: 3rem; line-height: 1; margin-bottom: 10px; text-align: center; }
    .acr-show-logo-wrap { text-align: center; margin-bottom: 14px; }
    .acr-show-logo {
        height: 90px; width: auto; max-width: 240px; display: inline-flex; align-items: center; justify-content: center;
        padding: 6px 12px; border-radius: var(--r-base); overflow: hidden; background: #F9FAFB; border: 1px solid #E5E7EB;
    }
    /* Respecte le ratio natif du logo (hauteur fixe, largeur auto) → pas de déformation des logos non carrés */
    .acr-show-logo img { height: 100%; width: auto; max-width: 100%; object-fit: contain; display: block; }
    .acr-show-h1 {
        font-family: var(--f-heading); font-size: 2.4rem; font-weight: 800;
        color: var(--c-primary); margin: 0 0 10px; line-height: 1.2; text-align: center;
    }
    .acr-show-h2 {
        font-size: 1.15rem; color: #6B7280; margin: 0 0 14px; font-weight: 400;
        line-height: 1.4; text-align: center;
    }
    .acr-show-aka {
        text-align: center; color: #6B7280; font-size: 0.95rem; font-style: italic;
        margin: 4px 0 14px; letter-spacing: 0.02em;
    }
    .acr-show-aka .acr-aka-label { font-weight: 500; font-style: normal; color: #9CA3AF; }

    .acr-show-badges { display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 8px; margin: 16px 0 6px; }
    .acr-show-badge {
        padding: 6px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 600;
        color: #fff; display: inline-flex; align-items: center; gap: 4px; line-height: 1;
    }
    .acr-badge-diff {
        padding: 6px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 700;
        text-transform: uppercase; color: #fff; display: inline-flex; align-items: center; line-height: 1;
    }
    .acr-diff-beginner { background: #10B981; }
    .acr-diff-intermediate { background: #F59E0B; }
    .acr-diff-advanced { background: #EF4444; }
    .acr-show-updated { text-align: center; color: #9ca3af; font-size: 0.82rem; margin: 8px 0 20px; font-style: italic; }

    /* Phrase-réponse (answer-first AEO) */
    .acr-answer-box {
        background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%);
        border-left: 4px solid var(--c-primary); padding: 18px 24px; border-radius: 6px;
        margin-bottom: 22px;
    }
    .acr-answer-box p { font-size: 1.12rem; font-weight: 600; color: var(--c-dark); margin: 0; line-height: 1.55; }

    /* Boîtes de section + bento grid (calque glossaire) */
    .acr-section { margin-bottom: 0; }
    .acr-section-box { border-radius: var(--r-base); padding: 20px; border-left: 4px solid transparent; }
    .acr-section-title {
        font-family: var(--f-heading); font-size: 1.2rem; font-weight: 700;
        margin: 0 0 12px; display: flex; align-items: center; gap: 8px;
    }
    .acr-def-box { background: #F8FAFC; border-left-color: var(--c-primary); padding: 28px; margin-bottom: 24px; }
    .acr-def-box .acr-section-title { color: var(--c-dark); font-size: 1.3rem; }
    .acr-def-text { font-size: 1.05rem; line-height: 1.8; color: #4B5563; }
    .acr-def-text p { margin: 0 0 12px; }
    .acr-def-text p:last-child { margin-bottom: 0; }

    .acr-bento { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 28px; }
    @media (min-width: 768px) {
        .acr-bento { grid-template-columns: 1fr 1fr; }
        .acr-bento-full { grid-column: 1 / -1; }
    }

    .acr-box-analogy { background: #EEF7FF; border-left-color: var(--c-primary); }
    .acr-box-analogy .acr-section-title { color: var(--c-primary); }
    .acr-box-analogy p { font-size: 1.1rem; line-height: 1.6; color: var(--c-dark); margin: 0; }

    .acr-box-example { background: #F0FFF4; border-left-color: #10B981; }
    .acr-box-example .acr-section-title { color: #065F46; }
    .acr-box-example p { color: #065F46; margin: 0; line-height: 1.6; }

    .acr-box-fact { background: #FFFBE6; border-left-color: #F59E0B; }
    .acr-box-fact .acr-section-title { color: #92400E; }
    .acr-box-fact p { color: #92400E; font-style: italic; margin: 0; line-height: 1.6; }

    .acr-box-link { background: #fff; }

    /* Chips de maillage (catégorie parente / sous-acronymes) */
    .acr-kg-box { background: #F0FAFB; border-left-color: var(--c-primary); }
    .acr-kg-box .acr-section-title { color: #0B7285; }
    .acr-kg-group-label { margin: 0 0 8px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6B7280; }
    .acr-kg-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #fff;
        border: 1px solid #BAE6FD; border-radius: 9999px; color: #075985; font-weight: 600;
        font-size: 0.9rem; text-decoration: none; margin: 4px 6px 4px 0; transition: all 0.2s;
    }
    .acr-kg-chip:hover { background: #0B7285; color: #fff; border-color: #0B7285; text-decoration: none; }

    /* Site officiel */
    .acr-show-website {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--c-primary); color: #fff; padding: 10px 20px;
        border-radius: var(--r-btn); font-weight: 600; text-decoration: none; transition: background 0.2s;
    }
    .acr-show-website:hover { background: var(--c-dark); color: #fff; text-decoration: none; }

    .acr-show-share { margin-top: 32px; padding-top: 20px; border-top: 1px solid #eee; }

    /* Acronymes similaires (grille bas de page, calque glossaire) */
    .acr-show-related-title {
        font-family: var(--f-heading); font-size: 1.4rem; font-weight: 700;
        margin-bottom: 20px; color: var(--c-dark); padding-left: 14px; position: relative;
    }
    .acr-show-related-title::before {
        content: ''; position: absolute; left: 0; top: 4px; bottom: 4px;
        width: 4px; background: var(--c-primary); border-radius: 2px;
    }
    .acr-show-related-card {
        display: flex; flex-direction: column; justify-content: space-between;
        background: #fff; border-radius: var(--r-base); padding: 20px; height: 100%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #E5E7EB; transition: all 0.25s;
        text-decoration: none !important; color: inherit; margin-bottom: 20px;
    }
    .acr-show-related-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); border-color: var(--c-primary); }
    .acr-show-related-acr { font-family: var(--f-heading); font-weight: 700; color: var(--c-primary); font-size: 1.05rem; }
    .acr-show-related-name { font-size: 0.85rem; color: #6B7280; line-height: 1.4; margin-top: 6px; }

    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*='col-'] { display: flex; flex-direction: column; }

    @media (max-width: 767px) {
        .acr-show-card { padding: 24px 20px; }
        .acr-show-h1 { font-size: 1.8rem; }
    }
</style>
@endpush

@section('content')
<div class="acr-show-wrapper">
    <div class="container">

        {{-- Back link --}}
        <div class="row">
            <div class="col-xs-12">
                <a href="{{ route('acronyms.index') }}" class="acr-show-back" aria-label="{{ __('Retour aux acronymes') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('Retour aux acronymes') }}
                </a>
            </div>
        </div>

        {{-- Suggest edit + Report + admin actions --}}
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @include('fronttheme::partials.suggest-edit', [
                'model' => $acronym,
                'route' => route('acronyms.suggestions.store', $acronym->getTranslation('slug', app()->getLocale())),
            ])
            @if(Route::has('directory.community.report'))
                @include('core::components.report-modal', [
                    'reportUrl' => route('directory.community.report', ['type' => 'acronym', 'id' => $acronym->id]),
                    'csrfToken' => csrf_token(),
                ])
            @endif
            @include('core::components.admin-actions', ['item' => $acronym, 'type' => 'acronyms'])
        </div>

        {{-- Main card (pleine largeur, calque glossaire) --}}
        <div class="row">
            <div class="col-xs-12">
                <article class="acr-show-card">

                    {{-- En-tête : logo (si présent) sinon icône emoji sinon 🎓 --}}
                    @if($acronym->logo_url)
                        <div class="acr-show-logo-wrap">
                            <span class="acr-show-logo">
                                <img src="{{ str_starts_with($acronym->logo_url, 'http') ? $acronym->logo_url : asset($acronym->logo_url) }}" alt="{{ __('Logo') }} {{ $acronym->acronym }}" height="90" loading="lazy">
                            </span>
                        </div>
                    @else
                        <div class="acr-show-icon">{{ $acronym->icon ?: '🎓' }}</div>
                    @endif

                    {{-- Titre (acronyme) --}}
                    <h1 class="acr-show-h1" data-editable="acronym">{{ $acronym->acronym }}</h1>

                    {{-- Sous-titre (nom complet) --}}
                    @if($acronym->full_name)
                        <h2 class="acr-show-h2">{{ $acronym->full_name }}</h2>
                    @endif

                    {{-- Aussi appelé (aliases) --}}
                    @php
                        $_variants = [];
                        if (is_array($acronym->aliases)) {
                            foreach ($acronym->aliases as $_a) {
                                if (! is_string($_a)) continue;
                                $_a = trim($_a);
                                if (! $_a) continue;
                                if (mb_strtolower($_a) === mb_strtolower((string) $acronym->acronym)) continue;
                                if (mb_strtolower($_a) === mb_strtolower((string) $acronym->full_name)) continue;
                                if (in_array($_a, $_variants, true)) continue;
                                $_variants[] = $_a;
                            }
                        }
                    @endphp
                    @if(! empty($_variants))
                        <p class="acr-show-aka">
                            <span class="acr-aka-label">{{ __('Aussi appelé') }} :</span>
                            {{ implode(' · ', $_variants) }}
                        </p>
                    @endif

                    {{-- Badges : catégorie + difficulté (si présente) --}}
                    @if($acronym->category || ! empty($acronym->difficulty))
                        <div class="acr-show-badges">
                            @if($acronym->category)
                                <span class="acr-show-badge" style="background: {{ $acronym->category->color ?? 'var(--c-primary)' }};">
                                    {{ $acronym->category->icon }} {{ $acronym->category->name }}
                                </span>
                            @endif
                            @if(! empty($acronym->difficulty))
                                @php
                                    $_diffLabel = match($acronym->difficulty) {
                                        'beginner' => __('Débutant'),
                                        'intermediate' => __('Intermédiaire'),
                                        'advanced' => __('Avancé'),
                                        default => null,
                                    };
                                @endphp
                                @if($_diffLabel)
                                    <span class="acr-badge-diff acr-diff-{{ $acronym->difficulty }}">{{ $_diffLabel }}</span>
                                @endif
                            @endif
                        </div>
                    @endif

                    {{-- Vote communautaire (si module Voting actif) --}}
                    @if(trait_exists(\Modules\Voting\Traits\HasCommunityVotes::class))
                        <div style="text-align:center; margin: 10px 0 4px;">
                            @include('voting::components.vote-button', ['item' => $acronym, 'type' => 'acronym'])
                        </div>
                    @endif

                    {{-- Date de mise à jour (signal freshness GEO 2026) --}}
                    @if($acronym->updated_at)
                        <p class="acr-show-updated">
                            {{ __('Mis à jour le') }} <time datetime="{{ $acronym->updated_at->toDateString() }}">{{ $acronym->updated_at->locale('fr_CA')->translatedFormat('j F Y') }}</time>
                        </p>
                    @endif

                    {{-- Barre d'action (Sauvegarder/Copier/Signaler + bouton Admin superadmin) — en haut, comme glossaire/actualités --}}
                    <div class="acr-show-actionbar" style="margin: 0.5rem 0 1.25rem;">
                        @include('fronttheme::partials.article-action-bar', ['model' => $acronym, 'modelType' => 'Modules\\Acronyms\\Models\\Acronym', 'adminShareItems' => auth()->user()?->isSuperAdmin() ? $acronym->adminShareContents() : null])
                    </div>

                    {{-- Answer-first (AEO) : phrase-réponse ≤40 mots --}}
                    @if(! empty($acronym->one_sentence_answer))
                        <div class="acr-answer-box">
                            <p>{{ $acronym->one_sentence_answer }}</p>
                        </div>
                    @endif

                    {{-- Définition vedette (description) –pleine largeur, toujours présente --}}
                    <div class="acr-section">
                        <div class="acr-section-box acr-def-box">
                            <h2 class="acr-section-title">📖 {{ __('Définition') }}</h2>
                            <div class="acr-def-text">
                                @if($acronym->description)
                                    @php
                                        $descText = e($acronym->description);
                                        $sentences = preg_split('/(?<=[.!?])\s+(?=[A-ZÀ-ÿ])/', $descText);
                                        $paragraphs = array_chunk($sentences, 2);
                                    @endphp
                                    @foreach($paragraphs as $para)
                                        <p>{!! implode(' ', $para) !!}</p>
                                    @endforeach
                                @else
                                    <p style="color:#6B7280; font-style:italic; margin:0;">{{ __('Aucune description détaillée disponible pour cet acronyme.') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Bento grid : analogie + exemple + le saviez-vous (chaque bloc conditionnel) --}}
                    @if(! empty($acronym->analogy) || ! empty($acronym->example) || ! empty($acronym->did_you_know) || ! empty($acronym->reference_url) || (! empty($acronym->faq) && is_array($acronym->faq)) || (! empty($acronym->sources) && is_array($acronym->sources)) || ! empty($acronym->broader_slugs) || ! empty($acronym->narrower_slugs))
                    <div class="acr-bento">

                        {{-- En termes simples (analogie) --}}
                        @if(! empty($acronym->analogy))
                            <div class="acr-section">
                                <div class="acr-section-box acr-box-analogy" style="height:100%;">
                                    <h2 class="acr-section-title">💬 {{ __('En termes simples') }}</h2>
                                    <p>{!! nl2br(e($acronym->analogy)) !!}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Exemple concret --}}
                        @if(! empty($acronym->example))
                            <div class="acr-section">
                                <div class="acr-section-box acr-box-example" style="height:100%;">
                                    <h2 class="acr-section-title">🎯 {{ __('Exemple concret') }}</h2>
                                    <p>{!! nl2br(e($acronym->example)) !!}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Le saviez-vous ? –pleine largeur --}}
                        @if(! empty($acronym->did_you_know))
                            <div class="acr-section acr-bento-full">
                                <div class="acr-section-box acr-box-fact">
                                    <h2 class="acr-section-title">💡 {{ __('Le saviez-vous ?') }}</h2>
                                    <p>{!! nl2br(e($acronym->did_you_know)) !!}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Pour aller plus loin : référence externe --}}
                        @if(! empty($acronym->reference_url))
                            <div class="acr-section acr-bento-full">
                                <div class="acr-section-box acr-box-link">
                                    <h2 class="acr-section-title">📖 {{ __('Pour aller plus loin') }}</h2>
                                    <p><a href="{{ $acronym->reference_url }}" target="_blank" rel="noopener noreferrer">{{ $acronym->reference_label ?: __('Lire l\'article complet') }}</a></p>
                                </div>
                            </div>
                        @endif

                        {{-- FAQ (AEO/GEO) : Q&R structurées + FAQPage Schema --}}
                        @if(! empty($acronym->faq) && is_array($acronym->faq))
                            <div class="acr-section acr-bento-full">
                                <div class="acr-section-box acr-box-link">
                                    <h2 class="acr-section-title">❓ {{ __('Questions fréquentes') }}</h2>
                                    @foreach($acronym->faq as $qa)
                                        @if(is_array($qa) && ! empty($qa['question']) && ! empty($qa['answer']))
                                            <details style="border-bottom: 1px solid #e5e7eb; padding: 12px 0;">
                                                <summary style="font-weight: 600; cursor: pointer; padding: 4px 0; color: var(--c-dark);">{{ $qa['question'] }}</summary>
                                                <div style="padding: 10px 0 4px; color: #4B5563; line-height: 1.6;">{!! nl2br(e($qa['answer'])) !!}</div>
                                            </details>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Sources externes vérifiables (GEO + EEAT) --}}
                        @if(! empty($acronym->sources) && is_array($acronym->sources))
                            <div class="acr-section acr-bento-full">
                                <div class="acr-section-box acr-box-link">
                                    <h2 class="acr-section-title">📚 {{ __('Sources') }}</h2>
                                    <ul style="list-style: none; padding: 0; margin: 0;">
                                        @foreach($acronym->sources as $src)
                                            @if(is_array($src) && ! empty($src['label']) && ! empty($src['url']))
                                                <li style="margin-bottom: 10px; line-height: 1.5;">
                                                    <a href="{{ $src['url'] }}" target="_blank" rel="noopener noreferrer">{{ $src['label'] }}</a>
                                                    @if(! empty($src['author']) || ! empty($src['year']))
                                                        <span style="color: #6B7280; font-size: 0.9rem;">
                                                            ({{ trim(($src['author'] ?? '').(! empty($src['author']) && ! empty($src['year']) ? ', ' : '').($src['year'] ?? '')) }})
                                                        </span>
                                                    @endif
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Maillage : acronymes liés (broader/narrower) en chips --}}
                        @php
                            $_broaderSlugs = is_array($acronym->broader_slugs) ? array_filter($acronym->broader_slugs, 'is_string') : [];
                            $_narrowerSlugs = is_array($acronym->narrower_slugs) ? array_filter($acronym->narrower_slugs, 'is_string') : [];
                            $_kgSlugs = array_values(array_unique(array_merge($_broaderSlugs, $_narrowerSlugs)));
                            $_locale = app()->getLocale();
                            $_kgAcronyms = ! empty($_kgSlugs)
                                ? \Modules\Acronyms\Models\Acronym::query()
                                    ->whereIn('slug->'.$_locale, $_kgSlugs)
                                    ->where('is_published', 1)
                                    ->get(['id', 'slug', 'acronym', 'full_name', 'icon'])
                                    ->keyBy(fn ($a) => (string) $a->getTranslation('slug', $_locale, false))
                                : collect();
                            $_renderKgChip = function ($a) {
                                $url = route('acronyms.show', $a->getTranslation('slug', app()->getLocale(), false));
                                $icon = $a->icon ?: '🔤';
                                $label = e((string) $a->acronym);
                                $aria = e(__('Voir la signification de').' '.((string) $a->acronym));
                                return '<a href="'.e($url).'" class="acr-kg-chip" aria-label="'.$aria.'"><span aria-hidden="true">'.$icon.'</span><span>'.$label.'</span></a>';
                            };
                        @endphp
                        @if($_kgAcronyms->isNotEmpty())
                            <div class="acr-section acr-bento-full">
                                <div class="acr-section-box acr-kg-box">
                                    <h2 class="acr-section-title">🔗 {{ __('Acronymes liés') }}</h2>

                                    @if(! empty($_broaderSlugs))
                                        @php
                                            $_broaderChips = collect($_broaderSlugs)
                                                ->filter(fn ($s) => $_kgAcronyms->has((string) $s))
                                                ->map(fn ($s) => $_renderKgChip($_kgAcronyms->get((string) $s)))
                                                ->implode('');
                                        @endphp
                                        @if($_broaderChips)
                                            <div style="margin-bottom: 14px;">
                                                <p class="acr-kg-group-label">🏷️ {{ __('Catégorie parente') }}</p>
                                                <div>{!! $_broaderChips !!}</div>
                                            </div>
                                        @endif
                                    @endif

                                    @if(! empty($_narrowerSlugs))
                                        @php
                                            $_narrowerChips = collect($_narrowerSlugs)
                                                ->filter(fn ($s) => $_kgAcronyms->has((string) $s))
                                                ->map(fn ($s) => $_renderKgChip($_kgAcronyms->get((string) $s)))
                                                ->implode('');
                                        @endphp
                                        @if($_narrowerChips)
                                            <div>
                                                <p class="acr-kg-group-label">🌿 {{ __('Sous-acronymes') }}</p>
                                                <div>{!! $_narrowerChips !!}</div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endif

                    </div>
                    @endif

                    {{-- Site officiel --}}
                    @if($acronym->website_url)
                        <div style="margin-bottom: 24px;">
                            <a href="{{ $acronym->website_url }}" target="_blank" rel="noopener noreferrer" class="acr-show-website">
                                🌐 {{ __('Visiter le site officiel') }}
                            </a>
                        </div>
                    @endif

                    {{-- Partage social (la barre d'action + bouton Admin sont remontés en haut) --}}
                    <div class="acr-show-share">
                        @php
                            $acronymCode = $acronym->getTranslation('acronym', 'fr_CA', false) ?: $acronym->acronym;
                            $acronymFull = $acronym->getTranslation('full_name', 'fr_CA', false) ?: $acronym->full_name;
                            $acronymTitle = $acronymCode . ' — ' . $acronymFull;
                            $acronymSections = [];
                            if ($acronym->category) {
                                $acronymSections[] = ['icon' => '📘', 'label' => 'Catégorie', 'content' => $acronym->category->name];
                            }
                            if ($acronym->domain) {
                                $acronymSections[] = ['icon' => '🌐', 'label' => 'Domaine', 'content' => $acronym->domain];
                            }
                        @endphp
                        <div style="margin-top: 0.5rem;">
                            <x-core::smart-share
                                :title="$acronymTitle"
                                :summary="$acronym->getTranslation('one_sentence_answer', 'fr_CA', false) ?: ($acronym->getTranslation('description', 'fr_CA', false) ?: '')"
                                :sections="$acronymSections"
                                :directUrl="route('acronyms.show', $acronym->getTranslation('slug', 'fr_CA', false) ?: $acronym->slug)"
                                :indexUrl="route('acronyms.index')"
                                indexLabel="Voir tous les acronymes"
                                utmSource="share_acronym"
                                kind="Acronyme de l'éducation"
                            />
                        </div>
                    </div>
                </article>
            </div>
        </div>

        {{-- Acronymes similaires (maillage bas de page) --}}
        @if($relatedAcronyms->isNotEmpty())
            <div class="row">
                <div class="col-xs-12">
                    <h2 class="acr-show-related-title">{{ __('Acronymes similaires') }}</h2>
                </div>
            </div>
            <div class="row row-flex">
                @foreach($relatedAcronyms as $related)
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a href="{{ route('acronyms.show', $related->getTranslation('slug', app()->getLocale())) }}"
                           class="acr-show-related-card"
                           aria-label="{{ __('Voir la signification de') }} {{ $related->acronym }}">
                            <div>
                                <span class="acr-show-related-acr">{{ $related->icon ? $related->icon.' ' : '' }}{{ $related->acronym }}</span>
                                <div class="acr-show-related-name">{{ $related->full_name }}</div>
                            </div>
                            @if($related->category)
                                <div style="margin-top: 10px; font-size: 11px; color: {{ $related->category->color ?? '#6B7280' }};">
                                    {{ $related->category->icon }} {{ $related->category->name }}
                                </div>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
{{-- Schema.org JSON-LD inline (DefinedTerm enrichi + BreadcrumbList + FAQPage si présent) --}}
@php
    $_acrUrl = route('acronyms.show', $acronym->getTranslation('slug', app()->getLocale(), false) ?: $acronym->slug);
    $_acrDesc = $acronym->one_sentence_answer ?: ($acronym->description ? Str::limit(strip_tags($acronym->description), 280) : $acronym->full_name);

    $_definedTerm = [
        '@type' => 'DefinedTerm',
        'name' => (string) $acronym->acronym,
        'description' => (string) $_acrDesc,
        'url' => $_acrUrl,
        'termCode' => (string) $acronym->acronym,
        'inDefinedTermSet' => [
            '@type' => 'DefinedTermSet',
            'name' => __('Acronymes de l\'éducation au Québec'),
            'url' => route('acronyms.index'),
        ],
    ];
    $_altNames = [];
    if (! empty($acronym->full_name)) { $_altNames[] = (string) $acronym->full_name; }
    if (is_array($acronym->aliases)) {
        foreach ($acronym->aliases as $_a) {
            if (is_string($_a) && trim($_a) !== '') { $_altNames[] = trim($_a); }
        }
    }
    $_altNames = array_values(array_unique($_altNames));
    if (! empty($_altNames)) {
        $_definedTerm['alternateName'] = count($_altNames) === 1 ? $_altNames[0] : $_altNames;
    }

    $_breadcrumb = [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('Accueil'), 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __('Acronymes éducation'), 'item' => route('acronyms.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => (string) $acronym->acronym, 'item' => $_acrUrl],
        ],
    ];

    $_graph = [$_definedTerm, $_breadcrumb];

    if (! empty($acronym->faq) && is_array($acronym->faq)) {
        $_faqItems = [];
        foreach ($acronym->faq as $qa) {
            if (is_array($qa) && ! empty($qa['question']) && ! empty($qa['answer'])) {
                $_faqItems[] = [
                    '@type' => 'Question',
                    'name' => (string) $qa['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags((string) $qa['answer']),
                    ],
                ];
            }
        }
        if (! empty($_faqItems)) {
            $_graph[] = ['@type' => 'FAQPage', 'mainEntity' => $_faqItems];
        }
    }

    $_jsonLd = ['@context' => 'https://schema.org', '@graph' => $_graph];
@endphp
<script type="application/ld+json">{!! json_encode($_jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
