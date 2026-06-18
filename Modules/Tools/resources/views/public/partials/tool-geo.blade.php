{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- GEO/AEO : JSON-LD WebApplication (array PHP, jamais spatie/schema-org) + answer-box réutilisable pour /outils/{slug}. Défensif. --}}
@php
if (isset($tool) && filled($tool->name ?? null) && filled($tool->slug ?? null)) {
    $__sa = [
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        '@id' => url('/outils/' . $tool->slug) . '#webapp',
        'name' => $tool->name,
        'applicationCategory' => 'WebApplication',
        'operatingSystem' => 'Any',
        'url' => url('/outils/' . $tool->slug),
        'inLanguage' => 'fr-CA',
    ];

    if (filled($tool->description ?? null)) {
        $__sa['description'] = \Illuminate\Support\Str::limit(trim(strip_tags($tool->description)), 300, '');
    }

    if (filled($tool->featured_image ?? null)) {
        $__sa['image'] = \Illuminate\Support\Str::startsWith($tool->featured_image, 'http')
            ? $tool->featured_image
            : url($tool->featured_image);
    }

    $__sa['offers'] = [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'CAD',
    ];
    $__sa['isAccessibleForFree'] = true;

    $__sa['publisher'] = [
        '@type' => 'Organization',
        'name' => config('app.name'),
        'url' => url('/'),
    ];

    $__json = json_encode($__sa, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
@endphp

@if(isset($__json))
@push('head')
<script type="application/ld+json">{!! $__json !!}</script>
@endpush
@endif

@if(isset($tool) && (filled($tool->answer_summary ?? null) || !empty($tool->answer_points ?? [])))
    <x-core::answer-box :summary="$tool->answer_summary" :points="$tool->answer_points ?? []" :collapsible="true" />
@endif
