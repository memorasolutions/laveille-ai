@props([
    'url' => null,
    'domain' => null,
    'size' => 64,
    'alt' => '',
    'class' => '',
    'width' => null,
    'height' => null,
])

@php
    if ($url && ! $domain) {
        $parsed = parse_url($url);
        $domain = $parsed['host'] ?? null;
    }

    if ($domain) {
        $domain = preg_replace('/^www\./i', '', $domain);
        $domain = parse_url('http://' . $domain, PHP_URL_HOST);
    }

    $resolvedUrl = null;

    if ($domain && class_exists(\Modules\Core\Services\FaviconResolverService::class)) {
        try {
            $resolvedUrl = \Modules\Core\Services\FaviconResolverService::resolve($domain, (int) $size);
        } catch (\Throwable $e) {
            $resolvedUrl = null;
        }
    }
@endphp

@if($resolvedUrl)
<img
    src="{{ $resolvedUrl }}"
    loading="lazy"
    decoding="async"
    alt="{{ $alt }}"
    width="{{ $width ?? $size }}"
    height="{{ $height ?? $size }}"
    title="{{ $domain }}"
    @if($class) class="{{ $class }}" @endif
/>
@endif
