<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', ($article->seo_title ?? $article->title) . ' - ' . config('app.name'))
@section('meta_description', safe_excerpt($article->excerpt ?? strip_tags($article->content), 160))
@section('og_type', 'article')
@section('share_text')
@php
    $clean = fn($str) => str_replace("'", "\u{2019}", trim((string) $str));

    // Detection concentre (multi-critere robuste)
    $catName = strtolower((string) ($article->blogCategory?->name ?? $article->category?->name ?? ''));
    $catSlug = strtolower((string) ($article->blogCategory?->slug ?? $article->category?->slug ?? ''));
    $isConcentre = Str::startsWith($article->slug, ['concentre-', 'le-concentre-'])
        || in_array($catName, ['le concentré', 'le concentre', 'concentré', 'concentre'], true)
        || in_array($catSlug, ['le-concentre', 'concentre'], true)
        || collect($article->tags ?? [])->contains(fn($tag) => in_array(strtolower((string) $tag), ['concentré', 'concentre', 'hebdo', 'concentre-hebdo'], true));

    if ($isConcentre) {
        // Template A — Concentre hebdo : 100% derive du contenu reel (parsing dynamique)
        $hook = "🧠 " . $clean($article->title);

        // Tagline : excerpt ou premier paragraphe stripped
        $excerptRaw = (string) ($article->excerpt ?? '');
        if (mb_strlen($excerptRaw) < 50 && $article->content) {
            preg_match('/<p[^>]*>(.*?)<\/p>/is', (string) $article->content, $pm);
            $excerptRaw = $pm[1] ?? strip_tags((string) $article->content);
        }
        $tagline = $clean(mb_strimwidth(strip_tags((string) $excerptRaw), 0, 220, '…'));

        // TOUTES les h2 (vrai compte sections)
        preg_match_all('/<h2[^>]*>(.*?)<\/h2>/is', (string) $article->content, $h2Matches);
        $sections = collect($h2Matches[1] ?? [])
            ->map(fn($h) => trim(strip_tags((string) $h)))
            ->filter(fn($h) => $h !== '')
            ->values()
            ->all();

        $bullets = array_map(fn($s) => "• " . $clean($s), $sections);
        $count = count($sections);
        $countLabel = $count > 0
            ? "👉 {$count} " . ($count === 1 ? 'actu décryptée' : 'actus décryptées') . " pour nous autres."
            : "👉 À découvrir sur laveille.ai.";

        $lines = array_filter([
            $hook,
            '',
            $tagline,
            '',
            ...$bullets,
            '',
            $countLabel,
            $clean("💬 Laquelle t'a surpris·e? Partage ton top en commentaire."),
            "🔗 " . request()->url(),
            "#IAQuebec #VeilleIA",
            "Via @laveilleAI",
        ], fn($line) => $line !== null && $line !== false);
    } else {
        // Template B — Article normal (curiosity + teaser + meta inline)
        $title = $clean($article->title);
        $excerpt = $article->excerpt ?? strip_tags((string) $article->content);
        $teaser = $clean(Str::limit($excerpt, 180));

        $metaLine = null;
        if (!empty($article->user?->name) || !empty($article->blogCategory?->name)) {
            $authorName = $article->user?->name ? $clean($article->user->name) : null;
            $categoryName = $article->blogCategory?->name ? $clean($article->blogCategory->name) : null;
            $metaLine = implode(' · ', array_filter([$authorName, $categoryName]));
        }

        $lines = array_filter([
            "💡 {$title}",
            $teaser,
            $metaLine,
            "📖 Lecture sur laveille.ai : " . request()->url(),
            "#IA #VeilleQuebec",
            "Via @laveilleAI",
        ]);
    }

    echo implode("\n", $lines);
