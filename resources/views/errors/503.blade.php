<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Maintenance en cours') }} - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            width: 100%;
            text-align: center;
            animation: fadeIn 1s ease-out;
        }

        .illustration {
            width: 200px;
            height: 200px;
            margin: 0 auto 40px;
            animation: rotate 20s linear infinite;
        }

        .illustration svg {
            width: 100%;
            height: 100%;
        }

        h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(90deg, #ffffff, #e94560);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .pulse-dots {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 10px;
        }

        .dot {
            width: 8px;
            height: 8px;
            background-color: #e94560;
            border-radius: 50%;
            animation: pulse 1.5s infinite ease-in-out;
        }

        .dot:nth-child(1) { animation-delay: 0s; }
        .dot:nth-child(2) { animation-delay: 0.5s; }
        .dot:nth-child(3) { animation-delay: 1s; }

        footer {
            margin-top: 60px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            width: 100%;
            max-width: 600px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(0.8); opacity: 0.5; }
            50% { transform: scale(1.2); opacity: 1; }
        }

        @media (max-width: 768px) {
            h1 { font-size: 2.2rem; }
            .subtitle { font-size: 1.1rem; padding: 0 10px; }
            .illustration { width: 160px; height: 160px; }
        }

        @media (max-width: 480px) {
            h1 { font-size: 1.8rem; }
            .subtitle { font-size: 1rem; }
            .illustration { width: 140px; height: 140px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="illustration">
            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <circle cx="100" cy="100" r="45" fill="none" stroke="#e94560" stroke-width="12"/>
                <circle cx="100" cy="100" r="25" fill="#1a1a2e"/>
                <circle cx="100" cy="100" r="12" fill="#e94560"/>
                <rect x="92" y="40" width="16" height="20" fill="#e94560" rx="2"/>
                <rect x="92" y="140" width="16" height="20" fill="#e94560" rx="2"/>
                <rect x="40" y="92" width="20" height="16" fill="#e94560" rx="2"/>
                <rect x="140" y="92" width="20" height="16" fill="#e94560" rx="2"/>
                <rect x="56" y="56" width="14" height="14" fill="#e94560" rx="2" transform="rotate(45 63 63)"/>
                <rect x="130" y="56" width="14" height="14" fill="#e94560" rx="2" transform="rotate(45 137 63)"/>
                <rect x="56" y="130" width="14" height="14" fill="#e94560" rx="2" transform="rotate(45 63 137)"/>
                <rect x="130" y="130" width="14" height="14" fill="#e94560" rx="2" transform="rotate(45 137 137)"/>
            </svg>
        </div>

        <h1>{{ __('Maintenance en cours') }}</h1>
        <p class="subtitle">
            {{ __('Nous améliorons votre expérience. Le site sera de retour très bientôt.') }}
        </p>
        <div class="pulse-dots">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>

        <footer>
            &copy; {{ date('Y') }} {{ config('app.name') }} - {{ __('Tous droits réservés') }}
        </footer>
    </div>
</body>
</html>
