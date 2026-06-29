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
    .nw-search-icon { position: absolute; right: 0.625rem; top: 50%; transform: translateY(-50%); color: #6b7280; pointer-events: none; }
    @keyframes nw-spin { to { transform: translateY(-50%) rotate(360deg); } }
    [x-cloak] { display: none !important; }
    .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
    #nw-results-container.is-loading { opacity: 0.55; transition: opacity 150ms ease; }
    .nw-chips { display: flex; gap: 0.5rem; padding: 0.125rem 0; scrollbar-width: none; }
    .nw-chips::-webkit-scrollbar { display: none; }
    @media (min-width: 1024px) {
        .nw-chips { flex-wrap: wrap; row-gap: 0.5rem; }
    }
    @media (max-width: 1023px) {
        .nw-chips {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-right: 24px;
            min-width: 0;
            max-width: 100%;
            width: 100%;
            -webkit-mask-image: linear-gradient(to right, black calc(100% - 32px), transparent);
            mask-image: linear-gradient(to right, black calc(100% - 32px), transparent);
            scroll-snap-type: x proximity;
        }
        .nw-chip { scroll-snap-align: start; flex-shrink: 0; }
    }
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
    .nw-follow-btn { background: none; border: none; padding: 2px; cursor: pointer; color: #6b7280; transition: color 0.15s; display: inline-flex; align-items: center; flex-shrink: 0; }
    .nw-follow-btn:hover { color: var(--c-primary); }
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
                <div class="nw-search-wrap" x-data="newsLiveSearch()" style="position: relative;">
                    <form method="GET" action="{{ route('news.index') }}" @submit.prevent="autoSearch($event.target.q.value, true)">
                        @foreach(request()->except(['q', 'page']) as $key => $value)
                            @if($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                        @endforeach
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Rechercher un sujet...') }}" aria-label="{{ __('Rechercher une actualité (recherche automatique dès 3 caractères)') }}" class="nw-search-input"
                               x-on:input.debounce.400ms="autoSearch($event.target.value)"
                               autocomplete="off">
                        <span class="nw-search-icon" x-show="!loading"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg></span>
                        <span class="nw-search-icon" x-show="loading" x-cloak aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: nw-spin 0.7s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        </span>
                    </form>
                    <span class="visually-hidden" aria-live="polite" x-text="statusMessage"></span>
                </div>
                <form method="GET" action="{{ route('news.index') }}">
                    @foreach(request()->except(['sort', 'page']) as $key => $value)
                        @if($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <select name="sort" onchange="this.form.submit()" aria-label="{{ __('Trier les actualités') }}" class="nw-sort-select">
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
                        @auth
                        @if(Route::has('category-subscription.toggle'))
                        <button x-data="{ subscribed: {{ auth()->user()->isSubscribedTo($cat->category_tag, 'news') ? 'true' : 'false' }} }"
                            @click="subscribed = !subscribed; fetch('{{ route('category-subscription.toggle') }}', { method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Content-Type': 'application/json', 'Accept': 'application/json'}, body: JSON.stringify({category_tag: '{{ $cat->category_tag }}', module: 'news'}) }).catch(() => subscribed = !subscribed)"
                            class="nw-follow-btn" :title="subscribed ? '{{ __('Ne plus suivre') }}' : '{{ __('Suivre') }}'"
                            :aria-label="(subscribed ? '{{ __('Ne plus suivre') }}' : '{{ __('Suivre') }}') + ' {{ $cat->category_tag }}'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" :fill="subscribed ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        </button>
                        @endif
                        @endauth
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
        <div id="nw-results-container" aria-live="polite" aria-busy="false">
        @if($articles->isEmpty())
            <div class="nw-empty">
                <p style="font-size: 1.125rem; margin-bottom: 0.5rem;">{{ __('Aucune actualité ne correspond à vos critères.') }}</p>
                <a href="{{ route('news.index') }}" class="nw-chip">{{ __('Réinitialiser les filtres') }}</a>
            </div>
        @else
            <div class="row nw-articles-grid news-grid">
                @foreach($articles as $article)
                <div class="col-sm-6 col-md-4" style="margin-bottom: 1.25rem;">
                    @include('news::public.partials.article-card', ['article' => $article])
                </div>
                @endforeach
            </div>

            <div style="margin-top: 1.5rem;">
                {{ $articles->appends(request()->query())->links() }}
            </div>
        @endif
        </div>{{-- /#nw-results-container --}}
    </div>
</section>

@push('scripts')
<script>
function newsLiveSearch() {
    return {
        loading: false,
        statusMessage: '',
        lastQuery: @json($filters['q'] ?? ''),
        async autoSearch(value, force = false) {
            const v = (value || '').trim();
            if (!force) {
                if (v.length > 0 && v.length < 3) return;
                if (v === this.lastQuery) return;
            }
            this.lastQuery = v;
            this.loading = true;
            this.statusMessage = '{{ __('Recherche en cours…') }}';
            const container = document.getElementById('nw-results-container');
            if (container) { container.classList.add('is-loading'); container.setAttribute('aria-busy', 'true'); }

            const url = new URL(window.location.href);
            if (v) url.searchParams.set('q', v); else url.searchParams.delete('q');
            url.searchParams.delete('page');

            try {
                const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('nw-results-container');
                if (fresh && container) {
                    container.innerHTML = fresh.innerHTML;
                    window.history.pushState({}, '', url.toString());
                    const cardCount = container.querySelectorAll('.nw-card').length;
                    this.statusMessage = cardCount > 0
                        ? cardCount + ' {{ __('résultats trouvés') }}'
                        : '{{ __('Aucun résultat') }}';
                }
            } catch (e) {
                console.error('[news search] failed:', e);
                this.statusMessage = '{{ __('Erreur de recherche, appuyez sur Entrée pour réessayer.') }}';
            } finally {
                this.loading = false;
                if (container) { container.classList.remove('is-loading'); container.setAttribute('aria-busy', 'false'); }
            }
        }
    };
}
</script>
@endpush

@endsection