@endphp
@endsection
{{-- og:image jamais en WebP/AVIF (Facebook/LinkedIn n'affichent pas ces formats en aperçu de
     partage - aperçu vide et silencieux sans cette protection, audit 2026-08-22) : accesseur
     dédié featured_image_shareable_url (Article model), distinct de featured_image_url utilisé
     pour l'affichage normal (où le WebP reste légitime). --}}
@if($article->featured_image)
    @section('og_image', $article->featured_image_shareable_url.'?v='.($article->updated_at?->timestamp ?? '0'))
@endif

@php
    // #237 P27 : Person auteur harmonisé via lv_jsonld_author_stephane() (Schéma EEAT canonique).
    // Si l'article a un auteur custom, on garde son nom mais on enrichit avec le profil canonique.
    $__articleAuthor = $article->author && $article->author->name
        ? (function_exists('lv_jsonld_author_stephane') ? lv_jsonld_author_stephane() : ['@type' => 'Person', 'name' => $article->author->name])
        : (function_exists('lv_jsonld_author_stephane') ? lv_jsonld_author_stephane() : ['@type' => 'Person', 'name' => 'Stéphane Lapointe']);

    $schemaJson = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->title,
        'description' => Str::limit(strip_tags($article->excerpt ?? $article->content), 300),
        'datePublished' => $article->published_at?->toIso8601String(),
        'dateModified' => $article->updated_at->toIso8601String(),
        'author' => $__articleAuthor,
        'publisher' => function_exists('lv_jsonld_publisher')
            ? lv_jsonld_publisher()
            : [
                '@type' => 'Organization',
                'name' => 'La veille',
                'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo-avatar.png')],
            ],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url('/blog/' . $article->slug)],
    ] + ($article->featured_image ? ['image' => $article->featured_image_url] : []),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

