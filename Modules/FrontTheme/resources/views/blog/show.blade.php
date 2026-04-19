<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $article->title . ' - ' . config('app.name'))
@section('meta_description', Str::limit($article->excerpt ?? strip_tags($article->content), 160))
@section('og_type', 'article')
@section('share_text')
@php
    // Détecte si c'est un Concentré hebdo (Template A) ou un article standard (Template B)
    $isConcentre = Str::startsWith($article->slug, 'le-concentre-de-la-semaine') ||
                   collect($article->tags ?? [])->contains(fn($tag) => in_array(strtolower($tag), ['concentré', 'hebdo', 'concentre-hebdo']));

    $clean = fn($str) => str_replace('\'', "\u{2019}", trim($str));

    if ($isConcentre) {
        // Template A — Concentré hebdo (curiosity gap #3 + liste #1 sonar-pro 2026)
        $hook = $clean("🧠 La semaine IA qui a tout fait basculer au Québec");
        $curiosity = $clean("GPT-5.4 qui bat Erdős, agents autonomes, Adobe qui redéfinit la création…");

        preg_match_all('/<h2[^>]*>(.*?)<\/h2>/', $article->content, $h2Matches);
        $bullets = collect($h2Matches[1] ?? [])
            ->map(fn($h) => "• " . $clean(strip_tags($h)))
            ->take(5)
            ->values()
            ->toArray();

        $lines = array_filter([
            $hook,
            $curiosity,
            '',
            ...$bullets,
            '',
            $clean("👉 10 actus décryptées pour nous autres."),
            $clean("💬 Laquelle t'a surpris·e? Partage ton top en com."),
            "🔗 " . request()->url(),
            "#IAQuebec #VeilleIA",
            "Via @laveilleAI",
        ]);
    } else {
        // Template B — Article normal (curiosity + teaser + meta inline)
        $title = $clean($article->title);
        $excerpt = $article->excerpt ?? strip_tags($article->content);
        $teaser = $clean(Str::limit($excerpt, 180));

        $metaLine = null;
        if (!empty($article->author?->name) || !empty($article->category?->name)) {
            $author = $article->author?->name ? $clean($article->author->name) : null;
            $category = $article->category?->name ? $clean($article->category->name) : null;
            $metaLine = implode(' · ', array_filter([$author, $category]));
        }

        $lines = array_filter([
            "💡 {$title}",
            $teaser,
            $metaLine,
            "📖 Lecture 5-7 min : " . request()->url(),
            "#IA #VeilleQuebec",
            "Via @laveilleAI",
        ]);
    }

    echo implode("\n", $lines);
@endphp
@endsection
@if($article->featured_image)
    @section('og_image', asset($article->featured_image))
@endif

@php
    $schemaJson = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->title,
        'description' => Str::limit(strip_tags($article->excerpt ?? $article->content), 300),
        'datePublished' => $article->published_at?->toIso8601String(),
        'dateModified' => $article->updated_at->toIso8601String(),
        'author' => [
            '@type' => 'Person',
            'name' => $article->author->name ?? 'La veille',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'La veille',
            'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo-avatar.png')],
        ],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url('/blog/' . $article->slug)],
    ] + ($article->featured_image ? ['image' => asset($article->featured_image)] : []),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

@push('head')
    <meta name="llm:summary" content="{{ e($article->title) }} — {{ e(Str::limit(strip_tags($article->excerpt ?? $article->content ?? ''), 200)) }}">
    <meta name="llm:keywords" content="{{ e($article->title) }}, article, blog, IA, intelligence artificielle, francophone, Québec">
    <meta name="llm:url" content="{{ url('/blog/' . $article->slug) }}">
    <script type="application/ld+json">{!! $schemaJson !!}</script>
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $article->title,
        'breadcrumbItems' => [__('Blog'), $article->title]
    ])
@endsection

@auth
@include('core::components.admin-bar', [
    'label' => __('Article admin'),
    'actions' => array_filter([
        Route::has('admin.blog.articles.edit') ? ['label' => __('Éditer'), 'icon' => 'pencil', 'url' => route('admin.blog.articles.edit', $article)] : null,
        Route::has('admin.blog.comments.index') ? ['label' => __('Modération commentaires'), 'icon' => 'shield', 'url' => route('admin.blog.comments.index'), 'target' => '_blank'] : null,
        ['divider' => true],
        Route::has('admin.blog.articles.destroy') ? ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.blog.articles.destroy', $article), 'method' => 'DELETE', 'confirm' => __('Supprimer cet article ?'), 'danger' => true] : null,
    ]),
])
@if(Route::has('admin.blog.articles.edit'))
    @include('core::components.mode-toggle', ['editUrl' => route('admin.blog.articles.edit', $article)])
