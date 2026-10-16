{{-- MEMORA solutions - entête de marque sobre, entièrement pilotée par config('file-attente.brand.*') --}}
@php
    $brandName = config('file-attente.brand.name') ?: config('app.name');
    $brandUrl = config('file-attente.brand.url') ?: config('app.url');
    $brandLogo = ($onDark ?? false)
        ? (config('file-attente.brand.logo_light') ?: config('file-attente.brand.logo'))
        : config('file-attente.brand.logo');
@endphp
<header class="brand" role="banner">
  <a href="{{ $brandUrl }}" rel="noopener">
    @if ($brandLogo)<img src="{{ $brandLogo }}" alt="" style="height:40px;width:auto;vertical-align:middle">@endif
    <span>{{ $brandName }}</span>
  </a>
</header>
