<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Destinations sociales : ce que le site a le DROIT de viser
|--------------------------------------------------------------------------
|
| CE FICHIER NE CONTIENT AUCUNE DONNÉE. Il LIT la source unique :
|     ~/.claude/refs/social-destinations.json
|
| Pourquoi une source unique (règle DRY, CLAUDE.md règle 11) : les mêmes
| destinations servent à laveille.ai ET aux pages clientes de MEMORA, qui
| n'ont rien à voir avec ce dépôt. Recopier la liste ici en ferait deux
| vérités, et corriger l'une en croyant avoir corrigé les deux est la
| façon la plus ordinaire de publier sur la page d'un ancien client.
|
| Le garde-fou lui-même est décrit dans le skill /publier. En résumé :
| la destination est déclarée d'avance par son identifiant, relue à
| l'écran juste avant le clic, et toute discordance arrête l'envoi.
|
| CE FICHIER NE TOUCHE À RIEN CHEZ LE CLIENT. Il n'enlève aucun accès, ne
| modifie aucune page, ne supprime aucune fiche.
|
*/

$source = getenv('HOME') . '/.claude/refs/social-destinations.json';

// Défaut SÛR : si la source est absente, on n'autorise RIEN plutôt que
// d'autoriser tout. Une liste d'exclusion introuvable ne doit jamais se
// lire comme « aucune exclusion ».
if (! is_readable($source)) {
    return [
        'source_lisible' => false,
        'anciens_clients' => ['google_business' => [], 'facebook_jetons' => []],
        'bornes_redaction' => [],
        'exiger_concordance_destination' => true,
        'approbation_humaine_obligatoire' => true,
        'refuser_toute_publication' => true,
    ];
}

$data = json_decode((string) file_get_contents($source), true);

if (! is_array($data)) {
    return [
        'source_lisible' => false,
        'anciens_clients' => ['google_business' => [], 'facebook_jetons' => []],
        'bornes_redaction' => [],
        'exiger_concordance_destination' => true,
        'approbation_humaine_obligatoire' => true,
        'refuser_toute_publication' => true,
    ];
}

$exclus = $data['anciens_clients_ne_jamais_publier'] ?? [];

/*
| VALIDATION DE SCHÉMA - défaut trouvé par revue adversariale le 2026-09-05.
|
| `is_array()` seul ne suffisait pas : un JSON valide mais VIDE (`{}` ou `[]`)
| le franchissait, produisait des exclusions vides, et retournait
| `refuser_toute_publication = false`. Autrement dit, un fichier corrompu ou
| tronqué se lisait comme « aucun ancien client à exclure », ce qui est
| exactement l'inverse du défaut sûr annoncé.
|
| On exige donc que la structure porte réellement ce qu'elle prétend porter.
*/
$structureValide = ! empty($exclus['google_business'])
    && ! empty($exclus['facebook_jetons_obligatoires'])
    && ! empty($data['bornes_redaction']['linkedin']['coupure_voir_plus'])
    && ! empty($data['bornes_redaction']['facebook']['coupure_voir_plus']);

if (! $structureValide) {
    return [
        'source_lisible' => true,
        'source_chemin' => $source,
        'schema_valide' => false,
        'anciens_clients' => ['google_business' => [], 'facebook_jetons' => []],
        'bornes_redaction' => [],
        'exiger_concordance_destination' => true,
        'approbation_humaine_obligatoire' => true,
        'refuser_toute_publication' => true,
    ];
}

return [
    'source_lisible' => true,
    'schema_valide' => true,
    'source_chemin' => $source,

    'anciens_clients' => [
        'google_business' => $exclus['google_business'] ?? [],
        'facebook_jetons' => $exclus['facebook_jetons_obligatoires'] ?? [],
    ],

    'comptes_facebook' => $data['comptes_facebook'] ?? [],
    'bornes_redaction' => $data['bornes_redaction'] ?? [],

    // Le seul garde-fou qui agit APRÈS toutes les manipulations d'interface :
    // relire à l'écran la destination réellement sélectionnée et la comparer
    // à celle déclarée. Une liste d'exclusion protège contre une destination
    // connue comme interdite ; elle ne protège pas du mauvais clic sur une
    // destination autorisée.
    'exiger_concordance_destination' => true,

    // Publier est public et irréversible. Pour une page cliente, c'est le
    // CLIENT qui parle, et il n'est pas dans la boucle.
    'approbation_humaine_obligatoire' => true,

    'refuser_toute_publication' => false,
];