@endif
@include('core::components.admin-activity-mini', ['model' => $article])
@endauth

@section('content')
    <!-- start wpo-blog-single-section -->
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8 col-12">
                    <div class="wpo-blog-content">
                        <div class="post format-standard-image">
                            @if($article->featured_image)
                                <div class="entry-media">
                                    <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}">
                                </div>
                            @endif
                            <div class="entry-meta">
                                <ul>
                                    <li><i class="fi flaticon-user"></i> {{ __('Par') }} <a href="{{ $article->isGuestPost() && $article->submitted_by ? route('directory.profile', $article->submitted_by) : '#' }}">{{ $article->getAuthorName() }}</a>@if($article->isGuestPost()) <span style="background: var(--c-primary); color: #fff; padding: 1px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-left: 4px;">{{ __('Auteur invité') }}</span>@endif</li>
                                    <li><i class="fi flaticon-calendar"></i> {{ $article->published_at?->format('d M Y') }}</li>
                                    @if($article->blogCategory)
                                        <li><i class="fi flaticon-tag"></i> <a href="{{ route('blog.category', $article->blogCategory->slug) }}">{{ $article->blogCategory->name }}</a></li>
                                    @endif
                                </ul>
                            </div>
                            <h1 style="margin: 0 0 12px; font-size: 1.8rem;" data-editable="title">{{ $article->title }}</h1>
                            @include('fronttheme::partials.article-action-bar', ['model' => $article, 'modelType' => 'Modules\\Blog\\Models\\Article'])

                            {{-- Navigation série (détection automatique par slug "-partie-N") --}}
                            @include('fronttheme::partials.series-nav', ['article' => $article])

                            @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
                                {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('article-top') !!}
                            @endif

                            @if($article->video_url)
                                <div class="video-embed mb-4">
                                    @php
                                        $ytId = null;
                                        if (class_exists(\Modules\AI\Services\YouTubeService::class)) {
                                            $ytId = \Modules\AI\Services\YouTubeService::getVideoId($article->video_url);
                                        } elseif (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $article->video_url, $m)) {
                                            $ytId = $m[1];
                                        }
                                    @endphp
                                    @if($ytId)
                                        <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:8px;">
                                            <iframe src="https://www.youtube-nocookie.com/embed/{{ $ytId }}" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy" title="{{ $article->title }}"></iframe>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if($article->video_summary)
                                <div class="video-summary mb-4 p-3" style="background:#f0f7ff;border-left:4px solid var(--c-primary);border-radius:6px;">
                                    <h5 style="margin-bottom:10px;"><i class="fi flaticon-play-button" style="margin-right:6px;"></i>Résumé de la vidéo</h5>
                                    <div class="rt-description">{!! \Illuminate\Support\Str::markdown($article->video_summary) !!}</div>
                                </div>
                            @endif

                            <div class="entry-details">
                                @php
                                    $articleContent = $article->content;
                                    if (! ($isPreview ?? false) && class_exists(\Modules\Ads\Services\AdsRenderer::class)) {
                                        $adsRenderer = app(\Modules\Ads\Services\AdsRenderer::class);
                                        $articleContent = $adsRenderer->renderShortcodes($articleContent);
                                        $articleContent = $adsRenderer->injectAfterParagraph($articleContent, 'article-inline', 3);
                                    }
                                    // Regex plus strict : matche uniquement un heading ou paragraphe dont le TITRE commence par
                                    // "Sources" ou "Références" (avec strong/b optionnel), suivi d'un ":" — évite les faux positifs sur "open source"
                                    $articleContent = preg_replace(
                                        '/(<(?:h[2-4])[^>]*>(?:<(?:strong|b|em)>)?\s*(?:Sources?|Références?)\s*:?\s*(?:<\/(?:strong|b|em)>)?\s*<\/(?:h[2-4])>)/i',
                                        '</div><div class="sources-section">$1',
                                        $articleContent,
                                        1
                                    );
                                    if (function_exists('render_shortcodes')) {
                                        $articleContent = render_shortcodes($articleContent);
                                    }
                                    // AEO : sections wrappées, IDs sur headings, itemprop sur premiers paragraphes
                                    // (skip en mode preview pour éviter le bug DOMDocument avec HTML complexe)
                                    if (! ($isPreview ?? false) && class_exists(\App\Helpers\AeoHelper::class)) {
                                        $articleContent = \App\Helpers\AeoHelper::chunkContent($articleContent);
                                    }
                                @endphp
                                {!! $articleContent !!}
                            </div>
                        </div>

                        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
                            {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('article-bottom') !!}
                        @endif

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

                        @php $author = $article->isGuestPost() ? $article->submittedByUser : $article->user; @endphp
                        <div class="author-box" itemscope itemtype="https://schema.org/Person">
                            <div class="author-avatar">
                                <img src="{{ $author?->avatar ? asset('storage/' . $author->avatar) : asset('images/logo.webp') }}"
                                     alt="{{ $author->name ?? __('Auteur') }}"
                                     itemprop="image"
                                     loading="lazy"
                                     style="border-radius:50%;width:120px;height:120px;object-fit:cover;">
                            </div>
                            <div class="author-content">
                                <span class="author-name" itemprop="name">{{ $author->name ?? __('Auteur') }}</span>
                                <p itemprop="description">{{ $author->bio ?? __('Merci de lire nos articles.') }}</p>
                                @if($author?->social_links)
                                    <div class="author-social" style="margin-top:8px;">
                                        @foreach($author->social_links as $platform => $url)
                                            @if($url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" itemprop="sameAs" title="{{ ucfirst($platform) }}" style="margin-right:10px;color:var(--c-primary);">
                                                    @if($platform === 'twitter') <i class="fi flaticon-twitter"></i>
                                                    @elseif($platform === 'linkedin') <i class="fi flaticon-linkedin"></i>
                                                    @elseif($platform === 'github') <i class="ti-github"></i>
                                                    @elseif($platform === 'website') <i class="fi flaticon-link"></i>
                                                    @else <i class="fi flaticon-link"></i>
                                                    @endif
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(class_exists(\Modules\Community\Livewire\CommentsThread::class))
                            <div class="mt-4 pt-4 border-top">
                                @livewire('community-comments-thread', ['commentableType' => \Modules\Blog\Models\Article::class, 'commentableId' => $article->id])
                            </div>
                        @endif

                    </div>
                </div>
                <div class="col col-lg-4 col-12 d-none d-lg-block">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div>
    </section>
    <!-- end wpo-blog-single-section -->

    {{-- Navigation série en bas d'article --}}
    <div class="container">
        @include('fronttheme::partials.series-nav', ['article' => $article])
    </div>

    @if($relatedArticles->isNotEmpty())
    <section style="padding: 30px 0 40px; background: #f9fafb;">
        <div class="container">
            <h3 style="font-weight: 700; margin-bottom: 24px;">{{ __('Articles reliés') }}</h3>
            <div class="row">
                @foreach($relatedArticles as $related)
                <div class="col-sm-4" style="margin-bottom: 20px;">
                    <div class="panel panel-default" style="border-radius: 8px; overflow: hidden;">
                        @if($related->featured_image)
                            <a href="{{ route('blog.show', $related->slug) }}">
                                <img src="{{ asset($related->featured_image) }}" alt="{{ $related->title }}" style="width: 100%; height: 150px; object-fit: cover;">
                            </a>
                        @endif
                        <div class="panel-body">
                            <small class="text-muted">{{ $related->published_at?->format('d M Y') }}</small>
                            <h4 style="font-size: 15px; font-weight: 600; margin: 6px 0 8px; line-height: 1.4;">
                                <a href="{{ route('blog.show', $related->slug) }}" style="color: inherit; text-decoration: none;">{{ $related->title }}</a>
                            </h4>
                            <p style="font-size: 13px; color: #6B7280; margin: 0;">{{ Str::limit($related->excerpt ?? strip_tags($related->content), 80) }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

@push('scripts')
@php
$blogPostingJsonLd = json_encode([
    chr(64).'context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('blog.show', $article->slug)],
    'headline' => $article->title,
    'description' => Str::limit($article->excerpt ?? strip_tags($article->content), 160),
    'image' => $article->featured_image ? asset($article->featured_image) : asset('images/og-image.png'),
    'datePublished' => $article->published_at?->toIso8601String(),
    'dateModified' => $article->updated_at?->toIso8601String(),
    'author' => ['@type' => 'Person', 'name' => $article->getAuthorName()],
    'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/og-image.png')]],
    'articleSection' => $article->blogCategory?->name,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
@endphp
<script type="application/ld+json">
{!! $blogPostingJsonLd !!}
</script>
@endpush
@endsection
