{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Media Picture Component - WebP with fallback
    Usage: <x-media::picture :media="$media" conversion="medium" class="img-fluid" />
--}}
@props([
    'media',
    'conversion' => 'medium',
    'alt' => null,
    'loading' => 'lazy',
])
@php
    $altText = $alt ?? $media->getCustomProperty('alt_text', $media->file_name);
    $webpConversion = $conversion . '-webp';
    $hasWebp = $media->hasGeneratedConversion($webpConversion);
    $hasOriginal = $media->hasGeneratedConversion($conversion);
    $fallbackUrl = $hasOriginal ? $media->getUrl($conversion) : $media->getUrl();
@endphp
@if($hasWebp)
<picture>
    <source srcset="{{ $media->getUrl($webpConversion) }}" type="image/webp">
    <img src="{{ $fallbackUrl }}" alt="{{ $altText }}" loading="{{ $loading }}" {{ $attributes }}>
</picture>
@else
<img src="{{ $fallbackUrl }}" alt="{{ $altText }}" loading="{{ $loading }}" {{ $attributes }}>
@endif
