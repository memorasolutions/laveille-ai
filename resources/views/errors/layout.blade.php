<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale() ?? 'fr-CA') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · {{ config('app.name', 'La veille de Stef') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --c-primary: #064E5A;
            --c-accent: #9A2A06;
            --c-dark: #1a1d23;
            --c-text-muted: #52586a;
            --c-surface: #ffffff;
            --c-bg: #F0F4F8;
            --c-border: #E5E7EB;
            --c-chip-bg: #F3F4F6;
        }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--c-bg);
            color: var(--c-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
            line-height: 1.55;
        }
        .err-card {
            max-width: 600px;
            width: 100%;
            text-align: center;
            background: var(--c-surface);
            padding: 2.5rem 2rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border-top: 5px solid var(--c-primary);
        }
        .err-emoji {
            font-size: 3.25rem;
            line-height: 1;
            margin-bottom: 1rem;
            display: block;
        }
        .err-code {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--c-text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0.5rem;
        }
        .err-title {
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--c-dark);
            margin-bottom: 0.85rem;
            letter-spacing: -0.5px;
        }
        .err-message {
            color: var(--c-text-muted);
            font-size: 1.05rem;
            margin-bottom: 2rem;
        }
        .err-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.6rem;
        }
        .err-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.75rem 1.4rem;
            min-height: 44px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }
        .err-btn-primary {
            background: var(--c-primary);
            color: #fff;
        }
        .err-btn-primary:hover { background: #053f49; }
        .err-btn-primary:focus-visible {
            outline: 3px solid var(--c-accent);
            outline-offset: 3px;
        }
        .err-btn-secondary {
            background: var(--c-chip-bg);
            color: var(--c-dark);
        }
        .err-btn-secondary:hover { background: #E5E7EB; }
        .err-btn-secondary:focus-visible {
            outline: 3px solid var(--c-primary);
            outline-offset: 3px;
        }
        .err-footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--c-border);
            font-size: 0.8rem;
            color: var(--c-text-muted);
        }
        .err-footer a {
            color: var(--c-primary);
            text-decoration: none;
            font-weight: 600;
        }
        .err-footer a:hover { text-decoration: underline; }
        @media (max-width: 480px) {
            .err-card { padding: 2rem 1.5rem; }
            .err-title { font-size: 1.5rem; }
            .err-message { font-size: 1rem; }
            .err-emoji { font-size: 2.75rem; }
        }
    </style>
</head>
<body>
    <main class="err-card" role="main">
        <span class="err-emoji" aria-hidden="true">@yield('emoji', '⚠️')</span>
        <p class="err-code">{{ __('Erreur') }} @yield('code')</p>
        <h1 class="err-title">@yield('title')</h1>
        <p class="err-message">@yield('message')</p>
        <div class="err-actions">
            <a href="{{ url('/') }}" class="err-btn err-btn-primary">{{ __('Retour à l\'accueil') }}</a>
            @hasSection('secondary')
                @yield('secondary')
            @else
                <a href="{{ url('/annuaire') }}" class="err-btn err-btn-secondary">{{ __('Explorer l\'annuaire') }}</a>
            @endif
        </div>
        <div class="err-footer">
            <a href="{{ url('/') }}">{{ config('app.name', 'La veille de Stef') }}</a>
            · {{ __('Veille IA Québec') }}
        </div>
    </main>
</body>
</html>
