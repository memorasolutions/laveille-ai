<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/backoffice/wowdash/images/favicon.png') }}" sizes="16x16">
    <!-- remix icon font css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/remixicon.css') }}">
    <!-- BootStrap css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/bootstrap.min.css') }}">
    <!-- main css -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/style.css') }}">
    @livewireStyles
    @vite(['resources/js/app.js'])
</head>

<body>

    <main class="auth bg-base d-flex flex-wrap">
        <div class="auth-left d-lg-block d-none" style="overflow:hidden;position:relative;">
            <img src="{{ asset('assets/auth/login-bg.jpg') }}" alt="" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;">
        </div>
        <div class="auth-right py-32 px-24 d-flex flex-column justify-content-center">
            <div class="max-w-464-px mx-auto w-100">
                <div>
                    <a href="{{ url('/') }}" class="mb-40 max-w-290-px d-inline-block">
                        <img src="{{ asset('themes/gosass/img/logo.svg') }}" alt="{{ config('app.name') }}" style="height:40px;">
                    </a>
                </div>

                {{ $slot ?? '' }}
                @yield('content')

            </div>
        </div>
    </main>

    <!-- jQuery library js -->
    <script src="{{ asset('assets/backoffice/wowdash/js/lib/jquery-3.7.1.min.js') }}"></script>
    <!-- Bootstrap js -->
    <script src="{{ asset('assets/backoffice/wowdash/js/lib/bootstrap.bundle.min.js') }}"></script>
    <!-- Iconify Font js -->
    <script src="{{ asset('assets/backoffice/wowdash/js/lib/iconify-icon.min.js') }}"></script>
    <!-- main js -->
    <script src="{{ asset('assets/backoffice/wowdash/js/app.js') }}"></script>
    @livewireScripts
    @stack('scripts')
</body>

</html>
