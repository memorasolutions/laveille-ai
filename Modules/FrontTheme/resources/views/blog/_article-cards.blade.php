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
            $isUpdated = $article->updated_at && $article->published_at && $article->updated_at->gt($article->published_at) && $article->updated_at->diffInDays($article->published_at) >= 1;
        @endphp
        <div class="entry-meta-eeat">
            <img src="{{ asset('images/logo-avatar.png') }}" alt="" class="entry-author-avatar" loading="lazy" decoding="async" width="40" height="40">
            <div class="entry-author-info">
                <div class="entry-author-name">{{ $article->getAuthorName() }}</div>
                <div class="entry-author-role">{{ __('Veille IA Québec') }}</div>
                <div class="entry-author-meta">
                    <span>{{ __('Publié') }} <time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->translatedFormat('d M Y') }}</time></span>
                    @if($isUpdated)<span> · {{ __('Mis à jour') }} <time datetime="{{ $article->updated_at?->toIso8601String() }}">{{ $article->updated_at?->translatedFormat('d M Y') }}</time></span>@endif
                    <span> · {{ $minRead }} {{ __('min lecture') }}</span>
                </div>
            </div>
        </div>
        <div class="entry-details">
            <h3><a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a></h3>
            <p>{{ Str::limit($article->excerpt ?? strip_tags($article->content), 200) }}</p>
            <a href="{{ route('blog.show', $article->slug) }}" class="read-more">{{ __('LIRE LA SUITE...') }}</a>
        </div>
    </div>
@endforeach
