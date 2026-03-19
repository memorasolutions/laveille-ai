@extends(fronttheme_layout())

@section('title', __('Blog'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Blog')])
@endsection

@section('content')
    <!-- start wpo-blog-pg-section -->
    <section class="wpo-blog-pg-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8">
                    <div class="wpo-blog-content">
                        @forelse($articles as $article)
                            <div class="post format-standard-image">
                                <div class="entry-media">
                                    @if($article->featured_image)
                                        <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}">
                                    @else
                                        <img src="{{ fronttheme_asset('images/blog/img-' . (($loop->index % 3) + 10) . '.jpg') }}" alt="{{ $article->title }}">
                                    @endif
                                </div>
                                <div class="entry-meta">
                                    <ul>
                                        <li><i class="fi flaticon-user"></i> {{ __('Par') }} <a href="#">{{ $article->user->name ?? 'Admin' }}</a> </li>
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
                        @empty
                            <div class="alert alert-info">{{ __('Aucun article trouvé.') }}</div>
                        @endforelse

                        @if($articles->hasPages())
                            <div class="pagination-wrapper pagination-wrapper-left">
                                {{ $articles->links() }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col col-lg-4">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div> <!-- end container -->
    </section>
    <!-- end wpo-blog-pg-section -->
@endsection
