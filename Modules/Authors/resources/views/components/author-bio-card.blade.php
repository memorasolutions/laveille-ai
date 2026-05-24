@props(['author', 'showFollow' => true])

@php
    $authorName = $author->display_name ?? $author->user?->name ?? $author->slug;
    $words = preg_split('/\s+/', trim($authorName));
    $initials = strtoupper(mb_substr($words[0] ?? 'A', 0, 1) . (isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));
    $hue = (crc32($author->slug) % 360);
    $bio = $author->bio ?? '';
    $qualifications = is_array($author->qualifications ?? null) ? array_slice($author->qualifications, 0, 3) : [];
    $socialLinks = is_array($author->social_links ?? null) ? $author->social_links : [];
@endphp

<section class="lv-author-bio-card" aria-labelledby="lv-author-bio-h2">
    <h2 id="lv-author-bio-h2" class="lv-sr-only">À propos de l'auteur</h2>
    <div class="lv-author-bio-grid">
        <div class="lv-author-bio-avatar">
            @if($author->profile_image)
                <img src="{{ asset('storage/'.$author->profile_image) }}" alt="Portrait de {{ $authorName }}" />
            @else
                <div role="img"
                     aria-label="Avatar de {{ $authorName }}"
                     class="lv-author-bio-initials"
                     style="background:linear-gradient(135deg, hsl({{ $hue }}, 65%, 35%) 0%, hsl({{ ($hue + 40) % 360 }}, 65%, 55%) 100%);">{{ $initials }}</div>
            @endif
        </div>
        <div class="lv-author-bio-content">
            <h3 class="lv-author-bio-name">
                <a href="/@{{ $author->slug }}">{{ $authorName }}</a>
            </h3>
            @if($bio)
                <p class="lv-author-bio-text">{{ \Illuminate\Support\Str::limit($bio, 280) }}</p>
            @endif
            @if(!empty($qualifications))
                <ul class="lv-author-bio-quals" aria-label="Qualifications">
                    @foreach($qualifications as $q)
                        <li>{{ $q }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="lv-author-bio-actions">
                <a href="/@{{ $author->slug }}" class="lv-author-bio-btn-primary">Voir tous ses articles →</a>
                @if($showFollow)
                    <x-authors::follow-button :author="$author" />
                @endif
            </div>
            @if(!empty($socialLinks))
                <ul class="lv-author-bio-social" aria-label="Liens sociaux">
                    @foreach($socialLinks as $key => $url)
                        @if($url)
                            <li><a href="{{ $url }}" rel="noopener me" target="_blank" aria-label="{{ ucfirst($key) }} de {{ $authorName }}">{{ ucfirst($key) }}</a></li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>

<style>
    .lv-author-bio-card { margin-top:48px; padding:32px; background:var(--c-cream,#F8FAFB); border-radius:16px; box-shadow:0 4px 16px rgba(6,78,90,0.08); border:1px solid rgba(6,78,90,0.08); }
    .lv-author-bio-grid { display:flex; gap:24px; align-items:flex-start; }
    @media (max-width: 600px) { .lv-author-bio-grid { flex-direction:column; align-items:center; text-align:center; } }
    .lv-author-bio-avatar img, .lv-author-bio-initials { width:96px; height:96px; border-radius:50%; flex-shrink:0; object-fit:cover; display:block; }
    .lv-author-bio-initials { display:flex; align-items:center; justify-content:center; color:white; font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:38px; }
    .lv-author-bio-content { flex:1; }
    .lv-author-bio-name { font-size:24px; font-weight:700; margin:0 0 12px; }
    .lv-author-bio-name a { color:var(--c-primary,#064E5A); text-decoration:none; }
    .lv-author-bio-name a:hover { text-decoration:underline; }
    .lv-author-bio-name a:focus-visible { outline:3px solid var(--c-accent,#9A2A06); outline-offset:2px; }
    .lv-author-bio-text { font-size:15px; line-height:1.6; color:#3F4554; margin:0 0 16px; }
    .lv-author-bio-quals { list-style:none; padding:0; margin:0 0 16px; display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-start; }
    @media (max-width: 600px) { .lv-author-bio-quals { justify-content:center; } }
    .lv-author-bio-quals li { background:rgba(6,78,90,0.08); color:var(--c-primary,#064E5A); padding:4px 12px; border-radius:999px; font-size:12px; font-weight:600; }
    .lv-author-bio-actions { display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:12px; }
    @media (max-width: 600px) { .lv-author-bio-actions { justify-content:center; } }
    .lv-author-bio-btn-primary { min-height:44px; padding:10px 20px; background:var(--c-primary,#064E5A); color:white; border-radius:999px; text-decoration:none; font-weight:600; font-size:14px; display:inline-flex; align-items:center; }
    .lv-author-bio-btn-primary:hover { background:var(--c-accent,#9A2A06); }
    .lv-author-bio-btn-primary:focus-visible { outline:3px solid var(--c-accent,#9A2A06); outline-offset:2px; }
    .lv-author-bio-social { list-style:none; padding:0; margin:0; display:flex; gap:16px; flex-wrap:wrap; }
    @media (max-width: 600px) { .lv-author-bio-social { justify-content:center; } }
    .lv-author-bio-social a { color:var(--c-primary,#064E5A); font-weight:600; text-decoration:underline; font-size:13px; min-height:44px; display:inline-flex; align-items:center; }
    .lv-author-bio-social a:focus-visible { outline:3px solid var(--c-accent,#9A2A06); outline-offset:2px; }
    .lv-sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
</style>
