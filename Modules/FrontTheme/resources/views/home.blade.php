<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', config('app.name') . ' - ' . __('Veille IA, technologies et transformation numerique au Quebec'))
@section('meta_description', __('Veille technologique collaborative sur l\'intelligence artificielle, les outils IA et la transformation numerique au Quebec. Articles, glossaire, repertoire et communaute.'))

@push('head')
@if(class_exists(\Modules\SEO\Services\JsonLdService::class))
{!! \Modules\SEO\Services\JsonLdService::render(
    \Modules\SEO\Services\JsonLdService::website(),
    \Modules\SEO\Services\JsonLdService::organization()
) !!}
@endif
@endpush

@section('content')
        <h1 class="sr-only">{{ config('app.name') }} — {{ __('Veille IA, technologies et transformation numérique au Québec') }}</h1>
        <!-- start of wpo-blog-hero -->
        <div class="wpo-blog-hero-area">
            <div class="container">
                <div class="sortable-gallery">
                    <div class="gallery-filters"></div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="wpo-blog-grids gallery-container clearfix">
                                @if($articles->count() > 0)
                                @php $hero1 = $articles[0]; @endphp
                                <div class="grid">
                                    <div class="img-holder">
                                        <img src="{{ $hero1->featured_image ? asset($hero1->featured_image) : fronttheme_asset('images/hero/img-1.jpg') }}" alt="{{ $hero1->title }}" class="img img-responsive">
                                        <div class="wpo-blog-content">
                                            <div class="thumb">{{ $hero1->blogCategory->name ?? __('Général') }}</div>
                                            <h2><a href="{{ route('blog.show', $hero1->slug) }}">{{ $hero1->title }}</a></h2>
                                            <p>{{ Str::limit($hero1->excerpt ?? strip_tags($hero1->content), 120) }}</p>
                                            <ul>
                                                <li><img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name') }}" style="width:30px;height:30px;border-radius:50%;"></li>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $hero1->slug) }}">{{ $hero1->getAuthorName() }}</a></li>
                                                <li>{{ $hero1->published_at?->translatedFormat('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($articles->count() > 1)
                                @php $hero2 = $articles[1]; @endphp
                                <div class="grid">
                                    <div class="img-holder">
                                        <img src="{{ $hero2->featured_image ? asset($hero2->featured_image) : fronttheme_asset('images/hero/img-2.jpg') }}" alt="{{ $hero2->title }}" class="img img-responsive">
                                        <div class="wpo-blog-content">
                                            <div class="thumb">{{ $hero2->blogCategory->name ?? __('Général') }}</div>
                                            <h2><a href="{{ route('blog.show', $hero2->slug) }}">{{ $hero2->title }}</a></h2>
                                            <ul>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $hero2->slug) }}">{{ $hero2->getAuthorName() }}</a></li>
                                                <li>{{ $hero2->published_at?->translatedFormat('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($articles->count() > 2)
                                <div class="grid s2">
                                    @php $hero3 = $articles[2]; @endphp
                                    <div class="img-holder">
                                        <img src="{{ $hero3->featured_image ? asset($hero3->featured_image) : fronttheme_asset('images/hero/img-3.jpg') }}" alt="{{ $hero3->title }}" class="img img-responsive">
                                        <div class="wpo-blog-content">
                                            <div class="thumb">{{ $hero3->blogCategory->name ?? __('Général') }}</div>
                                            <h2><a href="{{ route('blog.show', $hero3->slug) }}">{{ $hero3->title }}</a></h2>
                                            <ul>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $hero3->slug) }}">{{ $hero3->getAuthorName() }}</a></li>
                                                <li>{{ $hero3->published_at?->translatedFormat('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                    @if($articles->count() > 3)
                                    @php $hero4 = $articles[3]; @endphp
                                    <div class="img-holder">
                                        <img src="{{ $hero4->featured_image ? asset($hero4->featured_image) : fronttheme_asset('images/hero/img-4.jpg') }}" alt="{{ $hero4->title }}" class="img img-responsive">
                                        <div class="wpo-blog-content">
                                            <div class="thumb">{{ $hero4->blogCategory->name ?? __('Général') }}</div>
                                            <h2><a href="{{ route('blog.show', $hero4->slug) }}">{{ $hero4->title }}</a></h2>
                                            <ul>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $hero4->slug) }}">{{ $hero4->getAuthorName() }}</a></li>
                                                <li>{{ $hero4->published_at?->translatedFormat('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end of wpo-blog-hero -->

        <!-- start of wpo-breacking-news -->
        @if($latestNews->isNotEmpty())
        <div class="wpo-breacking-news section-padding">
            <div class="container">
                <div class="row">
                    <div class="b-title"><span>{{ __('Dernières actualités') }}</span></div>
                    <div class="wpo-breacking-wrap owl-carousel">
                        @foreach($latestNews->take(9) as $newsItem)
                        <div class="wpo-breacking-item{{ $loop->first ? ' s1' : '' }}">
                            <div class="wpo-breacking-img">
                                @if($newsItem->image_url)
                                    <img src="{{ $newsItem->image_url }}" alt="{{ $newsItem->seo_title ?? $newsItem->title }}" loading="lazy">
                                @else
                                    <div style="background: linear-gradient(135deg, #1a2332 0%, #0b7285 100%); display: flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 8px; color: rgba(255,255,255,0.3); font-weight: 700; font-size: 1.25rem;">
                                        {{ mb_strtoupper(mb_substr($newsItem->category_tag ?? 'N', 0, 2)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="wpo-breacking-text">
                                <span>{{ $newsItem->pub_date?->diffForHumans() }}</span>
                                <h3><a href="{{ route('news.show', $newsItem) }}">{{ $newsItem->seo_title ?? $newsItem->title }}</a></h3>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
        <!-- end of wpo-breacking-news -->

        <!-- start wpo-blog-highlights-section -->
        <section class="wpo-blog-highlights-section">
            <div class="container">
                <div class="wpo-section-title">
                    <h2>{{ __('Articles à la une') }}</h2>
                </div>
                <div class="row">
                    <div class="col col-lg-8 col-12">
                        <!-- start wpo-blog-section -->
                        <div class="wpo-blog-highlights-wrap">
                            <div class="wpo-blog-items">
                                <div class="row">
                                    @foreach($articles->take((int) \Modules\Settings\Facades\Settings::get('fronttheme.home_highlights_limit', 6)) as $highlight)
                                    <div class="col col-lg-6 col-md-6 col-12">
                                        <div class="wpo-blog-item">
                                            <div class="wpo-blog-img">
                                                <img src="{{ $highlight->featured_image ? asset($highlight->featured_image) : fronttheme_asset('images/blog/img-' . ($loop->iteration) . '.jpg') }}" alt="{{ $highlight->title }}" loading="lazy">
                                                <div class="thumb">{{ $highlight->blogCategory->name ?? __('Général') }}</div>
                                            </div>
                                            <div class="wpo-blog-content">
                                                <h2><a href="{{ route('blog.show', $highlight->slug) }}">{{ $highlight->title }}</a></h2>
                                                <ul>
                                                    <li><img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name') }}" style="width:30px;height:30px;border-radius:50%;" loading="lazy"></li>
                                                    <li>{{ __('Par') }} <a href="{{ route('blog.show', $highlight->slug) }}">{{ $highlight->getAuthorName() }}</a></li>
                                                    <li>{{ $highlight->published_at?->translatedFormat('d M Y') }}</li>
                                                </ul>
                                                <p>{{ Str::limit($highlight->excerpt ?? strip_tags($highlight->content), 100) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <!-- end wpo-blog-section -->
                    </div>
                    <div class="col col-lg-4 col-12">
                        <div class="blog-sidebar">
                            <div class="widget category-widget">
                                <h3>{{ __('Catégories') }}</h3>
                                <ul>
                                    @foreach($categories as $cat)
                                    <li><a href="{{ route('blog.category', $cat->slug) }}">{{ $cat->name }}<span>({{ str_pad((string)($cat->articles_count ?? 0), 2, '0', STR_PAD_LEFT) }})</span></a></li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="widget recent-post-widget">
                                <h3>{{ __('Articles populaires') }}</h3>
                                <div class="posts">
                                    @foreach($recentArticles as $recent)
                                    <div class="post">
                                        <div class="img-holder">
                                            @if($recent->featured_image)
                                                <img src="{{ asset($recent->featured_image) }}" alt="{{ $recent->title }}">
                                            @else
                                                <img src="{{ fronttheme_asset('images/recent-posts/img-' . ($loop->iteration) . '.jpg') }}" alt="{{ $recent->title }}">
                                            @endif
                                        </div>
                                        <div class="details">
                                            <span class="date">{{ $recent->published_at?->translatedFormat('d M Y') }}</span>
                                            <h4><a href="{{ route('blog.show', $recent->slug) }}">{{ $recent->title }}</a></h4>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end container -->
        </section>
        <!-- end wpo-blog-highlights-section -->

        <!-- start wpo-blog-sponsored-section -->
        <section class="wpo-blog-sponsored-section section-padding">
            <div class="container">
                <div class="wpo-section-title">
                    <h2>{{ __('Articles antérieurs') }}</h2>
                </div>
                <div class="row">
                    <div class="wpo-blog-sponsored-wrap">
                        <div class="wpo-blog-items">
                            <div class="row">
                                @foreach($articles->skip((int) \Modules\Settings\Facades\Settings::get('fronttheme.home_sponsored_skip', 6))->take((int) \Modules\Settings\Facades\Settings::get('fronttheme.home_sponsored_limit', 4)) as $sponsored)
                                <div class="col col-xl-3 col-lg-6 col-md-6 col-12">
                                    <div class="wpo-blog-item">
                                        <div class="wpo-blog-img">
                                            <img src="{{ $sponsored->featured_image ? asset($sponsored->featured_image) : fronttheme_asset('images/sponsord/img-' . ($loop->iteration) . '.jpg') }}" alt="{{ $sponsored->title }}">
                                            <div class="thumb">{{ $sponsored->blogCategory->name ?? __('Général') }}</div>
                                        </div>
                                        <div class="wpo-blog-content">
                                            <h2><a href="{{ route('blog.show', $sponsored->slug) }}">{{ $sponsored->title }}</a></h2>
                                            <ul>
                                                <li><img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name') }}" style="width:30px;height:30px;border-radius:50%;"></li>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $sponsored->slug) }}">{{ $sponsored->getAuthorName() }}</a></li>
                                                <li>{{ $sponsored->published_at?->translatedFormat('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end container -->
        </section>
        <!-- end wpo-blog-sponsored-section -->

        <!-- Sections dynamiques des ressources -->
        @push('styles')
        <style>
            .hp-section { padding: 40px 0; }
            .hp-section-alt { background: #F8FAFB; }
            .hp-header { margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid #eee; display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; }
            .hp-title { font-family: var(--f-heading); color: var(--c-dark); margin: 0; font-size: 1.5rem; font-weight: 700; }
            .hp-subtitle { color: #6B7280; font-size: 13px; margin-top: 4px; }
            .hp-link-all { color: var(--c-primary); font-weight: 600; font-size: 14px; text-decoration: none; padding: 6px 12px; min-height: 24px; display: inline-flex !important; align-items: center !important; }
            .hp-link-all:hover { text-decoration: underline; color: var(--c-primary); }
            .hp-card { display: block; background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; text-decoration: none !important; color: var(--c-dark); transition: transform 0.2s, box-shadow 0.2s; height: 100%; margin-bottom: 20px; }
            .hp-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); color: var(--c-dark); border-color: var(--c-primary); }
            .hp-card-img { height: 130px; overflow: hidden; position: relative; }
            .hp-card-img img { width: 100%; height: 100%; object-fit: cover; }
            .hp-card-img-gradient { height: 100%; display: flex !important; align-items: center !important; justify-content: center !important; }
            .hp-card-img-text { font-family: var(--f-heading); font-size: 16px; font-weight: 700; color: #fff; text-align: center; padding: 10px; }
            .hp-card-body { padding: 16px; }
            .hp-card-body h3 { margin: 0 0 8px; font-size: 1rem; font-weight: 700; font-family: var(--f-heading); color: var(--c-dark); }
            .hp-card-body p { color: #6B7280; font-size: 13px; line-height: 1.5; margin: 0 0 10px; }
            .hp-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; color: #fff; }
            .hp-badges { display: flex !important; gap: 6px !important; flex-wrap: wrap !important; }
            .hp-row-flex { display: flex !important; flex-wrap: wrap !important; }
            .hp-row-flex > [class*='col-'] { display: flex !important; flex-direction: column !important; }
            .hp-card-term-header { height: 100px; overflow: hidden; position: relative; display: flex !important; align-items: center !important; justify-content: center !important; }
            .hp-card-term-header img { width: 100%; height: 100%; object-fit: cover; }
            .hp-card-term-emoji { font-size: 2.5rem; text-shadow: 0 2px 8px rgba(0,0,0,0.3); }
            .hp-card-icon-area { padding: 24px 16px 8px; text-align: center; }
            .hp-card-icon-area .hp-icon { font-size: 2.5rem; display: block; margin-bottom: 4px; }
        </style>
        @endpush

        {{-- Section 0: Dernières actualités IA --}}
        @if($latestNews->isNotEmpty())
        <section class="hp-section">
            <div class="container">
                <div class="hp-header">
                    <div>
                        <h2 class="hp-title">📰 {{ __('Dernières actualités') }}</h2>
                        <div class="hp-subtitle">{{ __('Veille quotidienne IA et technologie') }}</div>
                    </div>
                    <a href="{{ route('news.index') }}" class="hp-link-all">{{ __('Voir tout') }} →</a>
                </div>
                <div class="row hp-row-flex">
                    @foreach($latestNews as $newsItem)
                    @php
                        $nScore = $newsItem->relevance_score ?? 0;
                        $nDotClass = $nScore >= 8 ? 'nw-dot-high' : ($nScore >= 6 ? 'nw-dot-mid' : 'nw-dot-low');
                        $nSs = $newsItem->structured_summary;
                    @endphp
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <a href="{{ route('news.show', $newsItem) }}" class="hp-card">
                            <div class="hp-card-img" style="{{ $newsItem->image_url ? '' : 'background: linear-gradient(135deg, #1a2332, #0b7285);' }}">
                                @if($newsItem->image_url)
                                    <img src="{{ $newsItem->image_url }}" alt="{{ $newsItem->seo_title ?? $newsItem->title }}" loading="lazy">
                                @else
                                    <div class="hp-card-img-gradient">
                                        <span class="hp-card-img-text">{{ mb_strtoupper(mb_substr($newsItem->category_tag ?? 'IA', 0, 2)) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="hp-card-body">
                                <h3>{{ Str::limit($newsItem->seo_title ?? $newsItem->title, 65) }}</h3>
                                <p>{{ Str::limit($nSs['hook'] ?? $newsItem->summary ?? strip_tags($newsItem->description), 90) }}</p>
                                <div class="hp-badges">
                                    <span class="hp-badge" style="background: var(--c-primary);">{{ $newsItem->source->name ?? __('Source') }}</span>
                                    <span style="font-size: 0.6875rem; color: #6B7280;">{{ $newsItem->pub_date?->diffForHumans() }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- Section 1: Outils IA populaires --}}
        @if($popularTools->isNotEmpty())
        <section class="hp-section">
            <div class="container">
                <div class="hp-header">
                    <div>
                        <h2 class="hp-title">🔍 {{ __('Outils IA populaires') }}</h2>
                        <div class="hp-subtitle">{{ __('Les solutions les plus utilisées par la communauté') }}</div>
                    </div>
                    <a href="{{ route('directory.index') }}" class="hp-link-all">{{ __('Voir tout') }} →</a>
                </div>
                <div class="row hp-row-flex">
                    @foreach($popularTools as $tool)
                    @php
                        $screenshotSrc = $tool->screenshot ? (str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot)) : '';
                        $gradientColors = ['#0B7285','#064E5C','#E67E22','#D46A1F','#1A1D23','#2D3039'];
                        $gIdx = abs(crc32($tool->name)) % count($gradientColors);
                        $pricingLabels = ['free' => __('Gratuit'), 'freemium' => 'Freemium', 'paid' => __('Payant'), 'open_source' => 'Open source'];
                        $pricingColors = ['free' => '#059669', 'freemium' => '#B45309', 'paid' => '#B91C1C', 'open_source' => '#6366F1'];
                    @endphp
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <a href="{{ route('directory.show', $tool->slug) }}" class="hp-card">
                            <div class="hp-card-img" style="{{ $screenshotSrc ? '' : 'background: linear-gradient(135deg, ' . $gradientColors[$gIdx] . ', ' . $gradientColors[($gIdx + 1) % count($gradientColors)] . ');' }}">
                                @if($screenshotSrc)
                                    <img src="{{ $screenshotSrc }}" alt="{{ $tool->name }}" loading="lazy">
                                @else
                                    <div class="hp-card-img-gradient">
                                        <span class="hp-card-img-text">{{ $tool->name }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="hp-card-body">
                                <h3>{{ $tool->name }}</h3>
                                <p>{{ Str::limit($tool->short_description, 80) }}</p>
                                <div class="hp-badges">
                                    <span class="hp-badge" style="background: {{ $pricingColors[$tool->pricing] ?? '#6B7280' }};">{{ $pricingLabels[$tool->pricing] ?? ucfirst($tool->pricing) }}</span>
                                    @if($tool->categories->isNotEmpty())
                                        <span class="hp-badge" style="background: {{ $tool->categories->first()->color ?? 'var(--c-primary)' }};">{{ $tool->categories->first()->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- Section 2: Termes IA à découvrir --}}
        @if($featuredTerms->isNotEmpty())
        <section class="hp-section hp-section-alt">
            <div class="container">
                <div class="hp-header">
                    <div>
                        <h2 class="hp-title">📚 {{ __('Termes IA à découvrir') }}</h2>
                        <div class="hp-subtitle">{{ __('Enrichissez votre vocabulaire technique') }}</div>
                    </div>
                    <a href="{{ route('dictionary.index') }}" class="hp-link-all">{{ __('Voir tout') }} →</a>
                </div>
                <div class="row hp-row-flex">
                    @foreach($featuredTerms as $term)
                    @php
                        $heroSrc = $term->hero_image ? (str_starts_with($term->hero_image, 'http') ? $term->hero_image : asset($term->hero_image)) : '';
                        $diffColor = match($term->difficulty ?? 'beginner') { 'beginner' => '#059669', 'intermediate' => '#B45309', 'advanced' => '#B91C1C', default => '#6B7280' };
                        $diffLabel = match($term->difficulty ?? 'beginner') { 'beginner' => __('Débutant'), 'intermediate' => __('Intermédiaire'), 'advanced' => __('Avancé'), default => __('Débutant') };
                    @endphp
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <a href="{{ route('dictionary.show', $term->slug) }}" class="hp-card">
                            <div class="hp-card-term-header" style="{{ $heroSrc ? '' : 'background: linear-gradient(135deg, var(--c-primary), var(--c-dark));' }}">
                                @if($heroSrc)
                                    <img src="{{ $heroSrc }}" alt="{{ $term->name }}" loading="lazy">
                                @else
                                    <span class="hp-card-term-emoji">{{ $term->icon ?? '📄' }}</span>
                                @endif
                            </div>
                            <div class="hp-card-body">
                                <div style="display: flex !important; justify-content: space-between !important; align-items: flex-start !important; margin-bottom: 8px;">
                                    <h3 style="margin: 0;">{{ $term->name }}</h3>
                                    <span class="hp-badge" style="background: {{ $diffColor }}; flex-shrink: 0; margin-left: 8px;">{{ $diffLabel }}</span>
                                </div>
                                <p>{{ Str::limit(strip_tags($term->definition), 80) }}</p>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- Section 3: Acronymes éducation --}}
        @if($featuredAcronyms->isNotEmpty())
        <section class="hp-section">
            <div class="container">
                <div class="hp-header">
                    <div>
                        <h2 class="hp-title">🎓 {{ __('Acronymes éducation au Québec') }}</h2>
                        <div class="hp-subtitle">{{ __('Comprendre le jargon du milieu éducatif québécois') }}</div>
                    </div>
                    <a href="{{ route('acronyms.index') }}" class="hp-link-all">{{ __('Voir tout') }} →</a>
                </div>
                <div class="row hp-row-flex">
                    @foreach($featuredAcronyms as $acronym)
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <a href="{{ route('acronyms.show', $acronym->getTranslation('slug', app()->getLocale())) }}" class="hp-card">
                            <div class="hp-card-body">
                                <div style="display: flex !important; align-items: center !important; gap: 10px; margin-bottom: 10px;">
                                    @if($acronym->logo_url)
                                        <img src="{{ $acronym->logo_url }}" alt="{{ $acronym->acronym }}" style="width: 40px; height: 40px; object-fit: contain; border-radius: 50%; background: #F3F4F6; flex-shrink: 0;" loading="lazy">
                                    @else
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: {{ $acronym->category?->color ?? '#6B7280' }}; color: #fff; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: 700; font-size: 15px; flex-shrink: 0;">{{ substr($acronym->acronym, 0, 1) }}</div>
                                    @endif
                                    <h3 style="margin: 0; font-size: 1.1rem;">{{ $acronym->acronym }}</h3>
                                </div>
                                <p style="font-weight: 500; margin-bottom: 10px;">{{ Str::limit($acronym->full_name, 60) }}</p>
                                @if($acronym->category)
                                    <span class="hp-badge" style="background: {{ $acronym->category->color ?? '#6B7280' }};">{{ $acronym->category->name }}</span>
                                @endif
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- Section 4: Outils gratuits --}}
        @if($interactiveTools->isNotEmpty())
        <section class="hp-section hp-section-alt">
            <div class="container">
                <div class="hp-header">
                    <div>
                        <h2 class="hp-title">🛠️ {{ __('Outils gratuits') }}</h2>
                        <div class="hp-subtitle">{{ __('Des utilitaires interactifs à votre disposition') }}</div>
                    </div>
                    <a href="{{ route('tools.index') }}" class="hp-link-all">{{ __('Voir tout') }} →</a>
                </div>
                <div class="row hp-row-flex">
                    @foreach($interactiveTools as $tool)
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <a href="{{ route('tools.show', $tool->slug) }}" class="hp-card">
                            <div class="hp-card-icon-area">
                                <span class="hp-icon">{{ $tool->icon ?? '⚡' }}</span>
                            </div>
                            <div class="hp-card-body" style="text-align: center;">
                                <h3>{{ $tool->name }}</h3>
                                <p>{{ Str::limit($tool->description, 60) }}</p>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        <!-- start wpo-subscribe-section -->
        <section class="wpo-subscribe-section section-padding">
            <div class="container">
                <div class="wpo-subscribe-wrap">
                    <div class="subscribe-text">
                        <h3>{{ __('Ne manquez aucune mise à jour !') }}</h3>
                        <p>{{ __('Recevez les dernières nouvelles et mises à jour directement dans votre boîte courriel.') }}</p>
                    </div>
                    <div class="subscribe-form">
                        <form action="{{ Route::has('newsletter.subscribe') ? route('newsletter.subscribe') : '#' }}" method="POST">
                            @csrf
                            <div class="input-field">
                                <input type="email" name="email" placeholder="{{ __('Entrez votre courriel') }}" required autocomplete="email" aria-label="{{ __('Adresse courriel pour l\'infolettre') }}">
                                <button type="submit"><i class="fi flaticon-send"></i> {{ __('S\'inscrire') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div> <!-- end container -->
        </section>
        <!-- end subscribe-section -->
@endsection
