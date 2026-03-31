<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('Lien introuvable') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; color: #1a1a2e; padding: 20px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 48px 40px; max-width: 440px; width: 100%; text-align: center; }
        .icon { font-size: 3rem; margin-bottom: 16px; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 12px; }
        p { color: #6B7280; font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .btn { display: inline-block; background: #0B7285; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background 0.2s; }
        .btn:hover { background: #095e6e; }
        .footer { margin-top: 20px; font-size: 12px; color: #9CA3AF; }
        .footer a { color: #0B7285; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{{ $icon ?? '🔗' }}</div>
        <h1>{{ $title ?? __('Lien introuvable') }}</h1>
        <p>{{ $message ?? __('Ce lien court n\'existe pas ou n\'est plus disponible.') }}</p>
        <a href="{{ config('app.url') }}" class="btn">{{ __('Aller à l\'accueil') }}</a>
        <div class="footer">
            <a href="{{ config('app.url') }}">{{ config('app.name') }}</a>
        </div>
    </div>
</body>
</html>
