<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="widget widget-social mb-3">
    <h5 class="widget-title">{{ $widget->title }}</h5>
    <ul class="list-inline">
        @foreach(($widget->settings['social_links'] ?? []) as $link)
            <li class="list-inline-item">
                <a href="{{ $link['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer">{{ $link['name'] ?? '' }}</a>
            </li>
        @endforeach
    </ul>
</div>
