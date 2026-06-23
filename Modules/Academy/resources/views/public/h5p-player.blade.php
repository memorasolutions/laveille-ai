{{--
    Page LECTEUR H5P - rendue DANS L'IFRAME SANDBOX du lecteur de leçon.
    Auteur : MEMORA solutions <info@memora.ca> (https://memora.solutions)

    Cette page n'étend PAS le thème : c'est un document autonome minimal qui
    charge h5p-standalone depuis le CDN (jsdelivr) et instancie le contenu sur
    le dossier extrait ($contentUrl, servi en same-origin). La CSP dédiée est
    posée par H5pPlayerController (jsdelivr autorisé pour le player uniquement).

    Variables (toutes fournies par le contrôleur, jamais par le client) :
    - $contentUrl : URL publique du dossier H5P extrait (h5pJsonPath)
    - $cdnBase    : base jsdelivr du player (sans slash final)
    - $title      : titre de l'élément (a11y)
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    {{--
        Feuille de style du cadre H5P (CDN), protégée par SRI (sha384). Si un CDN
        compromis sert un fichier altéré, l'empreinte ne correspond plus et le
        navigateur refuse de l'appliquer. « crossorigin=anonymous » est requis pour
        que la vérification d'intégrité s'applique à une ressource cross-origin.
        $sriCss vide => pas d'attribut (repli sûr, rétrocompatible).
        Recalcul des empreintes documenté dans Modules/Academy/config/config.php.
    --}}
    <link rel="stylesheet" href="{{ $cdnBase }}/dist/styles/h5p.css"
        @if(!empty($sriCss)) integrity="{{ $sriCss }}" crossorigin="anonymous" @endif>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
        #h5p-container { width: 100%; }
        .h5p-loading,
        .h5p-error {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #374151;
            padding: 16px;
            font-size: 0.95rem;
        }
        .h5p-error { color: #9A3412; }
    </style>
</head>
<body>
    <div id="h5p-container" role="application" aria-label="{{ $title }}">
        <p class="h5p-loading">Chargement du contenu interactif…</p>
    </div>

    {{-- Bundle principal h5p-standalone (CDN), protégé par SRI (sha384, voir ci-dessus). --}}
    <script src="{{ $cdnBase }}/dist/main.bundle.js"
        @if(!empty($sriMainJs)) integrity="{{ $sriMainJs }}" crossorigin="anonymous" @endif></script>
    <script>
        (function () {
            var el = document.getElementById('h5p-container');
            // Données passées par le serveur, échappées en JSON (anti-injection).
            var contentUrl = @json($contentUrl);
            var cdnBase    = @json($cdnBase);

            function fail(message) {
                el.innerHTML = '<p class="h5p-error"></p>';
                el.querySelector('.h5p-error').textContent = message;
            }

            if (typeof H5PStandalone === 'undefined' || !H5PStandalone.H5P) {
                fail('Le lecteur H5P est indisponible pour le moment. Réessayez plus tard.');
                return;
            }

            try {
                new H5PStandalone.H5P(el, {
                    h5pJsonPath: contentUrl,
                    frameJs: cdnBase + '/dist/frame.bundle.js',
                    frameCss: cdnBase + '/dist/styles/h5p.css',
                });
            } catch (e) {
                fail('Ce contenu interactif est momentanément indisponible.');
            }
        })();
    </script>
</body>
</html>
