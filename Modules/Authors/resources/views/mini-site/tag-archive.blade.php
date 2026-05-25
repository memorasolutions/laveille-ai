<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles : {{ $tag }} · {{ $author->display_name ?? $author->slug }}</title>
    <meta name="description" content="Tous les articles tagués « {{ $tag }} » par {{ $author->display_name ?? $author->slug }} sur laveille.ai.">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Articles : {{ $tag }} · {{ $author->display_name ?? $author->slug }}">
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700|dm-sans:400,500" rel="stylesheet">
    <style>
        :root { --c-primary:#064E5A; --c-accent:#9A2A06; --c-cream:#F8FAFB; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:'DM Sans',-apple-system,sans-serif; background:var(--c-cream); color:#1F2937; line-height:1.6; }
        a { color:var(--c-accent); text-decoration:none; }
        a:focus-visible { outline:3px solid var(--c-accent); outline-offset:2px; }
        .topbar { padding:12px 24px; background:white; border-bottom:1px solid #e2e8f0; display:flex; gap:16px; align-items:center; }
        .topbar a { font-weight:600; color:var(--c-primary); min-height:44px; display:inline-flex; align-items:center; }
        .breadcrumb { padding:12px 24px; font-size:13px; color:#5A6270; max-width:1100px; margin:0 auto; }
        .breadcrumb ol { display:flex; flex-wrap:wrap; gap:8px; list-style:none; padding:0; margin:0; align-items:center; }
        .breadcrumb a { color:#5A6270; text-decoration:underline; }
        main { max-width:1100px; margin:0 auto; padding:24px; }
        h1 { font-family:'Plus Jakarta Sans',sans-serif; font-size:32px; color:var(--c-primary); margin:0 0 24px; }
        .posts-grid { display:grid; gap:24px; grid-template-columns:1fr; }
        @media (min-width:640px){ .posts-grid{ grid-template-columns:repeat(2,1fr);} }
        @media (min-width:1024px){ .posts-grid{ grid-template-columns:repeat(3,1fr);} }
        .post-card { background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(6,78,90,0.08); border:1px solid rgba(6,78,90,0.08); display:flex; flex-direction:column; transition:transform 200ms,box-shadow 200ms; }
        @media (prefers-reduced-motion: reduce){ .post-card{ transition:none; } }
        .post-card:hover { transform:translateY(-4px); box-shadow:0 10px 24px rgba(6,78,90,0.15); }
        .post-card__img, .post-card__ph { width:100%; aspect-ratio:16/9; object-fit:cover; display:block; }
        .post-card__ph { background:linear-gradient(135deg,hsl(190,30%,88%),hsl(190,20%,94%)); }
        .post-card__body { padding:16px; flex:1; display:flex; flex-direction:column; }
        .post-card h2 { font-family:'Plus Jakarta Sans',sans-serif; font-size:18px; margin:0 0 8px; line-height:1.3; }
        .post-card h2 a { color:var(--c-primary); }
        .post-card__excerpt { font-size:14px; color:#3F4554; margin:0 0 12px; flex:1; }
        .post-card__meta { font-size:12px; color:#5A6270; display:flex; gap:8px; }
        .pagination { display:flex; justify-content:center; gap:12px; margin-top:32px; }
        .pagination a, .pagination span { min-height:44px; display:inline-flex; align-items:center; padding:8px 16px; border-radius:8px; font-weight:600; }
        .pagination a { background:var(--c-primary); color:white; }
        .pagination .disabled { opacity:0.4; color:#5A6270; }
        footer { padding:24px; text-align:center; font-size:13px; color:#5A6270; border-top:1px solid #e2e8f0; margin-top:48px; }
    </style>
</head>
<body>
    <nav class="topbar" aria-label="Navigation">
        <a href="{{ route('authors.mini-site.show', $author->slug) }}">← {{ $author->display_name ?? $author->slug }}</a>
        <a href="{{ url('/') }}">laveille.ai</a>
    </nav>

    <nav class="breadcrumb" aria-label="Fil d'Ariane">
        <ol>
            <li><a href="{{ url('/') }}">Accueil</a></li>
            <li aria-hidden="true">›</li>
            <li><a href="{{ route('authors.mini-site.show', $author->slug) }}">{{ $author->display_name ?? $author->slug }}</a></li>
            <li aria-hidden="true">›</li>
            <li aria-current="page" style="color:var(--c-primary);font-weight:600;">Tag {{ $tag }}</li>
        </ol>
    </nav>

    <main>
        <h1>🏷️ Articles : {{ $tag }} <span style="font-size:18px;color:#5A6270;font-weight:400;">({{ $posts->total() }})</span></h1>

        <div class="posts-grid">
            @foreach($posts as $post)
                <article class="post-card">
                    @if($post->cover_image)
                        <img src="{{ $post->cover_image }}" alt="" loading="lazy" decoding="async" class="post-card__img">
                    @else
                        <div class="post-card__ph" aria-hidden="true"></div>
                    @endif
                    <div class="post-card__body">
                        <h2><a href="{{ route('authors.post.show', [$author->slug, $post->slug]) }}">{{ $post->title }}</a></h2>
                        <p class="post-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags((string) ($post->excerpt ?: $post->body_html)), 100) }}</p>
                        <div class="post-card__meta">
                            <span>{{ $post->reading_time ?? 1 }} min</span>
                            @if($post->published_at)
                                <span aria-hidden="true">•</span>
                                <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->diffForHumans() }}</time>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <nav class="pagination" aria-label="Pagination">
            @if($posts->onFirstPage())
                <span class="disabled" aria-hidden="true">« Précédent</span>
            @else
                <a href="{{ $posts->previousPageUrl() }}" rel="prev">« Précédent</a>
            @endif
            @if($posts->hasMorePages())
                <a href="{{ $posts->nextPageUrl() }}" rel="next">Suivant »</a>
            @else
                <span class="disabled" aria-hidden="true">Suivant »</span>
            @endif
        </nav>
    </main>

    <footer>© {{ date('Y') }} laveille.ai — MEMORA solutions</footer>
</body>
</html>
