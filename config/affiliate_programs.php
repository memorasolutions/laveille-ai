<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Table de référence des programmes d'affiliation confirmés pour les outils de l'annuaire
 * (Modules/Directory, directory_tools.affiliate_url). Recherche croisée pp_search + validation
 * Codex/Gemini, juillet 2026 — voir le plan complet dans les notes de session.
 *
 * IMPORTANT : ce fichier est un DOCUMENT DE RÉFÉRENCE pour l'admin humain qui saisit un lien
 * d'affiliation dans Modules/Directory/resources/views/admin/edit.blade.php (champ
 * "Lien d'affiliation"). Aucun code applicatif ne lit ce fichier pour deviner ou appliquer
 * automatiquement un affiliate_url — il n'existe aucun moyen fiable de détecter automatiquement
 * qu'une marque a un programme d'affiliation actif. Chaque lien reste saisi manuellement une
 * fois obtenu, ce fichier sert uniquement à documenter POURQUOI ce taux, POURQUOI cette source,
 * et QUAND l'information a été vérifiée (les taux/programmes changent).
 *
 * Clé = ecosystem_tag ou slug approximatif de l'outil (documentaire seulement, ne matche rien
 * automatiquement). 'source_confidence' : '1-src' = une seule source trouvée (à reconfirmer
 * avant d'inscrire un taux ferme dans un contrat), '2-src' = recoupé par deux sources.
 */

return [

    'canva-ai' => [
        'network' => 'Impact',
        'commission' => 'Jusqu\'à 36 $ par vente Pro, paiement unique',
        'cookie_window_days' => null,
        'verified_at' => '2026-07-24',
        'source_confidence' => '1-src',
    ],

    'elevenlabs' => [
        'network' => 'PartnerStack',
        'commission' => '22 % (11 % pour le forfait Business), récurrent 12 mois',
        'cookie_window_days' => 365,
        'verified_at' => '2026-07-24',
        'source_confidence' => '2-src',
    ],

    'grammarly' => [
        'network' => 'ShareASale',
        'commission' => '20 $ par vente + 0,20 $ par inscription, paiement unique',
        'cookie_window_days' => null,
        'verified_at' => '2026-07-24',
        'source_confidence' => '2-src',
    ],

    'copy-ai' => [
        'network' => 'Direct',
        'commission' => '45 % récurrent 12 mois (une source indique 20 % — taux à reconfirmer directement avec le programme avant tout contrat)',
        'cookie_window_days' => null,
        'verified_at' => '2026-07-24',
        'source_confidence' => '1-src',
    ],

    'notion-ai' => [
        'network' => 'PartnerStack',
        'commission' => 'Jusqu\'à 50 $ + 20 % la première année',
        'cookie_window_days' => 180,
        'verified_at' => '2026-07-24',
        'source_confidence' => '1-src',
    ],

    'runway' => [
        'network' => 'Direct',
        'commission' => '20 % récurrent, 12 mois',
        'cookie_window_days' => null,
        'verified_at' => '2026-07-24',
        'source_confidence' => '1-src',
    ],

    'murf-ai' => [
        'network' => 'PartnerStack',
        'commission' => '20 % récurrent, 24 mois',
        'cookie_window_days' => null,
        'verified_at' => '2026-07-24',
        'source_confidence' => '1-src',
    ],

    'synthesia' => [
        'network' => 'Rewardful',
        'commission' => '25 % récurrent',
        'cookie_window_days' => 60,
        'verified_at' => '2026-07-24',
        'source_confidence' => '2-src',
    ],

    'jasper' => [
        'network' => 'Direct',
        'commission' => '25-30 % récurrent, 12 mois (taux ambigu selon les sources — à reconfirmer)',
        'cookie_window_days' => null,
        'verified_at' => '2026-07-24',
        'source_confidence' => '1-src',
        // Note : fiches "Jasper AI" / "Jasper" fusionnées (doublon) — voir migration
        // 2026_07_24_120100_merge_jasper_duplicate_tools.php.
    ],

    'heygen' => [
        'network' => 'Direct (paiement PayPal)',
        'commission' => '35 % récurrent, mais 3 mois seulement',
        'cookie_window_days' => null,
        'verified_at' => '2026-07-24',
        'source_confidence' => '1-src',
    ],

    'writesonic' => [
        'network' => 'Direct',
        'commission' => '20 % récurrent',
        'cookie_window_days' => 60,
        'verified_at' => '2026-07-24',
        'source_confidence' => '2-src',
    ],

    'descript' => [
        'network' => 'Direct',
        'commission' => '25 $ forfaitaire, paiement unique',
        'cookie_window_days' => null,
        'verified_at' => '2026-07-24',
        'source_confidence' => '1-src',
    ],

];
