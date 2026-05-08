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
            --c-primary: #064E5A;
            --c-accent: #9A2A06;
            --c-dark: #1a1d23;
            --c-text-muted: #52586a;
            --c-surface: #ffffff;
            --c-bg: #F0F4F8;
            --c-border: #E5E7EB;
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
            border-top: 5px solid var(--c-accent);
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
            .err-card { padding: 2rem 1.5rem; }
            .err-title { font-size: 1.5rem; }
            .err-message { font-size: 1rem; }
            .err-emoji { font-size: 2.75rem; }
        }
    </style>
</head>
<body>
    <main class="err-card" role="main">
        <span class="err-emoji" aria-hidden="true">🛠️</span>
        <p class="err-code">{{ __('Erreur') }} 503</p>
        <h1 class="err-title">{{ __('Maintenance en cours') }}</h1>
        <p class="err-message">{{ $exception->getMessage() ?: __('On améliore la veille en coulisses. Le site revient très vite, promis.') }}</p>
        <div class="err-pulse" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>
        <div class="err-footer">
            {{ config('app.name', 'La veille de Stef') }} · {{ __('Veille IA Québec') }}
        </div>
    </main>
</body>
</html>
