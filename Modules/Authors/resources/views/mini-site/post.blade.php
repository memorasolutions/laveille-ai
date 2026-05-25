<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }} · {{ $author->display_name ?? $author->slug }}</title>
    <meta name="description" content="{{ $post->excerpt ?? \Illuminate\Support\Str::limit(strip_tags((string)$post->body_html), 200) }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:description" content="{{ $post->excerpt }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ route('authors.og-image', ['slug' => $author->slug, 'postSlug' => $post->slug]) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:image" content="{{ route('authors.og-image', ['slug' => $author->slug, 'postSlug' => $post->slug]) }}">
    @if($post->cover_image)
        <meta property="og:image:alt" content="{{ $post->title }}">
    @endif
    @if($post->published_at)
        <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
    @endif
    <meta property="article:author" content="{{ $author->display_name ?? $author->slug }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $post->title }}">
    <meta name="twitter:description" content="{{ $post->excerpt }}">

    @php $jsonLdEncoded = json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); @endphp
    <script type="application/ld+json">{!! $jsonLdEncoded !!}</script>

    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700&family=dm-sans:400,500&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js" defer></script>
    @livewireStyles
    <style>
        /* S110 Mode lecture distraction-free */
        body.lv-reader-mode .lv-reader-hide,
        body.lv-reader-mode .lv-section-wrap,
        body.lv-reader-mode .lv-post-back { display: none !important; }
        body.lv-reader-mode { background: #FBF9F4; }
        body.lv-reader-mode .lv-post-body { max-width: 65ch; margin: 0 auto; font-size: 19px; line-height: 1.8; color: #1F2937; padding: 0 20px 48px; }
        body.lv-reader-mode .lv-post-header { max-width: 65ch; margin: 0 auto; padding: 48px 20px 24px; }
        body.lv-reader-mode h1.lv-post-title { font-size: 36px; }
        .lv-reader-toggle { position: fixed; top: 16px; right: 16px; z-index: 100; background: #064E5A; color: #FFFFFF; border: none; border-radius: 999px; padding: 10px 16px; min-height: 44px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(6,78,90,0.25); font-family: 'Plus Jakarta Sans', system-ui, sans-serif; font-size: 14px; }
        .lv-reader-toggle:hover { background: #043C45; }
        .lv-reader-toggle:focus-visible { outline: 3px solid #9A2A06; outline-offset: 2px; }
        .lv-post-body { max-width: 65ch; margin: 0 auto; }
    </style>

    <style>
        body { margin: 0; font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif; background: #F8FAFB; color: #1F2937; line-height: 1.6; }
        .lv-post-header { max-width: 720px; margin: 0 auto; padding: 48px 24px 24px; }
        .lv-post-back { color: #0B7285; text-decoration: none; font-size: 14px; }
        .lv-post-back:hover, .lv-post-back:focus-visible { text-decoration: underline; outline: 2px solid #0B7285; outline-offset: 2px; }
        .lv-post-byline { display: flex; align-items: center; gap: 12px; margin: 16px 0 24px; }
        .lv-post-byline-avatar { background: linear-gradient(135deg, #0B7285, #C2410C); color: #FFFFFF; width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; flex-shrink: 0; }
        .lv-post-byline-info { display: flex; flex-direction: column; }
        .lv-post-byline-name { color: #0B7285; font-weight: 600; font-size: 15px; }
        .lv-post-byline-meta { color: #64748B; font-size: 13px; }
        h1.lv-post-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 40px; line-height: 1.2; color: #0F172A; margin: 24px 0 16px; }
        .lv-post-excerpt { font-size: 18px; color: #475569; font-style: italic; margin: 16px 0 32px; }
        .lv-post-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 16px 0; }
        .lv-post-tag { background: #E0F2F1; color: #0B7285; padding: 4px 12px; border-radius: 16px; font-size: 13px; font-weight: 600; }
        .lv-post-body { max-width: 720px; margin: 0 auto; padding: 0 24px 48px; }
        .lv-post-body h2 { font-family: 'Plus Jakarta Sans', sans-serif; color: #0B7285; font-size: 28px; margin: 32px 0 16px; }
        .lv-post-body h3 { font-family: 'Plus Jakarta Sans', sans-serif; color: #0B7285; font-size: 22px; margin: 24px 0 12px; }
        .lv-post-body p { margin: 16px 0; }
        .lv-post-body a { color: #C2410C; text-decoration: underline; }
        .lv-post-body a:focus-visible { outline: 3px solid #C2410C; outline-offset: 2px; }
        .lv-post-body blockquote { border-left: 4px solid #0B7285; padding: 8px 16px; margin: 24px 0; font-style: italic; color: #475569; background: #FFFFFF; border-radius: 0 8px 8px 0; }
        .lv-post-body code { background: #F1F5F9; padding: 2px 6px; border-radius: 4px; font-family: 'SF Mono', Monaco, Consolas, monospace; font-size: 14px; }
        .lv-post-body pre { background: #1E293B; color: #F8FAFB; padding: 16px; border-radius: 8px; overflow-x: auto; }
        .lv-post-body pre code { background: transparent; padding: 0; color: inherit; }
        .lv-post-body img { max-width: 100%; height: auto; border-radius: 8px; margin: 16px 0; }
        .lv-post-body ul, .lv-post-body ol { padding-left: 32px; margin: 16px 0; }
        .lv-post-cover { max-width: 720px; margin: 24px auto; padding: 0 24px; }
        .lv-post-cover img { width: 100%; height: auto; border-radius: 12px; }
        .lv-section-wrap { max-width: 720px; margin: 0 auto; padding: 24px; }
        @media (max-width: 640px) {
            h1.lv-post-title { font-size: 28px; }
        }
    </style>
    @stack('head')
</head>
@if($author->user && config('cashier.secret'))
    <script>
        // S114 — Tip button on post pages (gated cashier.secret server-side check)
    </script>
@endif
<body x-data="{ readerMode: localStorage.getItem('lv-reader-mode') === '1' }"
    :class="{ 'lv-reader-mode': readerMode }"
    x-init="if (readerMode) document.body.classList.add('lv-reader-mode')">
    @if($author->user && config('cashier.secret'))
        <div x-data="{ open: false, amount: 500 }"
             role="region"
             aria-label="Pourboires"
             style="position: fixed; bottom: 80px; right: 24px; z-index: 100;">
            <button type="button"
                    @click="open = !open"
                    @keydown.escape.window="open = false"
                    class="lv-tip-btn-post"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-controls="lv-tip-panel-post"
                    aria-label="Ouvrir menu pourboire">
                ☕ <span x-show="!open">Offrir un café</span><span x-show="open" x-cloak>Fermer</span>
            </button>
            <div id="lv-tip-panel-post"
                 x-show="open"
                 x-cloak
                 x-transition
                 @click.outside="open = false"
                 style="position: absolute; bottom: 60px; right: 0; background: #FFFFFF; border: 2px solid #064E5A; border-radius: 12px; padding: 16px; min-width: 240px; box-shadow: 0 8px 24px rgba(6,78,90,0.2);">
                <form method="POST" action="{{ url('/auteur/'.$author->slug.'/tip') }}">
                    @csrf
                    <fieldset style="border: 0; padding: 0; margin: 0 0 12px;">
                        <legend style="font-size: 13px; color: #064E5A; font-weight: 700; margin-bottom: 8px;">Montant</legend>
                        @foreach([500 => '5$', 1000 => '10$', 2000 => '20$'] as $cents => $label)
                            <label style="display: block; padding: 10px; min-height: 44px; cursor: pointer; border-radius: 8px; margin-bottom: 4px;"
                                   :style="amount === {{ $cents }} ? 'background: #E0F2F1; border: 2px solid #064E5A;' : 'background: #F8FAFB; border: 2px solid transparent;'">
                                <input type="radio" name="amount_cents" value="{{ $cents }}" x-model.number="amount"
                                       style="margin-right: 8px;" {{ $cents === 500 ? 'checked' : '' }}>
                                <span style="font-weight: 600;">{{ $label }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                    <button type="submit"
                            style="width: 100%; padding: 12px; min-height: 44px; background: #064E5A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;"
                            x-text="'💳 Offrir ' + (amount/100) + '$'">💳 Offrir 5$</button>
                </form>
            </div>
        </div>
        <style>
            .lv-tip-btn-post { background: #064E5A; color: #FFFFFF; border: none; border-radius: 999px; padding: 12px 20px; min-height: 44px; min-width: 44px; font-weight: 700; cursor: pointer; box-shadow: 0 6px 16px rgba(6,78,90,0.3); font-family: 'Plus Jakarta Sans', system-ui, sans-serif; font-size: 15px; transition: transform 200ms, background 200ms; }
            .lv-tip-btn-post:hover { background: #043C45; transform: scale(1.05); }
            .lv-tip-btn-post:focus-visible { outline: 3px solid #9A2A06; outline-offset: 2px; }
            [x-cloak] { display: none !important; }
        </style>
    @endif

    <button type="button" class="lv-reader-toggle"
        @click="readerMode = !readerMode; document.body.classList.toggle('lv-reader-mode'); localStorage.setItem('lv-reader-mode', readerMode ? '1' : '0')"
        :aria-pressed="readerMode"
        aria-label="Activer ou désactiver le mode lecture sans distraction">
        <span x-show="!readerMode">📖 Mode lecture</span>
        <span x-show="readerMode" x-cloak>✕ Mode normal</span>
    </button>

    @if(isset($isDraftPreview) && $isDraftPreview)
        <div role="status" style="background:#9A2A06; color:white; text-align:center; padding:12px; font-weight:600; position:sticky; top:0; z-index:1000;">
            👁️ Aperçu brouillon — cet article n'est pas encore publié
        </div>
    @endif

    <nav aria-label="Fil d'Ariane" class="lv-post-breadcrumb" style="max-width:720px; margin:0 auto; padding:24px 24px 0; font-size:13px; color:#5A6270;">
        <ol style="list-style:none; padding:0; margin:0; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
            <li><a href="{{ url('/') }}" style="color:#5A6270; text-decoration:underline;">Accueil</a></li>
            <li aria-hidden="true">›</li>
            <li><a href="/@{{ $author->slug }}" style="color:#5A6270; text-decoration:underline;">{{ $author->display_name ?? $author->slug }}</a></li>
            <li aria-hidden="true">›</li>
            <li aria-current="page" style="color:#064E5A; font-weight:600;">{{ \Illuminate\Support\Str::limit($post->title, 50) }}</li>
        </ol>
    </nav>

    <article>
        <header class="lv-post-header">
            <a href="{{ route('authors.mini-site.show', $author->slug) }}" class="lv-post-back">← Retour au profil</a>

            <h1 class="lv-post-title">{{ $post->title }}</h1>

            <div class="lv-post-byline">
                <span class="lv-post-byline-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($author->display_name ?? $author->slug, 0, 2)) }}</span>
                <div class="lv-post-byline-info">
                    <span class="lv-post-byline-name">{{ $author->display_name ?? $author->slug }}</span>
                    <span class="lv-post-byline-meta">
                        @if($post->published_at)
                            <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->translatedFormat('d F Y') }}</time>
                        @endif
                        @if($post->reading_time_minutes)
                            · {{ $post->reading_time_minutes }} min de lecture
                        @endif
                        @if($post->views_count)
                            · 👁 {{ $post->views_count }} vues
                        @endif
                    </span>
                </div>
            </div>

            @if($post->excerpt)
                <p class="lv-post-excerpt">{{ $post->excerpt }}</p>
            @endif

            @if(! empty($post->tags))
                <div class="lv-post-tags">
                    @foreach($post->tags as $tag)
                        <a class="lv-post-tag" href="{{ route('authors.tag-archive', ['slug' => $author->slug, 'tag' => $tag]) }}" aria-label="Voir tous les articles tagués {{ $tag }}">#{{ $tag }}</a>
                    @endforeach
                </div>
            @endif
        </header>

        @if($post->cover_image)
            <div class="lv-post-cover">
                <img src="{{ url($post->cover_image) }}" alt="Illustration de l'article : {{ $post->title }}">
            </div>
        @endif

        <div class="lv-post-body">
            {!! $post->body_html !!}
        </div>
    </article>

    @php
        $webmentions = \Modules\Authors\Models\AuthorWebmention::where('author_post_id', $post->id)
            ->verified()
            ->notSpam()
            ->latest('received_at')
            ->limit(50)
            ->get();
    @endphp
    <x-authors::reading-progress />

    <div class="lv-section-wrap" style="max-width: 720px; margin: 32px auto; padding: 0 24px;">
        <x-authors::share-buttons
            :title="$post->title"
            :url="url()->current()"
            :description="$post->excerpt ?? ''" />
    </div>

    <div class="lv-section-wrap" style="max-width: 1100px; margin: 32px auto; padding: 0 24px;">
        <livewire:authors.author-related-posts
            :author-profile-id="$author->id"
            :current-post-id="$post->id"
            :current-tags="is_array($post->tags) ? $post->tags : []" />
    </div>

    <div class="lv-section-wrap" style="max-width: 720px; margin: 32px auto; padding: 0 24px;">
        <x-authors::author-bio-card :author="$author" />
    </div>

    @if($webmentions->isNotEmpty())
        <div class="lv-section-wrap">
            <section aria-labelledby="lv-webmentions-title" class="lv-webmentions">
                <h2 id="lv-webmentions-title">🔗 Mentions du web ({{ $webmentions->count() }})</h2>
                <ul class="lv-webmentions-list">
                    @foreach($webmentions as $wm)
                        <li class="lv-webmention">
                            <header>
                                @if($wm->source_author_url)
                                    <a href="{{ e($wm->source_author_url) }}" rel="noopener" target="_blank">{{ e($wm->source_author_name ?? 'Anonyme') }}</a>
                                @else
                                    <strong>{{ e($wm->source_author_name ?? 'Anonyme') }}</strong>
                                @endif
                                <span class="lv-webmention-type">{{ $wm->type }}</span>
                                <time datetime="{{ $wm->received_at->toIso8601String() }}">{{ $wm->received_at->diffForHumans() }}</time>
                            </header>
                            @if($wm->source_excerpt)
                                <p class="lv-webmention-excerpt">{{ \Illuminate\Support\Str::limit($wm->source_excerpt, 200) }}</p>
                            @endif
                            <a href="{{ e($wm->source_url) }}" rel="noopener" target="_blank" class="lv-webmention-source">Voir la source →</a>
                        </li>
                    @endforeach
                </ul>
            </section>

            <style>
                .lv-webmentions { max-width: 720px; margin: 32px auto; padding: 24px; background: #F8FAFB; border-radius: 12px; }
                .lv-webmentions h2 { color: #064E5A; font-size: 22px; margin: 0 0 16px; }
                .lv-webmentions-list { list-style: none; padding: 0; margin: 0; }
                .lv-webmention { padding: 16px 0; border-bottom: 1px solid #E2E8F0; }
                .lv-webmention:last-child { border-bottom: none; }
                .lv-webmention header { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; font-size: 14px; }
                .lv-webmention header a, .lv-webmention header strong { color: #064E5A; font-weight: 600; }
                .lv-webmention-type { background: #064E5A; color: #FFFFFF; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
                .lv-webmention header time { color: #3A4050; font-size: 13px; margin-left: auto; }
                .lv-webmention-excerpt { color: #1F2937; line-height: 1.6; margin: 0 0 8px; }
                .lv-webmention-source { color: #9A2A06; font-size: 13px; text-decoration: underline; min-height: 44px; display: inline-flex; align-items: center; }
                .lv-webmention-source:focus-visible { outline: 3px solid #9A2A06; outline-offset: 2px; }
            </style>
        </div>
    @endif

    <div class="lv-section-wrap">
        <x-authors::comment-section :commentable="$post" :author-profile-id="$author->id" />
    </div>

    <div class="lv-section-wrap">
        <x-authors::newsletter-optin :author="$author" variant="inline" />
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
