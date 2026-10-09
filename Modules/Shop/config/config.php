<?php

declare(strict_types=1);

return [
    'name' => 'Shop',
    'enabled' => env('SHOP_ENABLED', true),
    'maintenance' => env('SHOP_MAINTENANCE', false),
    // Boutique visible du seul super-admin (404 pour tous les autres, webhooks épargnés).
    'founder_only' => env('SHOP_FOUNDER_ONLY', false),

    'gelato' => [
        'api_key' => env('GELATO_API_KEY'),
        'api_url' => env('GELATO_API_URL', 'https://api.gelato.com'),
        'store_id' => env('GELATO_STORE_ID'),
        'webhook_secret' => env('GELATO_WEBHOOK_SECRET'),
    ],

    'gelato_webhook_secret' => env('GELATO_WEBHOOK_SECRET'),

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_SHOP_WEBHOOK_SECRET'),
    ],

    /*
     * DRAPEAU « Gelato zéro erreur » (défaut : désactivé, la prod ne change pas).
     * ON = fichier d'impression préparé et approuvé obligatoire, idempotence de
     * la commande Gelato, signatures webhooks Gelato et Stripe en fail-closed
     * (secrets GELATO_WEBHOOK_SECRET et STRIPE_SHOP_WEBHOOK_SECRET OBLIGATOIRES).
     */
    // Minutes avant qu'un verrou de soumission Gelato sans gelato_order_id soit jugé orphelin (réconciliation + alerte).
    'gelato_orphan_minutes' => (int) env('SHOP_GELATO_ORPHAN_MINUTES', 10),
    'gelato_zero_erreur' => (bool) env('SHOP_GELATO_ZERO_ERREUR', false),
    // Marqueur d'environnement gravé (métadonnée) sur chaque commande Gelato : la réconciliation n'adopte jamais une commande
    // d'un autre environnement du même compte. Vide = hôte de APP_URL.
    // M7 (documenté, revue fable 2026-10-08) : poser SHOP_GELATO_ENV_MARKER EXPLICITEMENT, avec une valeur DIFFÉRENTE par
    // environnement (ex. prod / local / staging). Un marqueur absent ou identique entre environnements qui partagent le même
    // compte Gelato affaiblit l'isolation de la réconciliation (une commande d'un autre environnement pourrait être adoptée).
    'gelato_env_marker' => env('SHOP_GELATO_ENV_MARKER'),

    // Éditeur client (le client conçoit, le serveur re-rend via le moteur). Exige AUSSI gelato_zero_erreur. OFF = aucune route.
    'gelato_editor' => (bool) env('SHOP_GELATO_EDITOR', false),

    // Synchro catalogue Gelato : publier (status=published) les produits Gelato actifs/prêts. false = brouillon.
    'gelato_sync_autopublish' => (bool) env('SHOP_GELATO_SYNC_AUTOPUBLISH', true),
    'editor' => [
        'safe_margin_mm' => (float) env('SHOP_EDITOR_SAFE_MARGIN_MM', 10),
        'default_area_mm' => ['widthMm' => 300.0, 'heightMm' => 400.0], // surchargeable par metadata.editor_area_mm du produit
        'max_elements' => 30,
        'max_upload_kb' => 15360,
        'moderator' => null, // classe implémentant ContentModeratorContract (défaut : NullContentModerator)
    ],

    // Moteur « print-prep » (projet mcp_gelato) : HTTP local, repli CLI.
    'print_prep' => [
        'url' => env('GELATO_PREP_URL'),
        'secret' => env('GELATO_PREP_SECRET'),
        'cli_path' => env('GELATO_PREP_CLI'),   // ex. /chemin/mcp_gelato/dist/cli/prepare.js
        'node_binary' => env('GELATO_PREP_NODE', 'node'),
        'timeout' => (int) env('GELATO_PREP_TIMEOUT', 60),
        'connect_timeout' => (int) env('GELATO_PREP_CONNECT_TIMEOUT', 5),
    ],

    'currency' => env('SHOP_CURRENCY', 'CAD'),

    'tax' => [
        'tps' => (float) env('SHOP_TAX_TPS', 5.0),
        'tvq' => (float) env('SHOP_TAX_TVQ', 9.975),

        // Taxe PROVINCIALE de vente (TVP/RST) : NON perçue par défaut. Percevoir une taxe sans y être inscrit est pire que
        // ne pas la percevoir. À n'activer (SHOP_TAX_COLLECT_PST=true) qu'après inscription à la TVP C.-B./Sask./Man.
        'collect_pst' => (bool) env('SHOP_TAX_COLLECT_PST', false),

        // Barème par province de LIVRAISON (taux en %, recoupés 2026-10-09). Clés :
        //   'hst' => taux unique combiné (TVH)        'qst' => true : TPS + TVQ (taux ci-dessus)
        //   'pst' => taxe provinciale, utilisée SEULEMENT si collect_pst = true
        // Province absente de la table = TPS seule (repli sûr). Hors Canada = 0, géré par le code.
        'provinces' => [
            'ON' => ['hst' => 13.0],
            'NB' => ['hst' => 15.0],
            'NS' => ['hst' => 14.0], // baissée de 15 % le 2025-04-01
            'PE' => ['hst' => 15.0],
            'NL' => ['hst' => 15.0],
            'QC' => ['qst' => true],
            'AB' => [],
            'BC' => ['pst' => 7.0],
            'SK' => ['pst' => 6.0],
            'MB' => ['pst' => 7.0],
            'YT' => [],
            'NT' => [],
            'NU' => [],
        ],
    ],

    'handling_fee' => (float) env('SHOP_HANDLING_FEE', 1.00),

    'shipping_countries' => env('SHOP_SHIPPING_COUNTRIES')
        ? array_values(array_filter(array_map('trim', explode(',', (string) env('SHOP_SHIPPING_COUNTRIES')))))
        : ['CA'],

    'cart' => [
        'expiry_hours' => (int) env('SHOP_CART_EXPIRY_HOURS', 72),
    ],

    'routes' => [
        'prefix' => env('SHOP_ROUTES_PREFIX', 'boutique'),
        'admin_prefix' => env('SHOP_ROUTES_ADMIN_PREFIX', 'admin/shop'),
    ],

    'pagination' => (int) env('SHOP_PAGINATION', 12),

    'pricing' => [
        'usd_cad_rate' => (float) env('SHOP_USD_CAD_RATE', 1.40),
        // Plancher de taux de sécurité : taux de PRIX = max(taux vivant, plancher). Un CAD fort (moins de CAD par USD)
        // baisserait le prix de vente sans baisser le coût d'impression payé en USD : le plancher empêche cette érosion de marge.
        'usd_cad_rate_floor' => (float) env('SHOP_USD_CAD_RATE_FLOOR', 1.40),
        // Frais Stripe (2,9 % + 0,30 $ CAD) : le pourcentage est absorbé dans le prix produit; le fixe entre dans le garde-fou.
        'stripe_fee_pct' => (float) env('SHOP_STRIPE_FEE_PCT', 0.029),
        'stripe_fee_fixed' => (float) env('SHOP_STRIPE_FEE_FIXED', 0.30),
        // Garde-fou AVANT encaissement : marge nette minimale (CAD) exigée par commande. 1,00 $ et non 0 : un plancher à 0
        // laisse passer une commande à l'équilibre exact, qu'une dérive de coût mineure ou un arrondi font basculer en perte.
        'floor_margin_cad' => (float) env('SHOP_FLOOR_MARGIN_CAD', 1.00),
        // Coût inconnu (aucun cost_base / cost_by_uid valide) : la commande est TOUJOURS refusée; ce drapeau n'ajuste que le message.
        'floor_block_unknown_cost' => (bool) env('SHOP_FLOOR_BLOCK_UNKNOWN_COST', true),
        'estimated_shipping_usd' => (float) env('SHOP_ESTIMATED_SHIPPING_USD', 11.00),
        'shipping_by_category' => [
            'hoodies' => 12.00,
            't-shirts' => 8.00,
            'mugs' => 6.00,
            'tote-bags' => 7.00,
            'water-bottles' => 7.00,
            'posters' => 5.00,
        ],
        'margins' => [
            'hoodies' => 0.30,
            't-shirts' => 0.30,
            'mugs' => 0.35,
            'tote-bags' => 0.30,
            'water-bottles' => 0.30,
            'posters' => 0.25,
            'default' => 0.30,
        ],
    ],
];
