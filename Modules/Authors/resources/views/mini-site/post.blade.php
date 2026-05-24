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
    @if($post->cover_image)
        <meta property="og:image" content="{{ url($post->cover_image) }}">
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
<body>
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
                        <span class="lv-post-tag">#{{ $tag }}</span>
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
