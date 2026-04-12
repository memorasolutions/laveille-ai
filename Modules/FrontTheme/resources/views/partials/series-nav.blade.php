{{-- series-nav.blade.php — Navigation automatique pour séries d'articles (détection par slug "-partie-N") --}}
@php
    $seriesArticles = collect();
    $seriesParts = 0;
    $currentPartNumber = null;

    if ($article && $article->slug) {
        $locale = 'fr_CA';
        $slug = $article->getTranslation('slug', $locale, false) ?? $article->slug;

        if (preg_match('/^(.+)-partie-(\d+)(.*)$/', $slug, $matches)) {
            $baseSlug = $matches[1];
            $currentPartNumber = (int) $matches[2];

            $articleClass = \Modules\Blog\Models\Article::class;

            if (class_exists($articleClass)) {
                $seriesArticles = $articleClass::query()
                    ->where('status', 'published')
                    ->where("slug->$locale", 'LIKE', $baseSlug . '-partie-%')
                    ->get()
                    ->map(function ($a) use ($locale) {
                        $s = $a->getTranslation('slug', $locale, false) ?? $a->slug;
                        $partNum = null;
                        if (preg_match('/-partie-(\d+)/', $s, $m)) {
                            $partNum = (int) $m[1];
                        }
                        return [
                            'id' => $a->id,
                            'slug' => $s,
                            'title' => $a->getTranslation('title', $locale, false) ?? $a->title,
                            'part_number' => $partNum,
                        ];
                    })
                    ->filter(fn ($item) => $item['part_number'] !== null)
                    ->sortBy('part_number')
                    ->values();

                $seriesParts = $seriesArticles->count();
            }
        }
    }
@endphp

@if($seriesParts > 1)
<nav
    aria-label="Navigation de la série en {{ $seriesParts }} parties"
    style="background-color: #F0FAFB; border: 2px solid #D5EDF0; border-radius: 12px; padding: 24px; margin: 32px 0;"
>
    <h2 style="margin: 0 0 20px 0; font-size: 1.15rem; font-weight: 700; color: #1A1D23; line-height: 1.4;">
        Cette série en {{ $seriesParts }}&nbsp;parties
    </h2>

    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: stretch;">
        @foreach($seriesArticles as $part)
            @php
                $isCurrent = $part['id'] === $article->id;
                $truncatedTitle = Str::limit($part['title'], 60, '…');
            @endphp

            @if($isCurrent)
                <div
                    aria-current="page"
                    style="flex: 1 1 220px; max-width: 100%; background-color: #FFFFFF; border: 3px solid #0B7285; border-radius: 8px; padding: 16px; display: flex; flex-direction: column; gap: 8px;"
                >
                    <span style="display: inline-block; background-color: #0B7285; color: #FFFFFF; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 2px 8px; border-radius: 4px; align-self: flex-start;" aria-label="Article actuel">Vous êtes ici</span>
                    <span style="font-size: 0.85rem; font-weight: 700; color: #0B7285; line-height: 1.2;">Partie {{ $part['part_number'] }}&nbsp;:</span>
                    <span style="font-size: 0.9rem; font-weight: 600; color: #1A1D23; line-height: 1.4;">{{ $truncatedTitle }}</span>
                </div>
            @else
                <a
                    href="{{ url('/blog/' . $part['slug']) }}"
                    aria-label="Partie {{ $part['part_number'] }}&nbsp;: {{ $truncatedTitle }}"
                    style="flex: 1 1 220px; max-width: 100%; background-color: #FFFFFF; border: 2px solid #D5EDF0; border-radius: 8px; padding: 16px; text-decoration: none; display: flex; flex-direction: column; gap: 8px; transition: border-color 0.2s, box-shadow 0.2s;"
                    onmouseover="this.style.borderColor='#0B7285'; this.style.boxShadow='0 2px 8px rgba(11,114,133,0.15)';"
                    onmouseout="this.style.borderColor='#D5EDF0'; this.style.boxShadow='none';"
                    onfocus="this.style.borderColor='#0B7285'; this.style.boxShadow='0 0 0 3px rgba(11,114,133,0.4)';"
                    onblur="this.style.borderColor='#D5EDF0'; this.style.boxShadow='none';"
                >
                    <span style="font-size: 0.85rem; font-weight: 700; color: #0B7285; line-height: 1.2;">Partie {{ $part['part_number'] }}&nbsp;:</span>
                    <span style="font-size: 0.9rem; font-weight: 500; color: #1A1D23; line-height: 1.4;">{{ $truncatedTitle }}</span>
                    <span style="font-size: 0.78rem; color: #6E7687; margin-top: auto; font-weight: 500;">Lire cette partie →</span>
                </a>
            @endif
        @endforeach
    </div>
</nav>
@endif
