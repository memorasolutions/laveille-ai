<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('booking.brand.booking_page_title', 'Prendre rendez-vous') }}</title>
    <link href="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    @livewireStyles
    <style>
        body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .booking-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .wizard-card { background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,.08); }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="text-center mb-4">
            <h1 class="h3 fw-bold">{{ config('booking.brand.booking_page_title', 'Prendre rendez-vous') }}</h1>
        </div>
        <div class="wizard-card">
            @livewire('booking-wizard')
        </div>
    </div>
    <script src="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @livewireScripts
</body>
</html>
