{{--
    Page LECTEUR SCORM - rendue DANS L'IFRAME SANDBOX du lecteur de leçon.
    Auteur : MEMORA solutions <info@memora.ca> (https://memora.solutions)

    Document AUTONOME (n'étend pas le thème) qui définit le PONT API SCORM
    minimal (window.API 1.2 / window.API_1484_11 2004 basique) puis charge le
    SCO dans une iframe IMBRIQUÉE pointant vers ScormAssetController (disque
    privé, jamais public). La CSP dédiée est posée par ScormPlayerController.

    Variables (toutes fournies par le contrôleur, jamais par le client) :
    - $title     : titre de l'élément (a11y)
    - $launchSrc : URL protégée (ScormAssetController) du fichier de lancement
    - $commitUrl : URL du commit runtime (ScormCommitController)
    - $nonce     : nonce CSP à usage unique autorisant CE script inline
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; background: #fff; }
        #scorm-frame { width: 100%; height: 100%; min-height: 480px; border: 0; display: block; }
    </style>
</head>
<body>
    <iframe id="scorm-frame"
        src="{{ $launchSrc }}"
        title="{{ $title }}"
        sandbox="allow-scripts allow-same-origin allow-forms"
        loading="lazy"
        referrerpolicy="strict-origin-when-cross-origin"
    ></iframe>

    {{--
        PONT API SCORM (window.API / window.API_1484_11) - RECHERCHÉ par le SCO
        via la remontée de la chaîne window.parent (spec SCORM). L'iframe imbriquée
        ci-dessus est SANDBOX « allow-same-origin » : le SCO partage notre origine
        et peut donc trouver window.parent.API. RISQUE CONNU (dette assumée, comme
        H5P) : ceci laisse le JS du SCO s'exécuter dans NOTRE origine (peut lire le
        DOM parent). Accepté car le téléversement d'un paquet SCORM est restreint
        aux ADMINS de confiance (permission « academy.manage »).

        PÉRIMÈTRE MVP : seules cmi.core.lesson_status / cmi.core.score.raw (1.2)
        et cmi.completion_status / cmi.success_status / cmi.score.raw (2004) sont
        capturées et persistées ; le suivi granulaire par interaction
        (cmi.interactions.*) N'EST PAS implémenté.
    --}}
    <script nonce="{{ $nonce }}">
    (function () {
        'use strict';

        var COMMIT_URL  = @json($commitUrl);
        var CSRF_TOKEN  = @json(csrf_token());
        var cmi         = Object.create(null);
        var initialized = false;
        var finished    = false;

        // Valeurs initiales raisonnables (repli « incomplete » si le SCO ne
        // définit jamais le statut avant la fin de session).
        cmi['cmi.core.lesson_status'] = 'incomplete';
        cmi['cmi.completion_status']  = 'incomplete';
        cmi['cmi.success_status']     = 'unknown';

        function commit(useBeacon) {
            var payload = JSON.stringify(cmi);

            if (useBeacon && navigator.sendBeacon) {
                try {
                    var blob = new Blob([payload], { type: 'application/json' });
                    if (navigator.sendBeacon(COMMIT_URL, blob)) {
                        return true;
                    }
                } catch (e) { /* repli XHR ci-dessous */ }
            }

            try {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', COMMIT_URL, !useBeacon);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.setRequestHeader('X-CSRF-TOKEN', CSRF_TOKEN);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(payload);
            } catch (e) { /* commit best-effort : ne bloque jamais le SCO */ }

            return true;
        }

        // ── API SCORM 1.2 (LMSInitialize/LMSGetValue/LMSSetValue/LMSCommit/LMSFinish) ──
        window.API = {
            LMSInitialize: function () { initialized = true; return 'true'; },
            LMSFinish: function () { finished = true; commit(true); return 'true'; },
            LMSGetValue: function (key) {
                var v = cmi[key];
                return (v === undefined || v === null) ? '' : String(v);
            },
            LMSSetValue: function (key, value) { cmi[key] = value; return 'true'; },
            LMSCommit: function () { return commit(false) ? 'true' : 'false'; },
            LMSGetLastError: function () { return '0'; },
            LMSGetErrorString: function () { return 'No error'; },
            LMSGetDiagnostic: function () { return ''; }
        };

        // ── API SCORM 2004 (mode BASIQUE - pas de moteur de séquencement IMS SS) ──
        window.API_1484_11 = {
            Initialize: function () { initialized = true; return 'true'; },
            Terminate: function () { finished = true; commit(true); return 'true'; },
            GetValue: function (key) {
                var v = cmi[key];
                return (v === undefined || v === null) ? '' : String(v);
            },
            SetValue: function (key, value) { cmi[key] = value; return 'true'; },
            Commit: function () { return commit(false) ? 'true' : 'false'; },
            GetLastError: function () { return '0'; },
            GetErrorString: function () { return 'No error'; },
            GetDiagnostic: function () { return ''; }
        };

        // Filet de sécurité : si le SCO quitte sans appeler LMSFinish/Terminate
        // (fermeture d'onglet, navigation), on tente un dernier commit best-effort.
        window.addEventListener('beforeunload', function () {
            if (initialized && !finished) {
                commit(true);
            }
        });
    })();
    </script>
</body>
</html>
