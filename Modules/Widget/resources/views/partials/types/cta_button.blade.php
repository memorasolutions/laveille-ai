<div class="widget widget-cta mb-3">
    <h5 class="widget-title">{{ $widget->title }}</h5>
    <a href="{{ $widget->settings['button_url'] ?? '#' }}" class="btn btn-{{ $widget->settings['button_style'] ?? 'primary' }}">
        {{ $widget->settings['button_text'] ?? 'Découvrir' }}
    </a>
</div>
