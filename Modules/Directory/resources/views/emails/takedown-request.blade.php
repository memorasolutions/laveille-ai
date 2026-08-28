<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Nouvelle demande de retrait</title>
</head>
<body>
    <h1>Nouvelle demande de retrait</h1>

    <ul>
        <li><strong>Outil concerné :</strong> {{ $takedown->tool?->name ?? '–' }}</li>
        <li><strong>URL ciblée :</strong> {{ $takedown->target_url }}</li>
        <li><strong>Nom du demandeur :</strong> {{ $takedown->requester_name }}</li>
        <li><strong>Courriel du demandeur :</strong> {{ $takedown->requester_email }}</li>
        <li><strong>Organisation :</strong> {{ $takedown->requester_organization ?? '–' }}</li>
        <li><strong>Rôle :</strong> {{ $takedown->requester_role }}</li>
        <li><strong>Type de droit invoqué :</strong> {{ $takedown->right_type }}</li>
        <li><strong>Détails du droit :</strong> {{ $takedown->right_details }}</li>
        <li><strong>Description du problème :</strong> {{ $takedown->description }}</li>
        <li><strong>Déclaration acceptée :</strong> {{ $takedown->declaration_accepted ? 'Oui' : 'Non' }}</li>
        <li><strong>Adresse IP :</strong> {{ $takedown->ip_address }}</li>
        <li><strong>Date de soumission :</strong> {{ $takedown->created_at->format('Y-m-d H:i') }}</li>
    </ul>

    <p><em>Rappel : vérifier la légitimité (identité + preuve de droit) avant toute action. Régime canadien : avis et avis, pas de retrait automatique.</em></p>
</body>
</html>
