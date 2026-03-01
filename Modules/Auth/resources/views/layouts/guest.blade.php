<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            background-color: #fff;
            color: #111827;
            line-height: 1.5;
        }

        .auth-container {
            display: flex;
            min-height: 100vh;
        }

        /* Left column: form */
        .auth-form-col {
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            background: #fff;
        }

        .auth-form-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .auth-logo { height: 40px; margin-bottom: 1.5rem; display: block; }

        /* Right column: hero */
        .auth-hero-col {
            width: 50%;
            min-height: 100vh;
            background-image: url("{{ asset('images/auth-hero-bg.png') }}");
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 3rem;
        }

        .auth-hero-col::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(12, 74, 110, 0.9) 0%, transparent 60%);
        }

        .auth-hero-content {
            position: relative;
            max-width: 520px;
            margin-bottom: 2rem;
        }

        .auth-hero-heading {
            font-size: 2.25rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 2rem;
        }

        .auth-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 2rem;
            list-style: none;
            padding: 0;
        }

        .auth-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .auth-features .fi {
            color: #38bdf8;
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }

        .auth-features span {
            font-size: 1rem;
            font-weight: 500;
            color: #fff;
        }

        /* Form elements - scoped to auth only */
        .auth-form-wrapper .auth-input {
            display: block;
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            font-size: 0.95rem;
            color: #111827;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .auth-form-wrapper .auth-input:focus {
            outline: none;
            border-color: #0284c7;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .auth-form-wrapper .auth-input::placeholder {
            color: #9ca3af;
        }

        .auth-form-wrapper .auth-input-icon {
            position: absolute;
            inset-inline-start: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            width: 20px;
            height: 20px;
        }

        .auth-form-wrapper .auth-input-group {
            position: relative;
        }

        .auth-form-wrapper .auth-label {
            display: block;
            font-weight: 500;
            font-size: 0.95rem;
            color: #374151;
            margin-bottom: 0.625rem;
        }

        .auth-form-wrapper .auth-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            color: #fff;
            background: linear-gradient(135deg, #d946ef 0%, #0284c7 100%);
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .auth-form-wrapper .auth-btn:hover {
            opacity: 0.85;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }

        .auth-form-wrapper .auth-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .auth-form-wrapper .auth-social-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.875rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            color: #374151;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 0.375rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .auth-form-wrapper .auth-social-btn:hover {
            background: #f9fafb;
            color: #111827;
        }

        .auth-form-wrapper .auth-social-btn .social-icon {
            position: absolute;
            inset-inline-start: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .auth-form-wrapper .auth-link {
            color: #0284c7;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .auth-form-wrapper .auth-link:hover {
            color: #0369a1;
            text-decoration: underline;
        }

        .auth-form-wrapper .auth-text-muted {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .auth-form-wrapper .auth-divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .auth-form-wrapper .auth-divider::before,
        .auth-form-wrapper .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .auth-form-wrapper .auth-divider span {
            color: #9ca3af;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .auth-form-wrapper .auth-error {
            color: #dc2626;
            font-size: 0.85rem;
            margin-top: 0.375rem;
        }

        .auth-form-wrapper .auth-alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .auth-form-wrapper .auth-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .auth-form-wrapper .auth-checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .auth-form-wrapper .auth-checkbox {
            width: 1rem;
            height: 1rem;
            accent-color: #0284c7;
        }

        .auth-form-wrapper .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .auth-form-wrapper .auth-subtitle {
            color: #6b7280;
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .auth-form-wrapper .toggle-password-btn {
            position: absolute;
            inset-inline-end: 0;
            top: 0;
            bottom: 0;
            padding: 0 0.875rem;
            background: transparent;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .auth-form-wrapper .toggle-password-btn:hover {
            color: #6b7280;
        }

        @media (max-width: 991.98px) {
            .auth-hero-col { display: none; }
            .auth-form-col {
                width: 100%;
                min-height: 100vh;
            }
        }

        @media (min-width: 992px) and (max-width: 1199.98px) {
            .auth-form-col { padding: 2rem; }
            .auth-hero-col { padding: 2rem; }
        }
    </style>
</head>
<body>
    <main class="auth-container">
        <div class="auth-form-col">
            <div class="auth-form-wrapper">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('themes/gosass/img/logo.svg') }}" alt="{{ config('app.name') }}" class="auth-logo">
                </a>
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </div>

        <div class="auth-hero-col">
            <div class="auth-hero-content">
                <h2 class="auth-hero-heading">{{ __('Bienvenue sur') }} {{ config('app.name') }}</h2>
                <ul class="auth-features">
                    <li>
                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ __('Authentification sécurisée') }}</span>
                    </li>
                    <li>
                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ __('Gestion simplifiée') }}</span>
                    </li>
                    <li>
                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ __('Mises à jour en temps réel') }}</span>
                    </li>
                    <li>
                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ __('Support 24/7') }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </main>
    @livewireScripts
    @stack('scripts')
</body>
</html>
