{{-- Tabler Admin Theme - Head Component --}}
@php
    $primaryColor = \Modules\Settings\Models\Setting::get('branding.primary_color', '#206bc4');
    $fontFamily = \Modules\Settings\Models\Setting::get('branding.font_family', 'Inter');

    // Convert hex to RGB
    $hex = ltrim($primaryColor, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Convert RGB to HSL
    $rn = $r / 255; $gn = $g / 255; $bn = $b / 255;
    $max = max($rn, $gn, $bn); $min = min($rn, $gn, $bn);
    $h = $s = $l = ($max + $min) / 2;
    if ($max == $min) {
        $h = $s = 0;
    } else {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        switch ($max) {
            case $rn: $h = (($gn - $bn) / $d + ($gn < $bn ? 6 : 0)) / 6; break;
            case $gn: $h = (($bn - $rn) / $d + 2) / 6; break;
            case $bn: $h = (($rn - $gn) / $d + 4) / 6; break;
        }
    }
    $hue = round($h * 360);
    $sat = round($s * 100);
    $lig = round($l * 100);
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="{{ $primaryColor }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ \Modules\Settings\Models\Setting::get('branding.site_name', config('app.name')) }}">

<title>{{ $title ?? 'Admin' }} - {{ \Modules\Settings\Models\Setting::get('branding.site_name', config('app.name')) }}</title>

{{-- PWA --}}
<link rel="manifest" href="/manifest.json">

{{-- Tabler CSS --}}
<link rel="stylesheet" href="{{ asset('assets/backoffice/tabler/css/tabler.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/backoffice/tabler/css/tabler-vendors.min.css') }}">

{{-- Tabler Icons --}}
<link rel="stylesheet" href="{{ asset('assets/backoffice/tabler/icons/tabler-icons.min.css') }}">

{{-- ApexCharts CSS --}}
<link rel="stylesheet" href="{{ asset('assets/backoffice/tabler/libs/apexcharts/dist/apexcharts.css') }}">

{{-- Dynamic branding CSS variables --}}
<style>
    :root {
        --tblr-primary: {{ $primaryColor }};
        --tblr-primary-rgb: {{ $r }}, {{ $g }}, {{ $b }};
        --primary-50: hsl({{ $hue }}, {{ $sat }}%, 95%);
        --primary-100: hsl({{ $hue }}, {{ $sat }}%, 90%);
        --primary-200: hsl({{ $hue }}, {{ $sat }}%, 80%);
        --primary-300: hsl({{ $hue }}, {{ $sat }}%, 70%);
        --primary-400: hsl({{ $hue }}, {{ $sat }}%, 60%);
        --primary-500: hsl({{ $hue }}, {{ $sat }}%, {{ $lig }}%);
        --primary-600: hsl({{ $hue }}, {{ $sat }}%, {{ max($lig - 10, 10) }}%);
        --primary-700: hsl({{ $hue }}, {{ $sat }}%, {{ max($lig - 20, 5) }}%);
        --primary-800: hsl({{ $hue }}, {{ $sat }}%, {{ max($lig - 30, 5) }}%);
        --primary-900: hsl({{ $hue }}, {{ $sat }}%, {{ max($lig - 40, 5) }}%);
        --brand: {{ $primaryColor }};
    }
    @if($fontFamily !== 'Inter')
    body, .page { font-family: '{{ $fontFamily }}', sans-serif !important; }
    @endif
</style>

{{-- Vite assets --}}
@vite(['resources/js/app.js'])

{{-- Livewire styles --}}
@livewireStyles

{{-- Page-level CSS stack --}}
@stack('css')
