<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@include('fronttheme::partials.pagination-seo', ['paginator' => $articles])

@section('title', __('Blog'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Blog')])
@endsection

@section('content')
    <!-- start wpo-blog-pg-section -->
    <section class="wpo-blog-pg-section section-padding">
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
                                        <li><i class="fi flaticon-calendar"></i> {{ $article->published_at?->format('d M Y') }}</li>
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
                            <div class="alert alert-info">{{ __('Aucun article trouvé.') }}</div>
                        @endforelse
                    </div>

                    {{-- Sentinel : charge plus au scroll --}}
                    <div x-show="hasMore" x-intersect="loadMore()" class="text-center" style="padding: 30px 0;" role="status" aria-live="polite">
                        <div x-show="loading" style="display: inline-block; width: 28px; height: 28px; border: 3px solid #E5E7EB; border-top-color: var(--c-primary, #0B7285); border-radius: 50%; animation: spin 0.6s linear infinite;"></div>
                        <p x-show="loading" style="color: #9CA3AF; font-size: 13px; margin-top: 8px;">{{ __('Chargement des articles...') }}</p>
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
    <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
    @endpush
@endsection
