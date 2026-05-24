<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmé · {{ $author->display_name ?? $author->slug }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; background: #F8FAFB; color: #1F2937; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .lv-nl-card { max-width: 600px; width: 100%; padding: 48px 32px; text-align: center; background: #FFFFFF; border-radius: 16px; box-shadow: 0 4px 16px rgba(11, 114, 133, 0.08); }
        .lv-nl-card h1 { color: #0B7285; font-size: 32px; margin: 24px 0 16px; }
        .lv-nl-card p { font-size: 16px; line-height: 1.6; margin: 0 0 24px; color: #1F2937; }
        .lv-nl-btn { display: inline-block; min-height: 44px; padding: 12px 24px; background: #0B7285; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; transition: background .15s ease; }
        .lv-nl-btn:hover { background: #075560; }
        .lv-nl-btn:focus-visible { outline: 3px solid #C2410C; outline-offset: 2px; }
        .lv-nl-footer { font-size: 14px; color: #64748B; margin-top: 32px; }
    </style>
</head>
<body>
    <main role="main" class="lv-nl-card" aria-live="polite">
        <svg aria-hidden="true" focusable="false" width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
            <circle cx="40" cy="40" r="40" fill="#0B7285"/>
            <path d="M26 40L36 50L54 32" stroke="#FFFFFF" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
        <h1>Confirmé !</h1>
        <p>
            Tu es maintenant abonné·e à la newsletter de
            <strong>{{ $author->display_name ?? $author->slug }}</strong>.
            À très vite par courriel !
        </p>
        <a href="{{ route('authors.mini-site.show', $author->slug) }}" class="lv-nl-btn">
            Retour au profil
        </a>
        <p class="lv-nl-footer">Désabonnement 1-clic dans chaque envoi.</p>
    </main>
</body>
</html>
