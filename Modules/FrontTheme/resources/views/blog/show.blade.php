@extends(fronttheme_layout())

@section('title', $article->title . ' - ' . config('app.name'))
@section('meta_description', Str::limit($article->excerpt ?? strip_tags($article->content), 160))
@section('og_type', 'article')
@if($article->featured_image)
    @section('og_image', asset($article->featured_image))
@endif

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $article->title,
        'breadcrumbItems' => [__('Blog'), $article->title]
    ])
@endsection

@section('content')
    <!-- start wpo-blog-single-section -->
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8">
                    <div class="wpo-blog-content">
                        <div class="post format-standard-image">
                            @if($article->featured_image)
                                <div class="entry-media">
                                    <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}">
                                </div>
                            @endif
                            <div class="entry-meta">
                                <ul>
                                    <li><i class="fi flaticon-user"></i> {{ __('Par') }} <a href="#">{{ $article->user->name ?? 'Admin' }}</a></li>
                                    <li><i class="fi flaticon-calendar"></i> {{ $article->published_at?->format('d M Y') }}</li>
                                    @if($article->blogCategory)
                                        <li><i class="fi flaticon-tag"></i> <a href="{{ route('blog.category', $article->blogCategory->slug) }}">{{ $article->blogCategory->name }}</a></li>
                                    @endif
                                </ul>
                            </div>
                            <h2>{{ $article->title }}</h2>
                            <div class="entry-details">
                                {!! $article->content !!}
                            </div>
                        </div>

                        @if($article->tagsRelation->isNotEmpty())
                            <div class="tag-share clearfix">
                                <div class="tag">
                                    <span>{{ __('Tags :') }} </span>
                                    <ul>
                                        @foreach($article->tagsRelation as $tag)
                                            <li><a href="{{ route('blog.index', ['tag' => $tag->slug]) }}">{{ $tag->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="author-box">
                            <div class="author-avatar">
                                <img src="{{ fronttheme_asset('images/blog-details/author.jpg') }}" alt="{{ $article->user->name ?? 'Auteur' }}">
                            </div>
                            <div class="author-content">
                                <a href="#" class="author-name">{{ $article->user->name ?? 'Auteur' }}</a>
                                <p>{{ $article->user->bio ?? __('Merci de lire nos articles.') }}</p>
                            </div>
                        </div>

                        <!-- comments placeholder -->
                        <div class="comments-area">
                            <div class="comments-section">
                                <h3 class="comments-title">{{ __('Commentaires') }}</h3>
                                <p>{{ __('Les commentaires seront bientôt disponibles.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col col-lg-4">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div>
    </section>
    <!-- end wpo-blog-single-section -->
@endsection
