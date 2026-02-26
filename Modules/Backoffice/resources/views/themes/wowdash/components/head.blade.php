<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($branding['site_name'] ?? config('app.name')) }} - Administration</title>
    @php
        $faviconUrl = ! empty($branding['favicon'] ?? '')
            ? asset('storage/' . $branding['favicon'])
            : asset('assets/backoffice/wowdash/images/favicon.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}" sizes="16x16">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/apexcharts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/dataTables.min.css') }}">
    <!-- Text Editor css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/editor-katex.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/editor.atom-one-dark.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/editor.quill.snow.css') }}">
    <!-- Date picker css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/flatpickr.min.css') }}">
    <!-- Calendar css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/full-calendar.css') }}">
    <!-- Vector Map css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/jquery-jvectormap-2.0.5.css') }}">
    <!-- Popup css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/magnific-popup.css') }}">
    <!-- Slick Slider css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/slick.css') }}">
    <!-- prism css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/prism.css') }}">
    <!-- file upload css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/file-upload.css') }}">
    <!-- audio player css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/audioplayer.css') }}">
    <!-- main css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/style.css') }}">

    {{-- Police custom --}}
    @if(! empty($branding['font_url'] ?? ''))
        <style>@import url('{{ $branding['font_url'] }}');</style>
    @endif

    {{-- Variables CSS dynamiques depuis les settings branding --}}
    @php
        $primaryColor = $branding['primary_color'] ?? '#487FFF';
        $fontFamily = $branding['font_family'] ?? 'Inter';

        // Génération de la palette depuis la couleur primaire (ajustement HSL)
        $hex = ltrim($primaryColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r /= 255; $g /= 255; $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            $h = match ($max) {
                $r => (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6,
                $g => (($b - $r) / $d + 2) / 6,
                $b => (($r - $g) / $d + 4) / 6,
            };
        }

        // Fonction pour HSL → Hex
        $hslToHex = function ($h, $s, $l) {
            if ($s == 0) {
                $r = $g = $b = $l;
            } else {
                $hue2rgb = function ($p, $q, $t) {
                    if ($t < 0) $t += 1;
                    if ($t > 1) $t -= 1;
                    if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
                    if ($t < 1/2) return $q;
                    if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
                    return $p;
                };
                $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
                $p = 2 * $l - $q;
                $r = $hue2rgb($p, $q, $h + 1/3);
                $g = $hue2rgb($p, $q, $h);
                $b = $hue2rgb($p, $q, $h - 1/3);
            }
            return sprintf('#%02X%02X%02X', round($r * 255), round($g * 255), round($b * 255));
        };

        // Palette : luminosité ajustée pour chaque variante
        $palette = [
            '50'  => $hslToHex($h, $s, min(0.95, $l + 0.37)),
            '100' => $hslToHex($h, $s, min(0.90, $l + 0.30)),
            '200' => $hslToHex($h, $s, min(0.85, $l + 0.22)),
            '300' => $hslToHex($h, $s, min(0.78, $l + 0.14)),
            '400' => $hslToHex($h, $s, min(0.70, $l + 0.07)),
            '500' => $hslToHex($h, $s, min(0.62, $l + 0.02)),
            '600' => $primaryColor,
            '700' => $hslToHex($h, $s, max(0.15, $l - 0.07)),
            '800' => $hslToHex($h, $s, max(0.10, $l - 0.14)),
            '900' => $hslToHex($h, $s, max(0.05, $l - 0.22)),
        ];
    @endphp
    <style>
        :root {
            @foreach($palette as $shade => $color)
            --primary-{{ $shade }}: {{ $color }};
            @endforeach
            --brand: var(--primary-600);
            --button-secondary: var(--primary-50);
            @if($fontFamily !== 'Inter')
            --default-font: '{{ $fontFamily }}', sans-serif;
            @endif
        }
        @if($fontFamily !== 'Inter')
        body, .sidebar-menu, .navbar-header, .card, .form-control, .btn {
            font-family: '{{ $fontFamily }}', sans-serif !important;
        }
        @endif
        .form-select {
            padding-right: 2.5rem;
            background-position: right 0.75rem center;
        }
        .form-check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .form-switch .form-check-input::before {
            background: #fff !important;
            border-radius: 50% !important;
            visibility: visible !important;
            opacity: 1 !important;
            transform: translateY(-50%) !important;
            width: 16px;
            height: 16px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table-responsive.overflow-visible {
            overflow: visible !important;
        }
    </style>

    @vite(['resources/js/app.js'])
    @livewireStyles
    @stack('css')
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
</head>
