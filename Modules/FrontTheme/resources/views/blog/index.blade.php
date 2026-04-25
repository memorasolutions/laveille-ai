<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@include('fronttheme::partials.pagination-seo', ['paginator' => $articles])

@section('title', __('Blog') . ' - ' . config('app.name'))
@section('meta_description', __('Articles et analyses sur l\'intelligence artificielle, les technologies emergentes et la transformation numerique au Quebec.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Blog')])
@endsection

@section('content')
    <h1 class="sr-only">{{ __('Blog') }} — {{ config('app.name') }}</h1>
    <!-- start wpo-blog-pg-section -->
    <section class="wpo-blog-pg-section section-padding">

        {{-- Chips catégories + bouton suivre --}}
        @if(isset($categories) && $categories->isNotEmpty())
        <div class="container" style="margin-bottom: 1.5rem;">
            <div class="nw-filter-row">
                <span class="nw-filter-label">{{ __('Catégorie') }}</span>
                <div class="nw-chips">
                    <a href="{{ route('blog.index', request()->except(['category', 'page'])) }}" class="nw-chip {{ is_null($currentCategory ?? null) ? 'active' : '' }}">{{ __('Toutes') }}</a>
                    @foreach($categories as $cat)
                        <a href="{{ route('blog.index', array_merge(request()->except(['category', 'page']), ['category' => $cat->slug])) }}" class="nw-chip {{ ($currentCategory->id ?? null) === $cat->id ? 'active' : '' }}">
                            {{ $cat->name }} <span class="nw-chip-count">({{ $cat->published_articles_count }})</span>
                        </a>
                        @auth
                        @if(Route::has('category-subscription.toggle'))
                        <button x-data="{ subscribed: {{ auth()->user()->isSubscribedTo($cat->name, 'blog') ? 'true' : 'false' }} }"
                            @click="subscribed = !subscribed; fetch('{{ route('category-subscription.toggle') }}', { method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Content-Type': 'application/json', 'Accept': 'application/json'}, body: JSON.stringify({category_tag: '{{ $cat->name }}', module: 'blog'}) }).catch(() => subscribed = !subscribed)"
                            class="nw-follow-btn" :title="subscribed ? '{{ __('Ne plus suivre') }}' : '{{ __('Suivre') }}'"
                            :aria-label="(subscribed ? '{{ __('Ne plus suivre') }}' : '{{ __('Suivre') }}') + ' {{ $cat->name }}'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" :fill="subscribed ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        </button>
                        @endif
                        @endauth
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        <div class="container">
            <div class="row">
                <div class="col col-lg-8 col-12"
                     x-data="{
                        nextPage: {{ $articles->hasMorePages() ? $articles->currentPage() + 1 : 0 }},
                        hasMore: {{ $articles->hasMorePages() ? 'true' : 'false' }},
                        loading: false,
                        async loadMore() {
                            if (this.loading || !this.hasMore) return;
                            this.loading = true;
                            try {
                                const url = new URL(window.location.href);
                                url.searchParams.set('page', this.nextPage);
                                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                                const data = await res.json();
                                this.$refs.articles.insertAdjacentHTML('beforeend', data.html);
                                this.hasMore = data.hasMore;
                                this.nextPage = data.nextPage;
                            } catch(e) { console.error(e); }
                            finally { this.loading = false; }
                        }
                     }">
                    <div class="wpo-blog-content" x-ref="articles">
                        @forelse($articles as $article)
                            <div class="post format-standard-image">
                                <div class="entry-media">
                                    @if($article->featured_image)
                                        <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}" loading="lazy">
                                    @else
                                        <img src="{{ fronttheme_asset('images/blog/img-' . (($loop->index % 3) + 10) . '.jpg') }}" alt="{{ $article->title }}" loading="lazy">
                                    @endif
                                </div>
                                <div class="entry-meta">
                                    <ul>
                                        <li><i class="fi flaticon-user"></i> {{ __('Par') }} <a href="#">{{ $article->getAuthorName() }}</a> </li>
                                        <li><i class="fi flaticon-comment-white-oval-bubble"></i> {{ $article->comments_count ?? 0 }} {{ __('commentaires') }}</li>
                                        <li><i class="fi flaticon-calendar"></i> {{ $article->published_at?->translatedFormat('d M Y') }}</li>
                                    </ul>
                                </div>
                                <div class="entry-details">
                                    <h3><a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a></h3>
                                    <p>{{ Str::limit($article->excerpt ?? strip_tags($article->content), 200) }}</p>
                                    <a href="{{ route('blog.show', $article->slug) }}" class="read-more">{{ __('LIRE LA SUITE...') }}</a>
                                </div>
                            </div>
                            @if($loop->iteration === 3 && class_exists(\Modules\Ads\Services\AdsRenderer::class))
                                {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('between-posts') !!}
                            @endif
                        @empty
                            @php
                                $hasFilters = request()->hasAny(['search', 'tag']);
                                $hasCategory = isset($currentCategory);
                                $isBeyondFirstPage = $articles->currentPage() > 1;
                            @endphp
                            <div class="alert alert-info">
                                @if ($isBeyondFirstPage)
                                    {{ __('Cette page n\'existe pas.') }}
                                    <a href="{{ request()->fullUrlWithoutQuery(['page']) }}" class="alert-link">{{ __('Retour première page') }}</a>
                                @elseif ($hasFilters && $hasCategory)
                                    {{ __('Aucun article ne correspond à ces filtres combinés dans la catégorie :name.', ['name' => $currentCategory->name]) }}
                                    <a href="{{ route('blog.index', ['category' => request('category')]) }}" class="alert-link">{{ __('Effacer les filtres (conserver la catégorie :name)', ['name' => $currentCategory->name]) }}</a>
                                @elseif ($hasFilters && !$hasCategory)
                                    {{ __('Aucun article ne correspond à votre recherche.') }}
                                    <a href="{{ route('blog.index') }}" class="alert-link">{{ __('Voir tous les articles') }}</a>
                                @else
                                    {{ __('Aucun article trouvé.') }}
                                @endif
                            </div>
                        @endforelse
                    </div>

                    {{-- Sentinel : charge plus au scroll --}}
                    <div x-show="hasMore" x-intersect="loadMore()" class="text-center" style="padding: 30px 0;" role="status" aria-live="polite">
                        <div x-show="loading" style="display: inline-block; width: 28px; height: 28px; border: 3px solid #E5E7EB; border-top-color: var(--c-primary, #0B7285); border-radius: 50%; animation: spin 0.6s linear infinite;"></div>
                        <p x-show="loading" style="color: #374151; font-size: 13px; margin-top: 8px;">{{ __('Chargement des articles...') }}</p>
                    </div>

                    {{-- Pagination noscript fallback --}}
                    <noscript>
                        @if($articles->hasPages())
                            <div class="pagination-wrapper pagination-wrapper-left">
                                {{ $articles->links() }}
                            </div>
                        @endif
                    </noscript>
                </div>
                <div class="col col-lg-4 col-12 d-none d-lg-block">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div> <!-- end container -->
    </section>
    <!-- end wpo-blog-pg-section -->

    @push('styles')
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        .nw-filter-row { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 0.75rem; }
        .nw-filter-label { font-size: 0.8125rem; font-weight: 600; color: #374151; min-width: 70px; }
        .nw-chips { display: flex; overflow-x: auto; gap: 0.5rem; padding: 0.125rem 0; scrollbar-width: none; }
        .nw-chips::-webkit-scrollbar { display: none; }
        .nw-chip { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.8125rem; white-space: nowrap; text-decoration: none; transition: all 0.15s; background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
        .nw-chip:hover { background: #e5e7eb; color: #1f2937; text-decoration: none; }
        .nw-chip.active { background: var(--c-primary); color: #fff; border-color: var(--c-primary); }
        .nw-chip.active:hover { opacity: 0.9; color: #fff; text-decoration: none; }
        .nw-chip-count { font-size: 0.6875rem; opacity: 0.8; }
        .nw-follow-btn { background: none; border: none; padding: 2px; cursor: pointer; color: #374151; transition: color 0.15s; display: inline-flex; align-items: center; flex-shrink: 0; }
        .nw-follow-btn:hover { color: var(--c-primary); }
        @media (max-width: 640px) { .nw-filter-row { flex-direction: column; align-items: stretch; } .nw-filter-label { min-width: auto; } }
    </style>
    @endpush
@endsection
