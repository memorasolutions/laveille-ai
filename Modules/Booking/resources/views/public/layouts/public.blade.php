<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Réservation')</title>
    <link href="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-light">
    <main class="container py-5">
        @yield('content')
    </main>

    <footer class="container text-center text-muted py-4">
        <small>
            &copy; {{ date('Y') }}
            <a href="{{ config('app.url') }}" class="text-decoration-none">
                {{ config('app.name', 'Réservation') }}
            </a>
        </small>
    </footer>

    <script src="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
