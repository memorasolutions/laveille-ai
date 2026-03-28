<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="blog-sidebar">
    <div class="widget about-widget">
        <div class="img-holder">
            <img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name') }}" style="border-radius: 50%; max-width: 150px;" loading="lazy">
        </div>
        <h4>{{ config('app.name') }}</h4>
        <p>{{ __('Votre veille sur l\'IA, les technologies et la transformation numérique au Québec.') }}</p>
        <div class="aw-shape">
            <img src="{{ fronttheme_asset('images/blog/ab.png') }}" alt="" loading="lazy">
        </div>
    </div>
    <div class="widget search-widget">
        <form action="{{ route('blog.index') }}" method="GET">
            <div>
                <input type="text" name="search" class="form-control" placeholder="{{ __('Rechercher...') }}" value="{{ request('search') }}">
                <button type="submit"><i class="ti-search"></i></button>
            </div>
        </form>
    </div>
    @isset($categories)
        <div class="widget category-widget">
            <h3>{{ __('Catégories') }}</h3>
            <ul>
                @foreach($categories as $category)
                    <li><a href="{{ route('blog.category', $category->slug) }}">{{ $category->name }}<span>({{ $category->articles_count ?? 0 }})</span></a></li>
                @endforeach
            </ul>
        </div>
    @endisset
    @isset($recentArticles)
        <div class="widget recent-post-widget">
            <h3>{{ __('Articles récents') }}</h3>
            <div class="posts">
                @foreach($recentArticles->take(4) as $article)
                    <div class="post">
                        <div class="img-holder">
                            @if($article->featured_image)
                                <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}" loading="lazy">
                            @else
                                <img src="{{ fronttheme_asset('images/recent-posts/img-' . ($loop->iteration) . '.jpg') }}" alt="" loading="lazy">
                            @endif
                        </div>
                        <div class="details">
                            <span class="date">{{ $article->published_at?->format('d M Y') }}</span>
                            <h4><a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a></h4>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endisset
    @isset($popularTags)
        <div class="widget tag-widget">
            <h3>{{ __('Tags') }}</h3>
            <ul>
                @foreach($popularTags as $tag)
                    <li><a href="{{ route('blog.index', ['tag' => $tag->slug]) }}">{{ $tag->name }}</a></li>
                @endforeach
            </ul>
        </div>
    @endisset
    @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
        @php $adSidebar = app(\Modules\Ads\Services\AdsRenderer::class)->render('sidebar-rectangle'); @endphp
        @if($adSidebar)
            <div class="widget">{!! $adSidebar !!}</div>
        @endif
    @endif
    @if(Route::has('newsletter.subscribe'))
        <div class="wpo-contact-widget widget">
            <h2>{{ __('Restez informé') }}</h2>
            <p>{{ __('Inscrivez-vous pour recevoir nos derniers articles.') }}</p>
            <form action="{{ route('newsletter.subscribe') }}" method="POST">
                @csrf
                <div style="margin-bottom: 10px;">
                    <input type="email" name="email" class="form-control" placeholder="{{ __('Votre courriel') }}" required style="border-radius: 4px;">
                </div>
                <button type="submit" class="theme-btn" style="width: 100%; border: none; padding: 10px;">{{ __('S\'inscrire') }}</button>
            </form>
        </div>
    @else
        <div class="wpo-contact-widget widget">
            <h2>{{ __('Comment nous aider ?') }}</h2>
            <p>{{ __('Contactez-nous pour toute question ou suggestion.') }}</p>
            <a href="{{ route('contact') }}">{{ __('Contactez-nous') }}</a>
        </div>
    @endif
</div>
