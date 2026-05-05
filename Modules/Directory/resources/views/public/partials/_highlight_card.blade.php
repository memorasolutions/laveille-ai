{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Carte compacte pour la section Highlights --}}

@php
    $host = $tool->url ? parse_url($tool->url, PHP_URL_HOST) : '';
    $screenshotSrc = $tool->screenshot
        ? (str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot).'?v='.($tool->updated_at?->timestamp ?? '0'))
        : '';
    $pricingLabels = \Modules\Directory\Support\PricingCategories::labels();
    $gradientColors = ['#0B7285','#1a365d','#8E44AD','#E67E22','#2ECC71','#E74C3C','#3498DB','#F39C12'];
    $gIdx = abs(crc32($tool->name)) % count($gradientColors);
@endphp

<a href="{{ route('directory.show', $tool->slug) }}" class="rt-hl-card" title="{{ $tool->name }}" style="position:relative;">
    {{-- 2026-05-05 #135 : badge YouTube rouge tutos sur card highlight --}}
    @if(($tool->tutorials_count ?? 0) > 0)
        <span style="position:absolute;top:6px;right:6px;z-index:3;display:inline-flex;align-items:center;gap:3px;background:#0B7285;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:3px;line-height:1.2;box-shadow:0 1px 3px rgba(0,0,0,.2);" title="{{ $tool->tutorials_count }} {{ $tool->tutorials_count > 1 ? __('tutoriels') : __('tutoriel') }}">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <span>{{ $tool->tutorials_count }}</span>
        </span>
    @endif
    <div class="rt-hl-img" style="{{ $screenshotSrc ? '' : 'background: linear-gradient(135deg, ' . $gradientColors[$gIdx] . ', ' . $gradientColors[($gIdx + 1) % count($gradientColors)] . ');' }}">
        @if($screenshotSrc)
            <img src="{{ $screenshotSrc }}" alt="{{ $tool->name }}" loading="lazy"
                 onerror="this.onerror=null; this.src='/images/directory-fallback.svg';">
        @else
            <span class="rt-hl-img-text">{{ Str::limit($tool->name, 15) }}</span>
        @endif
    </div>
    <div class="rt-hl-body">
        <div class="rt-hl-name">{{ $tool->name }}</div>
        <span class="rt-badge badge-{{ $tool->pricing }}">{{ $pricingLabels[$tool->pricing] ?? ucfirst($tool->pricing) }}</span>
    </div>
</a>
