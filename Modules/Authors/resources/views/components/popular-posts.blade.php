@props(['author', 'limit' => 5])

@php
    $popularPosts = \Modules\Authors\Models\AuthorPost::published()
        ->public()
        ->where('author_profile_id', $author->id)
        ->orderByDesc('views_count')
        ->orderByDesc('published_at')
        ->limit($limit)
        ->get();
@endphp

@if($popularPosts->isNotEmpty())
    <aside class="lv-popular-posts" aria-labelledby="lv-popular-h2">
        <h2 id="lv-popular-h2" style="font-size:18px; font-weight:700; color:var(--c-primary,#064E5A); margin:0 0 16px;">
            🔥 Articles populaires
        </h2>
        <ol class="lv-popular-list" style="list-style:none; padding:0; margin:0; counter-reset:popular-counter;">
            @foreach($popularPosts as $post)
                <li style="counter-increment:popular-counter; position:relative; padding-left:36px; margin-bottom:8px;">
                    <span aria-hidden="true" style="position:absolute; left:0; top:14px; font-size:18px; font-weight:700; color:var(--c-accent,#9A2A06); font-family:'Plus Jakarta Sans',sans-serif;">{{ $loop->iteration }}.</span>
                    <a href="/@{{ $author->slug }}/{{ $post->slug }}" class="lv-popular-card" style="display:block; padding:10px 12px; min-height:44px; text-decoration:none; color:inherit; border-radius:8px; transition:background 200ms;">
                        <h3 style="font-size:14px; font-weight:600; color:var(--c-primary,#064E5A); margin:0 0 4px; line-height:1.3;">{{ $post->title }}</h3>
                        <div style="display:flex; gap:8px; font-size:12px; color:#5A6270; align-items:center;">
                            <span aria-label="{{ $post->views_count }} vues">👁 {{ number_format($post->views_count) }}</span>
                            <span aria-hidden="true">•</span>
                            <span>{{ $post->reading_time ?? 1 }} min</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ol>
    </aside>

    <style>
        .lv-popular-posts { background:var(--c-cream,#F8FAFB); padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(6,78,90,0.08); border:1px solid rgba(6,78,90,0.08); }
        .lv-popular-card:hover { background:rgba(6,78,90,0.06); }
        .lv-popular-card:focus-visible { outline:3px solid var(--c-accent,#9A2A06); outline-offset:2px; background:rgba(6,78,90,0.06); }
        @media (prefers-reduced-motion: reduce) { .lv-popular-card { transition:none; } }
    </style>
@endif
