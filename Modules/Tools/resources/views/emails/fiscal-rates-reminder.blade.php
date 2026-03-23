<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        .alert { background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #ffeeba; }
        .details { background-color: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 0.9em; }
        .footer { margin-top: 30px; font-size: 0.8em; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Mise à jour fiscale requise</h2>

        <div class="alert">
            <strong>Attention :</strong> Le fichier de configuration du simulateur fiscal semble obsolète.
        </div>

        <p>Bonjour,</p>

        <p>Le système a détecté que l'année configurée dans le simulateur ne correspond pas à l'année civile en cours. Les taux d'imposition, seuils et cotisations ont probablement changé.</p>

        <div class="details">
            <strong>Année actuelle :</strong> {{ $currentYear }}<br>
            <strong>Année configurée :</strong> {{ $configYear }}<br>
            <strong>Dernière mise à jour :</strong> {{ $lastUpdated }}<br>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 10px 0;">
            <strong>Fichier à modifier :</strong><br>
            <code>Modules/Tools/resources/data/simulateur-fiscal.json</code>
        </div>

        <h3>Actions requises :</h3>
        <ol>
            <li>Vérifier les nouveaux taux fiscaux fédéral et provincial {{ $currentYear }}.</li>
            <li>Mettre à jour les seuils RRQ, AE, RQAP, REER dans le fichier JSON.</li>
            <li>Changer <code>meta.year</code> à <strong>{{ $currentYear }}</strong> et <code>meta.lastUpdated</code> à la date du jour.</li>
            <li>Vider le cache : <code>php artisan responsecache:clear</code></li>
        </ol>

        <p><strong>Sources officielles :</strong></p>
        <ul>
            <li><a href="https://www.canada.ca/fr/agence-revenu/services/impot/particuliers/foire-questions-particuliers/taux-imposition-canadiens-particuliers-annee-courante-annees-passees.html">ARC — taux d'imposition fédéral</a></li>
            <li><a href="https://www.revenuquebec.ca/fr/citoyens/declaration-de-revenus/produire-votre-declaration-de-revenus/taux-dimposition/">Revenu Québec — taux d'imposition provincial</a></li>
            <li><a href="https://www.retraitequebec.gouv.qc.ca/fr/publications/rrq/rrq/Pages/cotisations.aspx">Retraite Québec — cotisations RRQ</a></li>
        </ul>

        <div class="footer">
            Message automatique du module Tools.<br>
            Commande : <code>php artisan tools:check-fiscal-rates</code>
        </div>
    </div>
</body>
</html>
