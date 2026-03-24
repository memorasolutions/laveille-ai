@foreach($articles as $article)
    <div class="post format-standard-image">
        <div class="entry-media">
            @if($article->featured_image)
                <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}" loading="lazy">
            @else
                <img src="{{ fronttheme_asset('images/blog/img-' . (($loop->index % 3) + 10) . '.jpg') }}" alt="{{ $article->title }}" loading="lazy">
            @endif
        </div>
        <div class="entry-meta">
            <ul>
                <li><i class="fi flaticon-user"></i> {{ __('Par') }} <a href="#">{{ $article->getAuthorName() }}</a> </li>
                <li><i class="fi flaticon-comment-white-oval-bubble"></i> {{ $article->comments_count ?? 0 }} {{ __('commentaires') }}</li>
                <li><i class="fi flaticon-calendar"></i> {{ $article->published_at?->format('d M Y') }}</li>
            </ul>
        </div>
        <div class="entry-details">
            <h3><a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a></h3>
            <p>{{ Str::limit($article->excerpt ?? strip_tags($article->content), 200) }}</p>
            <a href="{{ route('blog.show', $article->slug) }}" class="read-more">{{ __('LIRE LA SUITE...') }}</a>
        </div>
    </div>
@endforeach