@push('head')
    <meta name="llm:summary" content="{{ e($article->title) }} – {{ e(Str::limit(strip_tags($article->excerpt ?? $article->content ?? ''), 200)) }}">
    <meta name="llm:keywords" content="{{ e($article->title) }}, article, blog, IA, intelligence artificielle, francophone, Québec">
    <meta name="llm:url" content="{{ url('/blog/' . $article->slug) }}">
    {{-- CWV #238 — preload LCP image article + fetchpriority high --}}
    @if($article->featured_image)
    <link rel="preload" as="image" href="{{ $article->featured_image_url }}?v={{ $article->updated_at?->timestamp ?? '0' }}" fetchpriority="high">
    @endif
    <script type="application/ld+json">{!! $schemaJson !!}</script>
    @if($article->faqs->where('is_published', true)->isNotEmpty())
        {{-- #237 P27 : pre-encode dans bloc PHP pour eviter corruption directive Blade context (Laravel 11) --}}
        @php
            $__faqJson = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $article->faqs->where('is_published', true)->map(fn($faq) => [
                    '@type' => 'Question',
                    'name' => $faq->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($faq->answer),
                    ],
                ])->values()->all(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @endphp
        <script type="application/ld+json">{!! $__faqJson !!}</script>
    @endif
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $article->title,
        'breadcrumbItems' => [__('Blog'), $article->title]
    ])
@endsection

@can('view_admin_panel')
@include('core::components.admin-bar', [
    'label' => __('Article admin'),
    'model' => $article,
    'editUrl' => Route::has('admin.blog.articles.edit') ? route('admin.blog.articles.edit', $article) : null,
    'actions' => array_filter([
        Route::has('admin.blog.articles.edit') ? ['label' => __('Éditer'), 'icon' => 'pencil', 'url' => route('admin.blog.articles.edit', $article)] : null,
        Route::has('admin.blog.comments.index') ? ['label' => __('Modération commentaires'), 'icon' => 'shield', 'url' => route('admin.blog.comments.index'), 'target' => '_blank'] : null,
        ['divider' => true],
        Route::has('admin.blog.articles.destroy') ? ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.blog.articles.destroy', $article), 'method' => 'DELETE', 'confirm' => __('Supprimer cet article ?'), 'danger' => true] : null,
    ]),
])
@endcan

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
                                    <img src="{{ $article->featured_image_url }}?v={{ $article->updated_at?->timestamp ?? time() }}" alt="{{ $article->title }}" fetchpriority="high" loading="eager" decoding="async">
                                </div>
                            @endif
                            <div class="entry-meta">
                                <ul>
                                    <li><i class="fi flaticon-user"></i> {{ __('Par') }} <a href="{{ $article->isGuestPost() && $article->submitted_by ? route('directory.profile', $article->submitted_by) : '#' }}">{{ $article->getAuthorName() }}</a>@if($article->isGuestPost()) <span style="background: var(--c-primary); color: #fff; padding: 1px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-left: 4px;">{{ __('Auteur invité') }}</span>@endif</li>
                                    <li><i class="fi flaticon-calendar"></i> {{ $article->published_at?->translatedFormat('d M Y') }}</li>
                                    @if($article->blogCategory)
                                        <li><i class="fi flaticon-tag"></i> <a href="{{ route('blog.category', $article->blogCategory->slug) }}">{{ $article->blogCategory->name }}</a></li>
                                    @endif
                                </ul>
                            </div>
                            <h1 style="margin: 0 0 12px; font-size: 1.8rem;" data-editable="title">{{ $article->title }}</h1>
                            @php
                                $articleContent = $article->content;
                                if (! ($isPreview ?? false) && class_exists(\Modules\Ads\Services\AdsRenderer::class)) {
                                    $adsRenderer = app(\Modules\Ads\Services\AdsRenderer::class);
                                    $articleContent = $adsRenderer->renderShortcodes($articleContent);
                                    $articleContent = $adsRenderer->injectAfterParagraph($articleContent, 'article-inline', 3);
                                }
                                if (function_exists('render_shortcodes')) {
                                    $articleContent = render_shortcodes($articleContent);
                                }
                                // Composants Blade réutilisables (ex. <x-fronttheme::text-generator .../>)
                                // déjà rendus par PublicPostController::show() AVANT le début du rendu de
                                // cette vue (Blade::render() ne doit jamais s'exécuter au milieu d'un
                                // @section actif - corrompt la pile de sections partagée, incident 2026-07-03).
                                // AEO : sections wrappées, IDs sur headings, itemprop. DOIT précéder le wrap Sources :
                                // sinon le </div> injecté ferme le wrapper interne de chunkContent (DOMDocument) et la
                                // section Sources tombe hors $body → disparition silencieuse sur articles publiés (fix 2026-05-25).
                                if (! ($isPreview ?? false) && class_exists(\App\Helpers\AeoHelper::class)) {
                                    $articleContent = \App\Helpers\AeoHelper::chunkContent($articleContent);
                                }
                                // Encadrement visuel de la section "Sources"/"Références".
                                $lvSourcesHead = '(?:<(?:strong|b|em)>)?\s*(?:Sources?|Références?)\s*:?\s*(?:<\/(?:strong|b|em)>)?';
                                $lvSourcesCount = 0;
                                // Cas publié : chunkContent a produit des <section class="aeo-section"> → simple ajout de
                                // classe (HTML équilibré, aucun risque de casse au rendu).
                                $articleContent = preg_replace(
                                    '/<section class="aeo-section">(\s*<(?:h[2-4])[^>]*>' . $lvSourcesHead . '<\/(?:h[2-4])>)/i',
                                    '<section class="aeo-section sources-section">$1',
                                    $articleContent,
                                    1,
                                    $lvSourcesCount
                                );
                                // Cas preview (chunkContent non exécuté) : encadrement par div dédié.
                                if ($lvSourcesCount === 0) {
                                    $articleContent = preg_replace(
                                        '/(<(?:h[2-4])[^>]*>' . $lvSourcesHead . '<\/(?:h[2-4])>)/i',
                                        '</div><div class="sources-section">$1',
                                        $articleContent,
                                        1
                                    );
                                }
                                // Liens (externes ET internes) du contenu : ouverture dans un nouvel onglet
                                $articleContent = preg_replace(
                                    '/<a\b(?![^>]*\btarget=)/i',
                                    '<a target="_blank" rel="noopener noreferrer"',
                                    $articleContent
                                );
                                // 2026-05-05 #141 : auto-link glossaire/acronymes pour SEO/AEO/GEO. Calculé ICI
                                // (avant la barre d'action) pour que GlossaryLinkifier::getLastMatchedTerms()
                                // soit rempli quand le bouton toggle "Glossaire" de la barre d'action se rend
                                // (2026-07-25 #1358 : le bouton ne s'affichait jamais quand ce calcul se faisait
                                // après l'include de la barre d'action).
                                $articleContent = \Modules\Core\Services\GlossaryLinkifier::linkify($articleContent, ['per_section' => true, 'max_occ' => 1]);
                            @endphp
                            @include('fronttheme::partials.article-action-bar', ['model' => $article, 'modelType' => 'Modules\\Blog\\Models\\Article', 'adminShareItems' => auth()->user()?->isSuperAdmin() ? $article->adminShareContents() : null])

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
                                    <div class="rt-description">{!! \Illuminate\Support\Str::markdown($article->video_summary, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                                </div>
                            @endif

                            {{-- #165 GEO/AEO : answer-box « réponse d'abord » (rendu seulement si answer_summary rempli) --}}
                            <x-core::answer-box
                                :summary="$article->answer_summary ?? null"
                                :points="$article->answer_points ?? []"
                                :updated="$article->updated_at ? $article->updated_at->timezone('America/Toronto')->format('Y-m-d') : null"
                            />

                            {{-- #166 GEO/rebond : liens connexes au-dessus du pli (maillage vers le pilier + outils + reliés) --}}
                            <nav aria-label="{{ __('Liens connexes') }}" style="background-color:#f3f8f9;border-left:3px solid var(--c-primary,#064E5A);border-radius:8px;padding:12px 16px;margin-bottom:24px;font-size:0.95rem;">
                                <strong style="color:var(--c-primary,#064E5A);">💡 {{ __('Pour aller plus loin') }}</strong>
                                <span style="display:block;margin-top:6px;line-height:1.9;">
                                    <a href="{{ url('/outils/constructeur-prompts') }}" style="color:var(--c-primary,#064E5A);text-decoration:underline;">{{ __('Constructeur de prompts') }}</a>
                                    ·
                                    <a href="{{ url('/outils') }}" style="color:var(--c-primary,#064E5A);text-decoration:underline;">{{ __('Tous nos outils IA') }}</a>
                                    @isset($relatedArticles)
                                        @if($relatedArticles->isNotEmpty())
                                            @foreach($relatedArticles->take(2) as $r)
                                                ·
                                                {{-- 2026-08-31 (#2092) : accès brut au slug traduisible protégé par getPublicUrl(). --}}
                                                <a href="{{ $r->getPublicUrl() }}" style="color:var(--c-primary,#064E5A);text-decoration:underline;">{{ $r->title }}</a>
                                            @endforeach
                                        @endif
                                    @endisset
                                </span>
                            </nav>

                            {{-- Sommaire flottant + navigation par ancres (2026-07) : premier cas d'application, gabarit article de blog. --}}
                            <x-fronttheme::table-of-contents content-selector=".entry-details" />

                            <div class="entry-details">
                                {{-- $articleContent déjà calculé + linkifié plus haut (avant la barre d'action, cf. #1358) --}}
                                {!! $articleContent !!}
                            </div>
                            @include('core::partials.glossary-jsonld')
                            <script>
                                document.querySelectorAll('.entry-details a[href]').forEach(function (a) {
                                    if (! a.hasAttribute('target')) {
                                        a.setAttribute('target', '_blank');
                                        a.setAttribute('rel', 'noopener noreferrer');
                                    }
                                });
                            </script>
                        </div>

                        {{-- Module « vérification » étendu au blogue (2026-08-31) - strictement additif :
                             rend une LISTE de vérifications attachées, jamais un verdict global. Ne
                             rend rien du tout sur un article sans vérification (voir le composant). --}}
                        <x-blog::article-verifications :article="$article" />

                        @include('fronttheme::blog.partials.faq-accordion')

                        @include('fronttheme::partials.evergreen-related', ['haystack' => \Illuminate\Support\Str::lower(($article->title ?? '').' '.($article->blogCategory?->name ?? '').' '.(optional($article->tagsRelation)->pluck('name')->implode(' ') ?? ''))])

                        @include('fronttheme::partials.newsletter-native-cta')

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

                        @php
                            $author = $article->isGuestPost() ? $article->submittedByUser : $article->user;
                            $isMainAuthor = ! $article->isGuestPost();
                            $authorPhotoWebp = $isMainAuthor ? asset('images/author/stephane-lapointe-256.webp') : null;
                            $authorPhotoJpg = $isMainAuthor ? asset('images/author/stephane-lapointe-256.jpg') : ($author?->avatar ? asset('storage/' . $author->avatar) : asset('images/logo.webp'));
                            $authorLink = $isMainAuthor ? route('author.show', 'stephane-lapointe') : null;
                        @endphp
                        <div class="author-box" itemscope itemtype="https://schema.org/Person">
                            <div class="author-avatar">
                                @if($authorPhotoWebp)
                                    <picture>
                                        <source srcset="{{ $authorPhotoWebp }}" type="image/webp">
                                        <img src="{{ $authorPhotoJpg }}"
                                             alt="{{ $author->name ?? __('Auteur') }}"
                                             itemprop="image"
                                             loading="lazy"
                                             width="120" height="120"
                                             style="border-radius:50%;width:120px;height:120px;object-fit:cover;">
                                    </picture>
                                @else
                                    <img src="{{ $authorPhotoJpg }}"
                                         alt="{{ $author->name ?? __('Auteur') }}"
                                         itemprop="image"
                                         loading="lazy"
                                         style="border-radius:50%;width:120px;height:120px;object-fit:cover;">
                                @endif
                            </div>
                            <div class="author-content">
                                @if($authorLink)
                                    <a href="{{ $authorLink }}" rel="author" style="text-decoration:none;color:inherit;">
                                        <span class="author-name" itemprop="name">{{ $author->name ?? __('Auteur') }}</span>
                                    </a>
                                @else
                                    <span class="author-name" itemprop="name">{{ $author->name ?? __('Auteur') }}</span>
                                @endif
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
                            <a href="{{ $related->getPublicUrl() }}">
                                <img src="{{ $related->featured_image_url }}?v={{ $related->updated_at?->timestamp ?? time() }}" alt="{{ $related->title }}" style="width: 100%; height: 150px; object-fit: cover;">
                            </a>
                        @endif
                        <div class="panel-body">
                            <small class="text-muted">{{ $related->published_at?->translatedFormat('d M Y') }}</small>
                            <h4 style="font-size: 15px; font-weight: 600; margin: 6px 0 8px; line-height: 1.4;">
                                <a href="{{ $related->getPublicUrl() }}" style="color: inherit; text-decoration: none;">{{ $related->title }}</a>
                            </h4>
                            <p style="font-size: 13px; color: #374151; margin: 0;">{{ Str::limit($related->excerpt ?? strip_tags($related->content), 80) }}</p>
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
// #237 P27 : Person auteur harmonisé via helper canonique + clean @context (Blade @php block safe).
$blogPostingJsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    // 2026-08-31 (#2092) : $article->slug ici est sans risque réel (c'est l'article de LA page en
    // cours, résolu par liaison de route sur son propre slug - il ne peut pas être vide) mais
    // getPublicUrl() reste préférable pour rester cohérent avec le reste du fichier et le scanner.
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $article->getPublicUrl()],
    'headline' => $article->title,
    'description' => Str::limit($article->excerpt ?? strip_tags($article->content), 160),
    'image' => $article->featured_image_url ?: asset('images/og-image.png'),
    'datePublished' => $article->published_at?->toIso8601String(),
    'dateModified' => $article->updated_at?->toIso8601String(),
    'author' => function_exists('lv_jsonld_author_stephane')
        ? lv_jsonld_author_stephane()
        : ['@type' => 'Person', 'name' => $article->getAuthorName()],
    'publisher' => function_exists('lv_jsonld_publisher')
        ? lv_jsonld_publisher()
        : ['@type' => 'Organization', 'name' => config('app.name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/og-image.png')]],
    'articleSection' => $article->blogCategory?->name,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
@endphp
<script type="application/ld+json">
{!! $blogPostingJsonLd !!}
</script>
@endpush
@endsection
