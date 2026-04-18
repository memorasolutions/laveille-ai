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
        $domain = parse_url('http://'.$domain, PHP_URL_HOST);
    }

    $googleSize = $size > 64 ? 128 : $size;

    $providers = $domain ? [
        'https://icons.duckduckgo.com/ip3/'.$domain.'.ico',
        'https://icon.horse/icon/'.$domain,
        'https://www.google.com/s2/favicons?domain='.$domain.'&sz='.$googleSize,
    ] : [];

    $id = 'sf-'.preg_replace('/[^a-z0-9]/', '-', strtolower((string) $domain)).'-'.substr(md5(($domain ?? '').$size), 0, 8);
    $providersJson = json_encode($providers, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

@if($domain && ! empty($providers))
<img
    id="{{ $id }}"
    x-data="{
        providers: {{ $providersJson }},
        index: 0,
        failedAll: false,
        init() {
            this.$el.addEventListener('error', () => {
                if (this.failedAll) return;
                this.index++;
                if (this.index >= this.providers.length) {
                    this.failedAll = true;
                    this.$el.style.display = 'none';
                } else {
                    this.$el.src = this.providers[this.index];
                }
            });
        }
    }"
    src="{{ $providers[0] }}"
    loading="lazy"
    decoding="async"
    alt="{{ $alt }}"
    width="{{ $width ?? $size }}"
    height="{{ $height ?? $size }}"
    title="{{ $domain }}"
    @if($class) class="{{ $class }}" @endif
/>
@endif
