<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $term->name . ' - ' . __('Glossaire IA') . ' - ' . config('app.name'))
@section('meta_description', Str::limit($term->analogy ?? strip_tags($term->definition), 160))
@section('og_type', 'article')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $term->name,
        'breadcrumbItems' => [__('Glossaire'), $term->name]
    ])
@endsection

@push('styles')
<style>
    .gl-show-wrapper { padding: 10px 0 60px; min-height: 60vh; }

    .gl-back-link {
        display: inline-flex; align-items: center; margin: 20px 0;
        color: var(--c-primary); font-weight: 600; text-decoration: none; transition: transform 0.2s;
    }
    .gl-back-link:hover { transform: translateX(-5px); text-decoration: none; color: var(--c-primary); }
    .gl-back-link svg { margin-right: 8px; width: 18px; height: 18px; }

    .gl-main-card {
        background: #fff; border-radius: var(--r-base);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        padding: 40px 45px; margin: 0 auto 50px; max-width: 800px;
        border-top: 4px solid var(--c-primary);
    }
    .gl-term-icon { font-size: 3rem; margin-bottom: 10px; text-align: center; }
    .gl-term-title {
        font-family: var(--f-heading); font-size: 2.4rem; font-weight: 800;
        color: var(--c-dark); margin: 0 0 16px; line-height: 1.2; text-align: center;
    }
    .gl-badges { display: flex; justify-content: center; flex-wrap: wrap; gap: 8px; margin-bottom: 28px; }

    .badge-type {
        padding: 4px 12px; border-radius: 50px; font-size: 0.8rem;
        font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
    }
    .badge-type-acronym { background: #FEF3C7; color: #D97706; border: 1px solid #FFEDD5; }
    .badge-type-ai_term { background: #F0FDFA; color: #0F766E; border: 1px solid #CCFBF1; }
    .badge-type-explainer { background: #F3E8FF; color: #7E22CE; border: 1px solid #E9D5FF; }

    .badge-diff {
        padding: 4px 12px; border-radius: 50px; font-size: 0.75rem;
        font-weight: 700; text-transform: uppercase; color: #fff;
    }
    .diff-beginner { background: #10B981; }
    .diff-intermediate { background: #F59E0B; }
    .diff-advanced { background: #EF4444; }

    .badge-cat {
        padding: 4px 12px; border-radius: 50px; font-size: 0.8rem;
        font-weight: 600; color: #fff; display: inline-flex; align-items: center; gap: 4px;
    }

    /* Sections enrichies */
    .gl-section { margin-bottom: 28px; }
    .gl-section-title {
        font-family: var(--f-heading); font-size: 1.2rem; font-weight: 700;
        margin: 0 0 12px; display: flex; align-items: center; gap: 8px;
    }
    .gl-section-box {
        border-radius: var(--r-base); padding: 20px; border-left: 4px solid transparent;
    }
    .gl-box-analogy { background: #EEF7FF; border-left-color: var(--c-primary); }
    .gl-box-analogy .gl-section-title { color: var(--c-primary); }
    .gl-box-analogy p { font-size: 1.1rem; line-height: 1.6; color: var(--c-dark); margin: 0; }

    .gl-box-example { background: #F0FFF4; border-left-color: #10B981; }
    .gl-box-example .gl-section-title { color: #065F46; }
    .gl-box-example p { color: #065F46; margin: 0; line-height: 1.6; }

    .gl-box-fact { background: #FFFBE6; border-left-color: #F59E0B; }
    .gl-box-fact .gl-section-title { color: #92400E; }
    .gl-box-fact p { color: #92400E; font-style: italic; margin: 0; line-height: 1.6; }

    .gl-definition { font-size: 1.1rem; line-height: 1.8; color: #4B5563; }

    /* Share */
    .gl-share { margin-top: 32px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; }
    .gl-share-label { font-weight: 600; color: #9CA3AF; margin-bottom: 12px; font-size: 0.85rem; }
    .gl-share-btn {
        display: inline-block; padding: 8px 16px; border: 1px solid #E5E7EB;
        border-radius: var(--r-btn); color: var(--c-dark); text-decoration: none !important;
        font-size: 0.85rem; font-weight: 600; transition: background 0.2s; margin: 0 4px;
    }
    .gl-share-btn:hover { background: #F3F4F6; color: var(--c-dark); }

    /* Related */
    .gl-related-title {
        font-family: var(--f-heading); font-size: 1.4rem; font-weight: 700;
        margin-bottom: 20px; color: var(--c-dark); padding-left: 14px; position: relative;
    }
    .gl-related-title::before {
        content: ''; position: absolute; left: 0; top: 4px; bottom: 4px;
        width: 4px; background: var(--c-primary); border-radius: 2px;
    }
    .gl-related-card {
        display: flex; flex-direction: column; justify-content: space-between;
        background: #fff; border-radius: var(--r-base); padding: 20px;
        height: 100%; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #E5E7EB; transition: all 0.25s;
        text-decoration: none !important; color: inherit; margin-bottom: 20px;
    }
    .gl-related-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.1); }
    .gl-related-name {
        font-family: var(--f-heading); font-size: 1rem; font-weight: 700;
        color: var(--c-dark); margin: 8px 0;
    }
    .gl-related-link { color: var(--c-primary); font-weight: 600; font-size: 0.85rem; margin-top: auto; }

    .row-flex { display: flex; flex-wrap: wrap; }
    .row-flex > [class*='col-'] { display: flex; flex-direction: column; }

    @media (max-width: 767px) {
        .gl-main-card { padding: 24px 20px; }
        .gl-term-title { font-size: 1.8rem; }
    }
</style>
@endpush

@section('content')
<div class="gl-show-wrapper">
    <div class="container">

        {{-- Back link --}}
        <div class="row">
            <div class="col-xs-12">
                <a href="{{ route('dictionary.index') }}" class="gl-back-link" aria-label="{{ __('Retour au glossaire') }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('Retour au glossaire') }}
                </a>
            </div>
        </div>

        {{-- Suggest edit --}}
        @if(class_exists(\Modules\Directory\Models\ToolSuggestion::class))
            @include('fronttheme::partials.suggest-edit', [
                'model' => $term,
                'route' => route('dictionary.suggestions.store', $term->slug),
                'fields' => ['definition' => 'Définition', 'analogy' => 'Analogie', 'example' => 'Exemple', 'did_you_know' => 'Le saviez-vous', 'other' => 'Autre'],
            ])
        @endif

        {{-- Main card --}}
        <div class="row">
            <div class="col-xs-12">
                <article class="gl-main-card">

                    {{-- Icon --}}
                    @if($term->icon)
                        <div class="gl-term-icon">{{ $term->icon }}</div>
                    @endif

                    {{-- Title --}}
                    <h1 class="gl-term-title">{{ $term->name }}</h1>

                    {{-- Badges --}}
                    <div class="gl-badges">
                        @php
                            $typeClass = match($term->type) {
                                'acronym' => 'badge-type-acronym',
                                'ai_term' => 'badge-type-ai_term',
                                'explainer' => 'badge-type-explainer',
                                default => 'badge-type-ai_term',
                            };
                            $typeName = match($term->type) {
                                'acronym' => __('Acronyme'),
                                'ai_term' => __('Terme IA'),
                                'explainer' => __('Vulgarisation'),
                                default => __('Terme'),
                            };
                            $diffLabel = match($term->difficulty ?? 'beginner') {
                                'beginner' => __('Débutant'),
                                'intermediate' => __('Intermédiaire'),
                                'advanced' => __('Avancé'),
                                default => __('Débutant'),
                            };
                        @endphp
                        <span class="badge-type {{ $typeClass }}">{{ $typeName }}</span>
                        <span class="badge-diff diff-{{ $term->difficulty ?? 'beginner' }}">{{ $diffLabel }}</span>
                        @if($term->category)
                            <span class="badge-cat" style="background: {{ $term->category->color ?? 'var(--c-primary)' }};">
                                {{ $term->category->icon }} {{ $term->category->name }}
                            </span>
                        @endif
                    </div>

                    {{-- En termes simples (analogie) --}}
                    @if($term->analogy)
                        <div class="gl-section">
                            <div class="gl-section-box gl-box-analogy">
                                <h2 class="gl-section-title">💬 {{ __('En termes simples') }}</h2>
                                <p>{{ $term->analogy }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Définition --}}
                    <div class="gl-section">
                        <h2 class="gl-section-title" style="color: var(--c-dark);">📖 {{ __('Définition') }}</h2>
                        <div class="gl-definition">
                            {!! nl2br(e($term->definition)) !!}
                        </div>
                    </div>

                    {{-- Exemple concret --}}
                    @if($term->example)
                        <div class="gl-section">
                            <div class="gl-section-box gl-box-example">
                                <h2 class="gl-section-title">🎯 {{ __('Exemple concret') }}</h2>
                                <p>{{ $term->example }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Le saviez-vous ? --}}
                    @if($term->did_you_know)
                        <div class="gl-section">
                            <div class="gl-section-box gl-box-fact">
                                <h2 class="gl-section-title">💡 {{ __('Le saviez-vous ?') }}</h2>
                                <p>{{ $term->did_you_know }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Partager --}}
                    <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #eee;">
                        <p style="font-weight: 600; color: #9CA3AF; margin-bottom: 12px; font-size: 0.85rem; text-align: center;">{{ __('Partager cette définition') }}</p>
                        <div style="display: flex; justify-content: center; align-items: center; gap: 12px; flex-wrap: wrap;">
                            @include('fronttheme::partials.bookmark-btn', ['type' => 'Modules\\Dictionary\\Models\\Term', 'id' => $term->id])
                            @include('fronttheme::partials.share-buttons', ['title' => $term->name . ' — ' . __('Glossaire IA'), 'url' => request()->url()])
                        </div>
                    </div>

                </article>
            </div>
        </div>

        {{-- Related terms --}}
        @if($relatedTerms->isNotEmpty())
            <div class="row">
                <div class="col-xs-12">
                    <h2 class="gl-related-title">{{ __('Termes associés') }}</h2>
                </div>
            </div>
            <div class="row row-flex">
                @foreach($relatedTerms as $related)
                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                        <a href="{{ route('dictionary.show', $related->slug) }}"
                           class="gl-related-card"
                           aria-label="{{ __('Lire la définition de') }} {{ $related->name }}">
                            <div>
                                <span style="font-size: 1.5rem;">{{ $related->icon ?? '📄' }}</span>
                                <span class="gl-related-name">{{ $related->name }}</span>
                            </div>
                            <p style="font-size: 0.85rem; color: #6B7280; flex-grow: 1; margin: 8px 0;">
                                {{ Str::limit(strip_tags($related->definition), 80) }}
                            </p>
                            <div class="gl-related-link">
                                {{ __('Lire la définition') }} →
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script type="application/ld+json">
{
    "@@context": "https://schema.org/",
    "@@type": "DefinedTerm",
    "@@id": "{{ route('dictionary.show', $term->slug) }}",
    "name": "{{ $term->name }}",
    "description": "{{ Str::limit(strip_tags($term->definition), 160) }}",
    "inDefinedTermSet": {
        "@@type": "DefinedTermSet",
        "@@id": "{{ route('dictionary.index') }}",
        "name": "{{ __('Glossaire IA') }}"
    },
    "termCode": "{{ $term->slug }}"
}
</script>
@endpush
