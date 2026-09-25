<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    'name' => 'Signature',

    /*
    |--------------------------------------------------------------------------
    | Rétention et purge (plan .devis/outil_signature_html/PLAN-OUTIL-SIGNATURE.md, section 6)
    |--------------------------------------------------------------------------
    | Une signature est purgée à 6 mois exactement, SAUF si une image a été chargée dans les 6
    | derniers mois (section 6.7) - dans ce cas la purge est reportée et réévaluée chaque jour.
    | Aucun délai de grâce arbitraire au-delà de cette règle (décision explicite du fondateur).
    | Une signature de MEMBRE (compte encore existant) n'est jamais purgée automatiquement, quelle
    | que soit son inactivité - seul le membre peut la supprimer depuis « Mes signatures ».
    */
    'retention_months' => 6,

    // Avertissement courriel UNIQUE (membre) quand l'inactivité atteint ce seuil, avant la purge
    // à 'retention_months' (idempotent via expiry_warned_at, patron decido.expiry_warning_days_before).
    'warning_months' => 5,

    // Sur-échantillonnage FIXE (décision du fondateur 2026-09-25) : logo, portrait et bannière
    // sont tous produits à ce facteur x la taille d'affichage demandée. Jamais d'agrandissement
    // au-delà de la résolution native de l'image source (repli silencieux + avertissement).
    'oversampling_factor' => 2,

    // Taille max acceptée en téléversement (octets) - volontairement sous les 10 Mo pratiqués par
    // ImagePipelineService pour d'autres usages : une signature n'a besoin ni d'un fichier
    // volumineux ni de variantes responsives multiples (section 5 du plan).
    'max_upload_bytes' => 8 * 1024 * 1024,

    // Formats acceptés en ENTRÉE (jamais le SVG, durcissement volontaire par rapport au reste du
    // site - voir section 5 du plan : surface XSS inutile pour un outil qui écrit lui-même le HTML
    // final).
    'allowed_input_mimes' => ['image/jpeg', 'image/png', 'image/webp'],

    // Garde-fou bombe de décompression (M3.1) : dimensions maximales tolérées pour un fichier
    // SOURCE, vérifiées via getimagesize() AVANT toute lecture par Intervention Image/Imagick -
    // une signature de courriel n'a jamais besoin d'un fichier source au-delà de ces bornes.
    'max_input_side_px' => 6000,
    'max_input_megapixels' => 25,

    // Cibles indicatives de poids (Ko) - non bloquantes au lancement, à surveiller.
    'target_logo_kb' => 60,
    'target_photo_kb' => 80,
    'target_total_kb' => 250,

    // Disque et dossier des dérivées servies publiquement (config/filesystems.php, disque 'public'
    // déjà servi via /storage). L'ORIGINAL téléversé n'est plus persisté du tout (M3.3) : la
    // dérivée est vérifiée valide dans la MÊME requête (SignatureImagePipeline::process()), donc
    // rien à conserver ni à nettoyer plus tard sur un disque public.
    'disk' => 'public',
    'derivatives_directory' => 'signature-derivatives',

    // Cache-Control (secondes) de la route publique de service d'image - volontairement court
    // (~1 jour) pour que les chargements reviennent jusqu'au serveur et alimentent
    // last_image_loaded_on (section 6.7-d). Jamais un cache long.
    'asset_cache_seconds' => 86400,

    // Limites de débit (section 10 du plan) - créations de brouillon et téléversements d'image
    // anonymes, par IP.
    'rate_limit_drafts_per_minute' => 5,
    'rate_limit_uploads_per_minute' => 10,

    /*
    |--------------------------------------------------------------------------
    | Quarantaine opérateur (B2 - filet de rollback avant toute suppression réelle)
    |--------------------------------------------------------------------------
    | Avant toute purge (automatique OU suppression explicite par un membre), le contenu complet
    | et les fichiers image sont archivés/déplacés ici - PRIVÉ, jamais servi au web. La donnée
    | reste reconstructible 'quarantine_retention_days' jours (signature:restore-from-quarantine),
    | puis signature:purge-quarantaine la vide définitivement (planifiée quotidiennement).
    */
    'quarantine_disk' => 'local',
    'quarantine_directory' => 'signature-quarantaine',
    'purge_archive_directory' => 'signature-purge-archive',
    'quarantine_retention_days' => 30,
];
