<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hors connexion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1A1D23 0%, #2D3039 50%, #3F4451 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: #f8fafc;
        }
        .container { max-width: 400px; text-align: center; }
        .icon {
            width: 80px; height: 80px; margin: 0 auto 1.5rem;
            border-radius: 50%;
            background: rgba(11, 114, 133, 0.2);
            display: flex; align-items: center; justify-content: center;
        }
        .icon svg { width: 40px; height: 40px; color: #0B7285; }
        h1 { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; font-size: 1.5rem; margin-bottom: 0.75rem; }
        p { font-size: 1rem; color: #94a3b8; line-height: 1.6; margin-bottom: 1.5rem; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 2rem; background: #0B7285; color: #fff;
            border: none; border-radius: 0.5rem; font-size: 1rem;
            font-weight: 600; cursor: pointer; transition: background 0.2s;
        }
        .btn:hover { background: #064E5C; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0119 12.55M5 12.55a10.94 10.94 0 015.17-2.39M10.71 5.05A16 16 0 0122.56 9M1.42 9a15.91 15.91 0 014.7-2.88M8.53 16.11a6 6 0 016.95 0M12 20h.01"/>
            </svg>
        </div>
        <h1>Hors connexion</h1>
        <p>Impossible de se connecter. Vérifiez votre connexion et réessayez.</p>
        <button class="btn" onclick="window.location.reload()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M23 4v6h-6M1 20v-6h6"/>
                <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
            </svg>
            Réessayer
        </button>
    </div>
    <script>setInterval(() => { if (navigator.onLine) location.reload(); }, 5000);</script>
</body>
</html>
