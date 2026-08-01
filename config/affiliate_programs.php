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
 * 'status' : 'ouvert' = candidature possible ; 'ferme' = programme arrêté ou inaccessible de
 * fait, ne pas y consacrer de temps ; absent = ouvert par défaut.
 *
 * REVÉRIFICATION DU 2026-08-01 : trois entrées de juillet étaient devenues FAUSSES (Canva,
 * Notion, Runway). Un fichier de référence périmé est pire que pas de fichier : c'est
 * exactement celui qu'on consulte avant de s'engager sur un taux. Détail complet des sources
 * dans .outils/affiliation-programmes-directs-2026-08-01.md.
 *
 * Constat transversal de cette revérification : sur 26 programmes examinés, AUCUN n'affiche
 * publiquement de seuil de trafic minimum chiffré. Le vrai obstacle n'est donc pas un volume
 * à atteindre, c'est l'approbation discrétionnaire - ce qui éclaire les deux refus PartnerStack.
 */

return [

    'canva-ai' => [
        'network' => 'Impact (FERMÉ)',
        'commission' => 'Programme Impact arrêté depuis janvier 2024. Remplacé par un programme '
            .'sur invitation ou approbation seulement. L\'ancienne mention « jusqu\'à 36 $ par '
            .'vente Pro » ne vaut plus rien : ne pas s\'en servir.',
        'cookie_window_days' => null,
        'status' => 'ferme',
        'verified_at' => '2026-08-01',
        'source_confidence' => '1-src',
    ],

    'elevenlabs' => [
        'network' => 'PartnerStack',
        'commission' => '22 % (11 % pour le forfait Business), récurrent 12 mois',
        // Corrigé le 2026-08-01 : la fenêtre réelle est de 90 jours, pas 365. L'écart était
        // d'un facteur 4 en faveur du site, donc trompeur dans le bon sens - le genre d'erreur
        // qu'on ne remarque pas tant qu'on ne compare pas au relevé réel du programme.
        'cookie_window_days' => 90,
        'verified_at' => '2026-08-01',
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
        // Corrigé le 2026-08-01 : étiqueté « Direct » en juillet, en réalité via un réseau ou
        // ambigu selon les sources. Le taux reste non recoupé, donc inutilisable en l'état.
        'network' => 'À reconfirmer (pas « Direct » comme indiqué en juillet)',
        'commission' => '45 % récurrent 12 mois selon une source, 20 % selon une autre. Écart '
            .'trop grand pour engager quoi que ce soit : demander le taux au programme.',
        'cookie_window_days' => null,
        'verified_at' => '2026-08-01',
        'source_confidence' => '1-src',
    ],

    'notion-ai' => [
        'network' => 'PartnerStack (candidature auto-refusée)',
        'commission' => 'La page publique du programme existe toujours, mais la candidature '
            .'PartnerStack ressort « auto-declined ». Fermé de fait. C\'est exactement le refus '
            .'discrétionnaire déjà vécu deux fois avec ce réseau : ne pas réessayer sans un '
            .'contact humain chez Notion.',
        'cookie_window_days' => 180,
        'status' => 'ferme',
        'verified_at' => '2026-08-01',
        'source_confidence' => '1-src',
    ],

    'runway' => [
        'network' => 'Direct (sur candidature revue)',
        // Corrigé le 2026-08-01 : ce n'est plus du tout du récurrent. Le passage de « 20 %
        // récurrent 12 mois » à un forfait unique change complètement l'intérêt du programme.
        'commission' => '15 $ US forfaitaire par abonné, versement unique, après examen de la '
            .'candidature et une période pilote de 3 mois. Le « 20 % récurrent 12 mois » inscrit '
            .'en juillet n\'existe plus.',
        'cookie_window_days' => null,
        'verified_at' => '2026-08-01',
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
        // Corrigé le 2026-08-01 : étiqueté « Direct » en juillet, passe en réalité par
        // PartnerStack - donc soumis au même refus discrétionnaire que Notion.
        'network' => 'PartnerStack (et non « Direct »)',
        'commission' => '25 $ forfaitaire, paiement unique',
        'cookie_window_days' => null,
        'verified_at' => '2026-08-01',
        'source_confidence' => '1-src',
    ],

];
