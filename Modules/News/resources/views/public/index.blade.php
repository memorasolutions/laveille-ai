@extends(fronttheme_layout())

@section('title', __('Actualités IA et technologie') . ' - ' . config('app.name'))
@section('meta_description', __('Veille quotidienne IA et technologie : résumés structurés par intelligence artificielle, classés par catégorie.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Actualités')])
@endsection

@push('styles')
<style>
    .nw-articles-grid.row { flex-wrap: wrap !important; }
    .nw-filters { margin-bottom: 1.5rem; }
    .nw-filter-row { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 0.75rem; }
    .nw-filter-label { font-size: 0.8125rem; font-weight: 600; color: #6b7280; min-width: 70px; }
    .nw-search-wrap { position: relative; }
    .nw-search-input {
        width: 280px; height: 38px; padding: 0 2rem 0 0.75rem;
        border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.875rem;
        background: #fff; outline: none;
    }
    .nw-search-input:focus { border-color: var(--c-primary); }
    .nw-search-icon { position: absolute; right: 0.625rem; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
    .nw-chips { display: flex; overflow-x: auto; gap: 0.5rem; padding: 0.125rem 0; scrollbar-width: none; }
    .nw-chips::-webkit-scrollbar { display: none; }
    .nw-chip {
        display: inline-flex; align-items: center; gap: 0.25rem;
        padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.8125rem;
        white-space: nowrap; text-decoration: none; transition: all 0.15s;
        background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb;
    }
    .nw-chip:hover { background: #e5e7eb; color: #1f2937; text-decoration: none; }
    .nw-chip.active { background: var(--c-primary); color: #fff; border-color: var(--c-primary); }
    .nw-chip.active:hover { opacity: 0.9; color: #fff; text-decoration: none; }
    .nw-chip-count { font-size: 0.6875rem; opacity: 0.8; }
    .nw-sort-select {
        height: 38px; padding: 0 1.75rem 0 0.75rem; font-size: 0.8125rem;
        border: 1px solid #e5e7eb; border-radius: 8px; background: #fff;
        cursor: pointer; outline: none;
    }
    .nw-card {
        display: flex; flex-direction: column; height: 100%;
        background: #fff; border-radius: 8px; overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .nw-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .nw-card-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%; }
    .nw-card-img-wrap {
        position: relative; overflow: hidden; aspect-ratio: 16 / 9;
        background: linear-gradient(135deg, #1a2332 0%, #0b7285 100%);
        display: flex; align-items: center; justify-content: center;
    }
    .nw-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
    .nw-card:hover .nw-card-img { transform: scale(1.03); }
    .nw-card-placeholder { color: rgba(255,255,255,0.3); font-size: 2.5rem; font-weight: 700; font-family: var(--f-heading); }
    .nw-card-body { padding: 1rem; flex: 1; display: flex; flex-direction: column; }
    .nw-card-title {
        font-family: var(--f-heading); font-size: 1.05rem; font-weight: 700;
        line-height: 1.35; color: var(--c-dark); margin: 0 0 0.5rem;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .nw-card-hook {
        font-size: 0.9rem; color: #4b5563; line-height: 1.55; margin-bottom: 0.75rem;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; flex: 1;
    }
    .nw-meta { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-top: auto; padding-top: 0.75rem; border-top: 1px solid #f3f4f6; }
    .nw-source-pill { background: #f3f4f6; color: #6b7280; font-size: 0.6875rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase; }
    .nw-date { font-size: 0.75rem; color: #9ca3af; display: flex; align-items: center; gap: 0.375rem; }
    .nw-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    .nw-dot-high { background: #10b981; }
    .nw-dot-mid { background: #3b82f6; }
    .nw-dot-low { background: #d1d5db; }
    .nw-impact-bar { border-left: 3px solid transparent; }
    .nw-impact-high { border-left-color: #ef4444; }
    .nw-impact-mid { border-left-color: #f59e0b; }
    .nw-impact-low { border-left-color: #e5e7eb; }
    .nw-empty { text-align: center; padding: 4rem 1rem; color: #9ca3af; }
    .nw-page-intro { color: #6b7280; margin-bottom: 1.5rem; font-size: 1rem; }
    .nw-active-filters { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
    .nw-active-tag { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.2rem 0.5rem; background: #e0f2fe; color: #0369a1; border-radius: 4px; font-size: 0.75rem; }
    .nw-active-tag a { color: #0369a1; text-decoration: none; font-weight: 700; }
    @media (max-width: 767px) {
        .nw-search-input { width: 100%; }
        .nw-filter-row { flex-direction: column; align-items: stretch; }
        .nw-filter-label { min-width: auto; }
    }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <h1 style="font-family: var(--f-heading); margin-bottom: 0.25rem;">{{ __('Actualités IA et technologie') }}</h1>
        <p class="nw-page-intro">{{ __('Veille quotidienne — résumés structurés par intelligence artificielle') }}</p>

        {{-- Filtres --}}
        <div class="nw-filters">
            {{-- Recherche + tri --}}
            <div class="nw-filter-row">
                <div class="nw-search-wrap">
                    <form method="GET" action="{{ route('news.index') }}">
                        @foreach(request()->except(['q', 'page']) as $key => $value)
                            @if($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                        @endforeach
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Rechercher un sujet...') }}" class="nw-search-input">
                        <span class="nw-search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg></span>
                    </form>
                </div>
                <form method="GET" action="{{ route('news.index') }}">
                    @foreach(request()->except(['sort', 'page']) as $key => $value)
                        @if($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <select name="sort" onchange="this.form.submit()" class="nw-sort-select">
                        <option value="date" {{ ($filters['sort'] ?? 'date') === 'date' ? 'selected' : '' }}>{{ __('Plus récents') }}</option>
                        <option value="score" {{ ($filters['sort'] ?? 'date') === 'score' ? 'selected' : '' }}>{{ __('Meilleur score') }}</option>
                    </select>
                </form>
            </div>

            {{-- Chips catégories --}}
            <div class="nw-filter-row">
                <span class="nw-filter-label">{{ __('Catégorie') }}</span>
                <div class="nw-chips">
                    <a href="{{ route('news.index', array_filter(array_merge(request()->except(['category', 'page']), []))) }}" class="nw-chip {{ empty($filters['category']) ? 'active' : '' }}">{{ __('Toutes') }}</a>
                    @foreach($categories as $cat)
                        <a href="{{ route('news.index', array_filter(array_merge(request()->except(['category', 'page']), ['category' => $cat->category_tag]))) }}" class="nw-chip {{ ($filters['category'] ?? '') === $cat->category_tag ? 'active' : '' }}">
                            {{ $cat->category_tag }} <span class="nw-chip-count">({{ $cat->count }})</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Chips période --}}
            <div class="nw-filter-row">
                <span class="nw-filter-label">{{ __('Période') }}</span>
                <div class="nw-chips">
                    @php
                        $periodBase = array_filter(request()->except(['period', 'page']));
                    @endphp
                    <a href="{{ route('news.index', $periodBase) }}" class="nw-chip {{ empty($filters['period']) ? 'active' : '' }}">{{ __('Tout') }}</a>
                    <a href="{{ route('news.index', array_merge($periodBase, ['period' => 'today'])) }}" class="nw-chip {{ ($filters['period'] ?? '') === 'today' ? 'active' : '' }}">{{ __("Aujourd'hui") }}</a>
                    <a href="{{ route('news.index', array_merge($periodBase, ['period' => 'week'])) }}" class="nw-chip {{ ($filters['period'] ?? '') === 'week' ? 'active' : '' }}">{{ __('Cette semaine') }}</a>
                    <a href="{{ route('news.index', array_merge($periodBase, ['period' => 'month'])) }}" class="nw-chip {{ ($filters['period'] ?? '') === 'month' ? 'active' : '' }}">{{ __('Ce mois') }}</a>
                </div>
            </div>
        </div>

        {{-- Articles --}}
        @if($articles->isEmpty())
            <div class="nw-empty">
                <p style="font-size: 1.125rem; margin-bottom: 0.5rem;">{{ __('Aucune actualité ne correspond à vos critères.') }}</p>
                <a href="{{ route('news.index') }}" class="nw-chip">{{ __('Réinitialiser les filtres') }}</a>
            </div>
        @else
            <div class="row nw-articles-grid news-grid">
                @foreach($articles as $article)
                @php
                    $ss = $article->structured_summary;
                    $score = $article->relevance_score ?? 0;
                    $dotClass = $score >= 8 ? 'nw-dot-high' : ($score >= 6 ? 'nw-dot-mid' : 'nw-dot-low');
                    $impactClass = match($article->impact_level) { 'Élevé' => 'nw-impact-high', 'Moyen' => 'nw-impact-mid', default => 'nw-impact-low' };
                    $readText = strip_tags($article->description ?? '') . ' ' . ($article->summary ?? '');
                    if ($ss) { $readText .= ' ' . ($ss['hook'] ?? '') . ' ' . implode(' ', $ss['key_points'] ?? []) . ' ' . ($ss['why_important'] ?? ''); }
                    $readMinutes = max(1, (int) ceil(str_word_count($readText) / 200));
                @endphp
                <div class="col-sm-6 col-md-4" style="margin-bottom: 1.25rem;">
                    <article class="nw-card nw-impact-bar {{ $impactClass }}">
                        <a href="{{ route('news.show', $article) }}" class="nw-card-link">
                            <div class="nw-card-img-wrap">
                                @if($article->image_url)
                                    <img src="{{ $article->image_url }}" alt="" class="nw-card-img" loading="lazy">
                                @else
                                    <span class="nw-card-placeholder">{{ mb_strtoupper(mb_substr($article->category_tag ?? 'N', 0, 2)) }}</span>
                                @endif
                            </div>
                            <div class="nw-card-body">
                                <h3 class="nw-card-title">{{ $article->seo_title ?? $article->title }}</h3>
                                <p class="nw-card-hook">
                                    @if($ss && isset($ss['hook']))
                                        {{ $ss['hook'] }}
                                    @else
                                        {{ Str::limit($article->summary ?? strip_tags($article->description), 120) }}
                                    @endif
                                </p>
                                <div class="nw-meta">
                                    <span class="nw-source-pill">{{ $readMinutes }} min</span>
                                    <span class="nw-source-pill">{{ $article->source->name ?? __('Source') }}</span>
                                    <span class="nw-date">
                                        {{ $article->pub_date?->diffForHumans() }}
                                        @if($score)
                                            <span class="nw-dot {{ $dotClass }}" title="{{ $score }}/10"></span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </a>
                    </article>
                </div>
                @endforeach
            </div>

            <div style="margin-top: 1.5rem;">
                {{ $articles->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
