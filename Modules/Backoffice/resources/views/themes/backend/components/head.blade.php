<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ($branding['site_name'] ?? config('app.name')) }} - Administration</title>
    @php
        $faviconUrl = ! empty($branding['favicon'] ?? '')
            ? asset('storage/' . $branding['favicon'])
            : asset('assets/backoffice/backend/images/favicon.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}" sizes="16x16">

    <!-- Vendor CSS -->
    <link href="{{ asset('assets/backoffice/backend/vendor/owl-carousel/owl.carousel.css') }}" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/backend/icons/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/backend/icons/line-awesome/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/backend/icons/flaticon/flaticon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/backend/icons/flaticon_1/flaticon_1.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/backend/icons/themify-icons/css/themify-icons.css') }}">

    <!-- Plugins -->
    <link href="{{ asset('assets/backoffice/backend/vendor/niceselect/css/nice-select.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/backoffice/backend/vendor/flatpickr-master/css/flatpicker.css') }}" rel="stylesheet">

    <!-- Bootstrap CSS (required for btn, card, modal, table classes) -->
    <link href="{{ asset('assets/backoffice/backend/vendor/bootstrap/scss/bootstrap.css') }}" rel="stylesheet">

    <!-- Main CSS -->
    <link href="{{ asset('assets/backoffice/backend/css/style.css') }}" rel="stylesheet">

    {{-- Dynamic branding CSS variables --}}
    @php
        $primaryColor = $branding['primary_color'] ?? '#7B2CF5';
        $fontFamily = $branding['font_family'] ?? 'Poppins';
    @endphp
    <style>
        :root {
            --primary: {{ $primaryColor }};
            @if($fontFamily !== 'Poppins')
            --font-family: '{{ $fontFamily }}', sans-serif;
            @endif
        }
    </style>

    @vite(['resources/js/app.js'])
    @livewireStyles
    @stack('css')

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ $primaryColor }}">
</head>
