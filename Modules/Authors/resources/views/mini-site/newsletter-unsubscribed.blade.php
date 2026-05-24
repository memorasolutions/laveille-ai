<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Désabonnement · {{ $author->display_name ?? $author->slug }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; background: #F8FAFB; color: #1F2937; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .lv-nl-card { max-width: 600px; width: 100%; padding: 48px 32px; text-align: center; background: #FFFFFF; border-radius: 16px; box-shadow: 0 4px 16px rgba(11, 114, 133, 0.08); }
        .lv-nl-card h1 { color: #0B7285; font-size: 32px; margin: 24px 0 16px; }
        .lv-nl-card p { font-size: 16px; line-height: 1.6; margin: 0 0 24px; color: #1F2937; }
        .lv-nl-btn { display: inline-block; min-height: 44px; padding: 12px 24px; background: #0B7285; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; }
        .lv-nl-btn:hover { background: #075560; }
        .lv-nl-btn:focus-visible { outline: 3px solid #C2410C; outline-offset: 2px; }
        .lv-nl-resub { margin-top: 32px; padding-top: 24px; border-top: 1px solid #E2E8F0; }
        .lv-nl-resub p { margin: 0 0 8px; color: #475569; font-size: 14px; }
        .lv-nl-link { color: #C2410C; text-decoration: underline; font-weight: 600; }
        .lv-nl-link:focus-visible { outline: 3px solid #C2410C; outline-offset: 2px; }
    </style>
</head>
<body>
    <main role="main" class="lv-nl-card" aria-live="polite">
        <svg aria-hidden="true" focusable="false" width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
            <rect x="10" y="20" width="60" height="40" rx="4" stroke="#64748B" stroke-width="4" fill="none"/>
            <path d="M10 24L40 46L70 24" stroke="#64748B" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h1>Désabonnement effectué</h1>
        <p>
            Tu ne recevras plus les courriels de
            <strong>{{ $author->display_name ?? $author->slug }}</strong>.
            Merci d'avoir pris le temps de nous lire.
        </p>
        <a href="{{ route('authors.mini-site.show', $author->slug) }}" class="lv-nl-btn">
            Retour au profil
        </a>
        <div class="lv-nl-resub">
            <p>Tu changes d'idée&nbsp;?</p>
            <a href="{{ url('/@'.$author->slug.'#newsletter') }}" class="lv-nl-link">Te réabonner</a>
        </div>
    </main>
</body>
</html>
