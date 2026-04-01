<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $acronym->acronym . ' - ' . $acronym->full_name . ' - ' . config('app.name'))
@section('meta_description', __('Signification de') . ' ' . $acronym->acronym . ' : ' . $acronym->full_name . '. ' . Str::limit(strip_tags($acronym->description), 120))
@section('og_type', 'article')
@if(!empty($acronym->logo_url))
    @section('og_image', $acronym->logo_url)
@endif

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $acronym->acronym,
        'breadcrumbItems' => [__('Acronymes éducation'), $acronym->acronym]
    ])
@endsection

@push('styles')
<style>
    .acr-show-wrapper { padding: 10px 0 60px; min-height: 60vh; }

    .acr-show-back {
        display: inline-flex; align-items: center; margin: 20px 0;
        color: var(--c-primary); font-weight: 600; text-decoration: none; transition: transform 0.2s;
    }
    .acr-show-back:hover { transform: translateX(-5px); text-decoration: none; color: var(--c-primary); }
    .acr-show-back svg { margin-right: 8px; width: 18px; height: 18px; }

    .acr-show-card {
        background: #fff; border-radius: var(--r-base);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        padding: 36px 40px; margin: 0 auto 40px; max-width: 800px;
        border-top: 4px solid var(--c-primary);
    }
    .acr-show-header { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #F3F4F6; }
    .acr-show-logo {
        width: 80px; height: 80px; flex-shrink: 0; border-radius: var(--r-base);
        overflow: hidden; display: flex; align-items: center; justify-content: center;
        background: #F9FAFB; border: 1px solid #E5E7EB;
    }
    .acr-show-logo img { width: 100%; height: 100%; object-fit: contain; }
    .acr-show-logo-fallback {
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
        font-size: 2rem; color: #D1D5DB;
    }
    .acr-show-h1 {
        font-family: var(--f-heading); font-size: 2rem; font-weight: 800;
        color: var(--c-primary); margin: 0 0 6px; line-height: 1.2;
    }
    .acr-show-h2 { font-size: 1.1rem; color: #6B7280; margin: 0 0 12px; font-weight: 400; line-height: 1.4; }
    .acr-show-badge { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; color: #fff; }

    .acr-show-desc { font-size: 1.05rem; line-height: 1.8; color: #4B5563; margin-bottom: 24px; }
    .acr-show-desc p { margin-bottom: 12px; }

    .acr-show-website {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--c-primary); color: #fff; padding: 10px 20px;
        border-radius: var(--r-btn); font-weight: 600; text-decoration: none; transition: background 0.2s;
    }
    .acr-show-website:hover { background: var(--c-dark); color: #fff; text-decoration: none; }

    .acr-show-share { margin-top: 24px; padding-top: 20px; border-top: 1px solid #eee; }
    .acr-show-share-label { font-weight: 600; color: #9CA3AF; margin-bottom: 12px; font-size: 0.85rem; text-align: center; }

    .acr-show-related-title {
        font-family: var(--f-heading); font-size: 1.4rem; font-weight: 700;
        margin-bottom: 20px; color: var(--c-dark); padding-left: 14px; position: relative;
    }
    .acr-show-related-title::before {
        content: ''; position: absolute; left: 0; top: 4px; bottom: 4px;
        width: 4px; background: var(--c-primary); border-radius: 2px;
    }
    .acr-show-related-card {
        display: block; background: #fff; border-radius: var(--r-base); padding: 16px;
        border: 1px solid #E5E7EB; transition: all 0.25s; text-decoration: none !important;
        color: inherit; margin-bottom: 12px;
    }
    .acr-show-related-card:hover { transform: translateY(-2px); box-shadow: 0 8px 16px -4px rgba(0,0,0,0.1); border-color: var(--c-primary); }
    .acr-show-related-acr { font-family: var(--f-heading); font-weight: 700; color: var(--c-primary); font-size: 1rem; }
    .acr-show-related-name { font-size: 0.85rem; color: #6B7280; line-height: 1.4; margin-top: 4px; }

    @media (max-width: 767px) {
        .acr-show-card { padding: 24px 20px; }
        .acr-show-header { flex-direction: column; align-items: center; text-align: center; }
        .acr-show-h1 { font-size: 1.6rem; }
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

        {{-- Suggest edit + Report --}}
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

        <div class="row">
            <div class="col-md-8">
                {{-- Main card --}}
                <article class="acr-show-card">
                    <header class="acr-show-header">
                        <div class="acr-show-logo">
                            @if($acronym->logo_url)
                                <img src="{{ str_starts_with($acronym->logo_url, 'http') ? $acronym->logo_url : asset($acronym->logo_url) }}" alt="{{ __('Logo') }} {{ $acronym->acronym }}" width="80" height="80" loading="lazy">
                            @else
                                <div class="acr-show-logo-fallback">🎓</div>
                            @endif
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <h1 class="acr-show-h1" style="margin:0;">{{ $acronym->acronym }}</h1>
                                @if(trait_exists(\Modules\Voting\Traits\HasCommunityVotes::class))
                                    @include('voting::components.vote-button', ['item' => $acronym, 'type' => 'acronym'])
                                @endif
                            </div>
                            <h2 class="acr-show-h2">{{ $acronym->full_name }}</h2>
                            @if($acronym->category)
                                <span class="acr-show-badge" style="background: {{ $acronym->category->color ?? 'var(--c-primary)' }};">
                                    {{ $acronym->category->icon }} {{ $acronym->category->name }}
                                </span>
                            @endif
                        </div>
                    </header>

                    {{-- Description --}}
                    @if($acronym->description)
                        <div class="acr-show-desc">
                            @php
                                $descText = e($acronym->description);
                                // Split into sentences, regroup into paragraphs (2 sentences each)
                                $sentences = preg_split('/(?<=[.!?])\s+(?=[A-ZÀ-ÿ])/', $descText);
                                $paragraphs = array_chunk($sentences, 2);
                            @endphp
                            @foreach($paragraphs as $para)
                                <p>{!! implode(' ', $para) !!}</p>
                            @endforeach
                        </div>
                    @else
                        <p style="color: #9CA3AF; font-style: italic;">{{ __('Aucune description détaillée disponible pour cet acronyme.') }}</p>
                    @endif

                    {{-- Website --}}
                    @if($acronym->website_url)
                        <div style="margin-bottom: 24px;">
                            <a href="{{ $acronym->website_url }}" target="_blank" rel="noopener noreferrer" class="acr-show-website">
                                🌐 {{ __('Visiter le site officiel') }}
                            </a>
                        </div>
                    @endif

                    {{-- Share --}}
                    <div class="acr-show-share">
                        <p class="acr-show-share-label">{{ __('Partager cet acronyme') }}</p>
                        <div style="display: flex; justify-content: center; align-items: center; gap: 12px; flex-wrap: wrap;">
                            @include('fronttheme::partials.bookmark-btn', ['type' => 'Modules\\Acronyms\\Models\\Acronym', 'id' => $acronym->id])
                            @include('fronttheme::partials.share-buttons', ['title' => $acronym->acronym . ' –' . $acronym->full_name, 'url' => request()->url()])
                        </div>
                    </div>
                </article>
            </div>

            <div class="col-md-4">
                {{-- Related --}}
                @if($relatedAcronyms->isNotEmpty())
                    <h3 class="acr-show-related-title">{{ __('Acronymes similaires') }}</h3>
                    @foreach($relatedAcronyms as $related)
                        <a href="{{ route('acronyms.show', $related->getTranslation('slug', app()->getLocale())) }}" class="acr-show-related-card">
                            <div class="acr-show-related-acr">{{ $related->acronym }}</div>
                            <div class="acr-show-related-name">{{ $related->full_name }}</div>
                            @if($related->category)
                                <div style="margin-top: 6px; font-size: 11px; color: {{ $related->category->color ?? '#9CA3AF' }};">
                                    {{ $related->category->icon }} {{ $related->category->name }}
                                </div>
                            @endif
                        </a>
                    @endforeach
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script type="application/ld+json">
{
    "@@context": "https://schema.org/",
    "@@type": "DefinedTerm",
    "@@id": "{{ route('acronyms.show', $acronym->getTranslation('slug', app()->getLocale())) }}",
    "name": "{{ $acronym->acronym }}",
    "description": "{{ $acronym->full_name }}{{ $acronym->description ? ' - ' . Str::limit(strip_tags($acronym->description), 200) : '' }}",
    "inDefinedTermSet": {
        "@@type": "DefinedTermSet",
        "@@id": "{{ route('acronyms.index') }}",
        "name": "{{ __('Acronymes de l\'éducation au Québec') }}"
    },
    "termCode": "{{ $acronym->acronym }}"
}
</script>
@endpush
