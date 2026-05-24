<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Devenir auteur Premium avec fonctionnalités exclusives, support prioritaire et outils avancés.">
    <title>{{ $isPremium ? '💎 Gérer ton abonnement Premium' : '💎 Devenir auteur Premium' }} · La veille de Stef</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700&display=swap" rel="stylesheet">
    <style>
        :root { --teal:#0B7285; --accent:#C2410C; --cream:#F8FAFB; --gray:#475569; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: linear-gradient(135deg, var(--teal) 0%, var(--accent) 100%); color: var(--cream); line-height: 1.6; min-height: 100vh; display: flex; justify-content: center; padding: 32px 16px; }
        .lv-upgrade { max-width: 720px; width: 100%; }
        .lv-card { background: var(--cream); border-radius: 16px; padding: 40px 32px; color: var(--gray); box-shadow: 0 10px 32px rgba(0,0,0,0.18); }
        .lv-card h1 { font-size: 2.25rem; font-weight: 700; margin: 16px 0 12px; color: var(--teal); }
        .lv-card > p { margin: 0 0 24px; font-size: 1.0625rem; }
        .lv-price { background: linear-gradient(90deg, var(--teal), var(--accent)); color: white; padding: 24px; border-radius: 12px; text-align: center; font-size: 1.5rem; font-weight: 700; margin: 24px 0; }
        .lv-price small { display: block; font-size: 0.875rem; font-weight: 400; opacity: 0.95; margin-top: 4px; }
        .lv-features ul { list-style: none; margin: 24px 0; padding: 0; }
        .lv-features li { position: relative; padding: 6px 0 6px 32px; font-weight: 500; line-height: 1.5; }
        .lv-features li::before { content: "✓"; position: absolute; left: 0; top: 6px; width: 22px; height: 22px; background: #16A34A; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; }
        .lv-cta { margin: 24px 0; }
        .lv-cta button { background: var(--teal); color: white; border: none; border-radius: 12px; padding: 16px 32px; font-size: 1.0625rem; font-weight: 700; cursor: pointer; width: 100%; min-height: 44px; transition: background .2s ease; }
        .lv-cta button:hover { background: #075560; }
        .lv-cta button:focus-visible { outline: 3px solid var(--accent); outline-offset: 2px; }
        .lv-flash { padding: 14px 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; }
        .lv-flash--success { background: #DCFCE7; color: #065F46; }
        .lv-flash--warning { background: #FEF3C7; color: #92400E; }
        .lv-flash--info { background: #DBEAFE; color: #1E40AF; }
        .lv-back { display: inline-block; margin-bottom: 24px; font-weight: 600; color: var(--teal); text-decoration: underline; }
        .lv-back:focus-visible { outline: 3px solid var(--accent); outline-offset: 2px; }
        .lv-small { font-size: 0.8125rem; color: var(--gray); text-align: center; margin-top: 16px; }
        @media (max-width: 600px) { .lv-card { padding: 24px 20px; } .lv-card h1 { font-size: 1.75rem; } }
    </style>
</head>
<body>
    <main class="lv-upgrade">
        <div class="lv-card">
            <a href="{{ url('/auteur/dashboard') }}" class="lv-back">← Retour au tableau de bord</a>

            @if (session('warning'))
                <div class="lv-flash lv-flash--warning" role="alert">{{ session('warning') }}</div>
            @endif

            @if (request('status') === 'success')
                <div class="lv-flash lv-flash--success" role="status">🎉 Ton abonnement Premium est actif ! Bienvenue parmi nous.</div>
            @elseif (request('status') === 'cancelled')
                <div class="lv-flash lv-flash--info" role="status">Paiement annulé. Tu peux réessayer à tout moment.</div>
            @endif

            @if ($isPremium)
                <h1>💎 Tu es Premium</h1>
                <p>Tu as accès à toutes les fonctionnalités avancées. Gère ton abonnement ci-dessous.</p>

                <div class="lv-cta">
                    <form action="{{ route('authors.upgrade.billing-portal') }}" method="POST">
                        @csrf
                        <button type="submit">⚙️ Gérer mon abonnement (Stripe Customer Portal)</button>
                    </form>
                </div>
            @else
                <h1>💎 Devenir Premium</h1>
                <p>Débloque les outils pros pour auteurs exigeants.</p>

                <div class="lv-price">
                    7 $ CAD / mois
                    <small>Sans engagement · Annulation 1-clic</small>
                </div>

                <div class="lv-features">
                    <ul>
                        <li>Domaine personnalisé <code>tonblog.com</code> (CNAME)</li>
                        <li>Articles illimités en haute définition</li>
                        <li>Templates premium pour newsletters</li>
                        <li>Statistiques détaillées par article</li>
                        <li>Tips Stripe one-tap activés</li>
                        <li>Support prioritaire</li>
                        <li>Annulation 1-clic + remboursement 14 jours</li>
                    </ul>
                </div>

                <div class="lv-cta">
                    <form action="{{ route('authors.upgrade.checkout') }}" method="POST">
                        @csrf
                        <button type="submit">🚀 Démarrer mon essai Premium</button>
                    </form>
                </div>

                <p class="lv-small">Paiement sécurisé Stripe · Conforme Loi 25 QC + RGPD EU</p>
            @endif
        </div>
    </main>
</body>
</html>
