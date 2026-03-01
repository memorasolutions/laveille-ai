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
                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                <small class="text-muted d-block">{{ $post->published_at->diffForHumans() }}</small>
            </li>
        @endforeach
    </ul>
</div>
