<div class="lv-author-search">
    <label for="lv-search-input" class="sr-only">Rechercher dans les articles</label>
    <input
        id="lv-search-input"
        type="search"
        wire:model.live.debounce.300ms="query"
        placeholder="🔍 Rechercher dans les articles…"
        autocomplete="off"
        spellcheck="false"
        aria-label="Rechercher dans les articles"
        class="lv-search-input"
    >

    @if(mb_strlen($query) >= 2)
        <div role="region" aria-live="polite" aria-label="Résultats de recherche" class="lv-search-results">
            @if($results->isEmpty())
                <p class="lv-search-empty">Aucun résultat pour <strong>{{ $query }}</strong></p>
            @else
                <ul class="lv-search-list">
                    @foreach($results as $post)
                        <li>
                            <a href="{{ url('/@'.$post->authorProfile->slug.'/'.$post->slug) }}" class="lv-search-link">
                                <strong>{{ $post->title }}</strong>
                                @if($post->excerpt)
                                    <span class="lv-search-excerpt">{{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <style>
    .lv-author-search { max-width: 600px; margin: 16px 0; }
    .lv-search-input { width: 100%; padding: 12px 16px; min-height: 44px; border: 2px solid #064E5A; border-radius: 8px; font-size: 15px; font-family: inherit; background: #FFFFFF; color: #1F2937; }
    .lv-search-input:focus { outline: 3px solid #9A2A06; outline-offset: 2px; }
    .lv-search-results { margin-top: 12px; padding: 16px; background: #F8FAFB; border-radius: 8px; }
    .lv-search-empty { color: #3A4050; margin: 0; }
    .lv-search-list { list-style: none; padding: 0; margin: 0; }
    .lv-search-list li { border-bottom: 1px solid #E2E8F0; }
    .lv-search-list li:last-child { border-bottom: none; }
    .lv-search-link { display: block; padding: 12px 8px; min-height: 44px; color: #064E5A; text-decoration: none; }
    .lv-search-link:hover, .lv-search-link:focus-visible { background: #FFFFFF; outline: 2px solid #064E5A; outline-offset: -2px; border-radius: 4px; }
    .lv-search-link strong { display: block; margin-bottom: 4px; }
    .lv-search-excerpt { font-size: 13px; color: #3A4050; }
    </style>
</div>
