{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Carte compacte pour la section Highlights --}}

@php
    $host = $tool->url ? parse_url($tool->url, PHP_URL_HOST) : '';
    $screenshotSrc = $tool->screenshot
        ? (str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot))
        : '';
    $pricingLabels = ['free' => __('Gratuit'), 'freemium' => 'Freemium', 'paid' => __('Payant'), 'open_source' => 'Open source', 'enterprise' => 'Enterprise'];
    $gradientColors = ['#0B7285','#1a365d','#8E44AD','#E67E22','#2ECC71','#E74C3C','#3498DB','#F39C12'];
    $gIdx = abs(crc32($tool->name)) % count($gradientColors);
@endphp

<a href="{{ route('directory.show', $tool->slug) }}" class="rt-hl-card" title="{{ $tool->name }}">
    <div class="rt-hl-img" style="{{ $screenshotSrc ? '' : 'background: linear-gradient(135deg, ' . $gradientColors[$gIdx] . ', ' . $gradientColors[($gIdx + 1) % count($gradientColors)] . ');' }}">
        @if($screenshotSrc)
            <img src="{{ $screenshotSrc }}" alt="{{ $tool->name }}" loading="lazy">
        @else
            <span class="rt-hl-img-text">{{ Str::limit($tool->name, 15) }}</span>
        @endif
    </div>
    <div class="rt-hl-body">
        <div class="rt-hl-name">{{ $tool->name }}</div>
        <span class="rt-badge badge-{{ $tool->pricing }}">{{ $pricingLabels[$tool->pricing] ?? ucfirst($tool->pricing) }}</span>
    </div>
</a>
