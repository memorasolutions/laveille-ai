<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php
    $postCount = $widget->settings['post_count'] ?? 5;
    $posts = \Modules\Blog\Models\Article::published()->latest('published_at')->take($postCount)->get();
@endphp

<div class="widget widget-recent-posts mb-3">
    <h5 class="widget-title">{{ $widget->title }}</h5>
    <ul class="list-unstyled">
        @foreach($posts as $post)
            <li class="mb-2">
                {{-- 2026-08-31 (#2092) : accès brut au slug traduisible protégé par getPublicUrl(). --}}
                <a href="{{ $post->getPublicUrl() }}">{{ $post->title }}</a>
                <small class="text-muted d-block">{{ $post->published_at->diffForHumans() }}</small>
            </li>
        @endforeach
    </ul>
</div>
