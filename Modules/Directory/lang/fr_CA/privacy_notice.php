<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Textes du module « précaution données personnelles » (annuaire). Aucun texte en dur dans le
 * code ni dans les vues. Une clé de base (« general ») + une variante par type de contenu
 * (« variants »), pour ne pas dupliquer la phrase. Ton : conseil de minimisation à
 * l'utilisateur, jamais un jugement sur l'outil.
 */

return [
    'title' => 'Précaution pour vos renseignements personnels',
    'general' => "Avant d'envoyer :subject à un outil d'IA, transmettez seulement les renseignements nécessaires :purpose et retirez le reste, comme :examples. Vérifiez comment le service utilise, conserve et partage les documents avant l'envoi.",
    'variants' => [
        'cv' => [
            'subject' => 'votre CV',
            'purpose' => 'à sa révision',
            'examples' => 'votre adresse complète, votre date de naissance ou les coordonnées de vos références',
        ],
        'documents' => [
            'subject' => 'vos documents',
            'purpose' => 'à leur traitement',
            'examples' => "vos coordonnées complètes, vos numéros d'identification ou les renseignements sur d'autres personnes",
        ],
    ],
    'verified' => 'Selon sa politique de confidentialité (vérifiée le :date), :note.',
    'policy_link' => 'Lire la politique de confidentialité de :tool',
    'new_tab' => '(nouvel onglet)',
];
