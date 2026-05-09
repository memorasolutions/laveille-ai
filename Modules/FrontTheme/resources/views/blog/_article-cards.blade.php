@foreach($articles as $article)
    <div class="post format-standard-image">
        <div class="entry-media">
            @if($article->featured_image)
                <img src="{{ asset($article->featured_image) }}?v={{ $article->updated_at?->timestamp ?? time() }}" alt="{{ $article->title }}" loading="lazy">
            @else
                <img src="{{ fronttheme_asset('images/blog/img-' . (($loop->index % 3) + 10) . '.jpg') }}" alt="{{ $article->title }}" loading="lazy">
            @endif
        </div>
        @php
            $words = str_word_count(strip_tags($article->content ?? $article->excerpt ?? ''));
            $minRead = max(1, (int) ceil($words / 200));
        @endphp
        {{-- S90 #82 byline retirée des cartes (Option D pp_search 2026 mono-auteur). EEAT préservé : section header + page article + Schema.org Person --}}
        <div class="card-meta-compact">
            <time class="card-meta-compact-date" datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->translatedFormat('d M Y') }}</time>
            <span class="card-meta-compact-sep">·</span>
            <span class="card-meta-compact-readtime">{{ $minRead }} {{ __('min lecture') }}</span>
        </div>
        <div class="entry-details">
            <h3><a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a></h3>
            <p>{{ Str::limit($article->excerpt ?? strip_tags($article->content), 200) }}</p>
            <a href="{{ route('blog.show', $article->slug) }}" class="read-more">{{ __('LIRE LA SUITE...') }}</a>
        </div>
    </div>
@endforeach
