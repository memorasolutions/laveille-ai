<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Maintenance en cours') }} · {{ config('app.name', 'La veille de Stef') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --c-primary: #0B7285;
            --c-primary-hover: #064E5C;
            --c-accent: #C2410C;
            --c-accent-hover: #9A3412;
            --c-dark: #1A1D23;
            --c-text-secondary: #4a4f5c;
            --c-text-muted: #52586a;
            --c-surface: #F8FAFB;
            --c-border: #E5E7EB;
        }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--c-surface);
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
            max-width: 620px;
            width: 100%;
            text-align: center;
            background: #fff;
            padding: 2.5rem 2rem;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(11, 114, 133, 0.08);
            border-top: 5px solid var(--c-accent);
        }
        .err-mascot {
            margin: 0 auto 1.25rem;
            width: 140px;
            height: 140px;
            display: block;
        }
        .err-mascot svg { width: 100%; height: 100%; display: block; }
        .err-code {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--c-text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0.5rem;
        }
        .err-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--c-dark);
            margin-bottom: 0.85rem;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        .err-message {
            color: var(--c-text-secondary);
            font-size: 1.05rem;
            margin-bottom: 1.5rem;
        }
        .err-pulse {
            display: inline-flex;
            gap: 6px;
            margin-top: 0.5rem;
        }
        .err-pulse span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--c-accent);
            animation: errPulse 1.4s infinite ease-in-out;
        }
        .err-pulse span:nth-child(2) { animation-delay: 0.2s; }
        .err-pulse span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes errPulse {
            0%, 100% { opacity: 0.3; transform: scale(0.85); }
            50% { opacity: 1; transform: scale(1.15); }
        }
        @media (prefers-reduced-motion: reduce) {
            .err-pulse span { animation: none; opacity: 0.7; }
        }
        .err-footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--c-border);
            font-size: 0.8rem;
            color: var(--c-text-muted);
        }
        @media (max-width: 480px) {
            .err-card { padding: 2rem 1.25rem; }
            .err-title { font-size: 1.5rem; }
            .err-message { font-size: 1rem; }
            .err-mascot { width: 110px; height: 110px; }
        }
    </style>
</head>
<body>
    <main class="err-card" role="main">
        <div class="err-mascot" aria-hidden="true">
            @include('errors.octopus._render', ['emotion' => 'thinking'])
        </div>
        <p class="err-code">{{ __('Erreur') }} 503</p>
        <h1 class="err-title">{{ __('Maintenance en cours') }}</h1>
        <p class="err-message">{{ $exception->getMessage() ?: __('Octopus améliore la veille en coulisses. Le site revient très vite, promis.') }}</p>
        <div class="err-pulse" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>
        <div class="err-footer">
            {{ config('app.name', 'La veille de Stef') }} · {{ __('Veille IA Québec') }}
        </div>
    </main>
</body>
</html>
