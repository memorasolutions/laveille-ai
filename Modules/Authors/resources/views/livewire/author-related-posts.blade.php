<div>
@if($relatedPosts->isNotEmpty())
    <section class="lv-related-posts" aria-labelledby="lv-related-h2">
        <h2 id="lv-related-h2" style="font-size:24px; font-weight:700; color:var(--c-primary,#064E5A); margin:0 0 1.25rem;">
            📚 Articles similaires
        </h2>
        <div class="lv-related-grid">
            @foreach($relatedPosts as $post)
                @php
                    $profile = $post->authorProfile;
                    $firstTag = (is_array($post->tags) && !empty($post->tags)) ? $post->tags[0] : null;
                    $href = '/@'.$profile->slug.'/'.$post->slug;
                @endphp
                <a href="{{ $href }}"
                   class="lv-related-card"
                   aria-labelledby="lv-related-post-{{ $post->id }}">
                    @if($post->cover_image)
                        <img src="{{ $post->cover_image }}"
                             loading="lazy"
                             decoding="async"
                             alt=""
                             class="lv-related-cover">
                    @else
                        <div class="lv-related-placeholder" aria-hidden="true"></div>
                    @endif
                    <div class="lv-related-body">
                        <h3 id="lv-related-post-{{ $post->id }}" class="lv-related-title">{{ $post->title }}</h3>
                        @if($post->excerpt)
                            <p class="lv-related-excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt, 80) }}</p>
                        @endif
                        <footer class="lv-related-footer">
                            @if($firstTag)
                                <span class="lv-related-chip">{{ $firstTag }}</span>
                            @endif
                            <span>{{ $post->reading_time ?? 1 }} min</span>
                            @if($post->published_at)
                                <span aria-hidden="true">•</span>
                                <time datetime="{{ $post->published_at->toIso8601String() }}">
                                    {{ $post->published_at->diffForHumans() }}
                                </time>
                            @endif
                        </footer>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <style>
        .lv-related-posts { margin-top: 3rem; padding-top: 2rem; border-top: 1px solid rgba(6,78,90,0.12); }
        .lv-related-grid { display: grid; gap: 1.25rem; grid-template-columns: 1fr; }
        @media (min-width: 640px) { .lv-related-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1024px) { .lv-related-grid { grid-template-columns: repeat(3, 1fr); } }
        .lv-related-card { display: block; min-height: 44px; border-radius: 12px; overflow: hidden; background: var(--c-cream,#F8FAFB); box-shadow: 0 2px 8px rgba(6,78,90,0.08); text-decoration: none; color: inherit; transition: transform 200ms ease, box-shadow 200ms ease; border: 1px solid rgba(6,78,90,0.08); }
        @media (prefers-reduced-motion: reduce) { .lv-related-card { transition: none; } }
        .lv-related-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(6,78,90,0.15); }
        .lv-related-card:focus-visible { outline: 3px solid var(--c-accent,#9A2A06); outline-offset: 2px; }
        .lv-related-cover, .lv-related-placeholder { width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block; }
        .lv-related-placeholder { background: linear-gradient(135deg, hsl(190, 30%, 88%), hsl(190, 20%, 94%)); }
        .lv-related-body { padding: 1rem; }
        .lv-related-title { font-size: 17px; font-weight: 700; margin: 0 0 0.5rem; color: var(--c-primary,#064E5A); line-height: 1.3; }
        .lv-related-excerpt { font-size: 14px; line-height: 1.5; margin: 0 0 0.75rem; color: #3F4554; }
        .lv-related-footer { display: flex; align-items: center; gap: 0.5rem; font-size: 12px; color: #5A6270; flex-wrap: wrap; }
        .lv-related-chip { background: rgba(6,78,90,0.1); color: var(--c-primary,#064E5A); padding: 0.2rem 0.6rem; border-radius: 999px; font-weight: 600; font-size: 11px; }
    </style>
@endif
</div>
