<!DOCTYPE html>
{{--
    Page d'erreur du lancement LTI 1.3 (consumer) - affichée quand une
    validation de sécurité échoue (état/nonce, signature, émetteur,
    deployment_id...). AUCUN détail technique n'apparaît ici : le détail va
    exclusivement au journal serveur (voir LtiLaunchController::callback).
    Auteur : MEMORA solutions <info@memora.ca> (https://memora.solutions)
--}}
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Impossible de charger cet outil externe</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #F1F5F9;
            color: #1F2937;
        }
        .lti-error-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            text-align: center;
        }
        .lti-error-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .lti-error-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
            color: #9A3412;
        }
        .lti-error-sub {
            font-size: 0.95rem;
            color: #4B5563;
            max-width: 32rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="lti-error-wrap" role="alert">
        <div class="lti-error-icon" aria-hidden="true">⚠️</div>
        <p class="lti-error-title">Impossible de charger cet outil externe</p>
        <p class="lti-error-sub">
            Une erreur est survenue lors de la connexion à cet outil pédagogique.
            Vous pouvez fermer cet onglet et réessayer depuis votre leçon.
        </p>
    </div>
</body>
</html>
