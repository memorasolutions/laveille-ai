<!DOCTYPE html>
{{--
    Page de retour du lancement LTI 1.3 (consumer) - confirme au navigateur de
    l'apprenant que le jeton d'identité de l'outil externe a été validé.
    Auteur : MEMORA solutions <info@memora.ca> (https://memora.solutions)

    Cette page n'étend PAS le thème du site : elle est ouverte dans un nouvel
    onglet (target="_blank" depuis le bouton « Ouvrir l'outil externe »), en
    dehors du contexte de la leçon. C'est un document autonome minimal.

    MVP : ce consumer LTI 1.3 valide le lancement (OIDC + jeton d'identité)
    mais n'affiche PAS encore la ressource de l'outil dans une iframe intégrée
    (Deep Linking, qui fournirait l'URL précise de la ressource, est HORS
    PÉRIMÈTRE de ce MVP - voir Modules/Academy/tests/Feature/AcademyLtiTest.php).

    Variables fournies par le contrôleur (jamais par le client) :
    - $tool  : Modules\Academy\Models\LtiToolRegistration validé
    - $title : nom de l'outil (a11y / titre d'onglet)
--}}
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #F1F5F9;
            color: #1F2937;
        }
        .lti-frame-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            text-align: center;
        }
        .lti-frame-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .lti-frame-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
            color: #064E5A;
        }
        .lti-frame-sub {
            font-size: 0.95rem;
            color: #4B5563;
            max-width: 32rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="lti-frame-wrap" role="status">
        <div class="lti-frame-icon" aria-hidden="true">✅</div>
        <p class="lti-frame-title">Connexion établie avec « {{ $tool->name }} »</p>
        <p class="lti-frame-sub">
            Cet outil pédagogique externe a validé votre accès. Vous pouvez
            fermer cet onglet et revenir à votre leçon.
        </p>
    </div>
</body>
</html>
