<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    */
    'company' => [
        'name' => env('COMPANY_NAME', 'La veille de Stef'),
        'address' => env('COMPANY_ADDRESS', '1501, rue Saint-Benoit, L\'Ancienne-Lorette, QC G2E 1P2, Canada'),
        'email' => env('COMPANY_EMAIL', 'info@laveille.ai'),
        'dpo_email' => env('DPO_EMAIL', 'politiques@memora.ca'),
        'dpo_name' => env('DPO_NAME', 'Stéphane Lapointe'),
        'phone' => env('COMPANY_PHONE', ''),
        'country' => env('COMPANY_COUNTRY', 'CA'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal Documents
    |--------------------------------------------------------------------------
    */
    'documents' => [
        'privacy_policy' => [
            'version' => '3.0',
            'url' => '/privacy-policy',
            'updated_at' => '2026-03-29',
        ],
        'terms' => [
            'version' => '3.0',
            'url' => '/terms-of-use',
            'updated_at' => '2026-03-29',
        ],
        'cookie_policy' => [
            'version' => '2.0',
            'url' => '/cookie-policy',
            'updated_at' => '2026-03-29',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent Management
    |--------------------------------------------------------------------------
    */
    'consent' => [
        'expiration' => [
            'gdpr' => 180,
            'canada_quebec' => 365,
            'pipeda' => 365,
            'ccpa' => 365,
        ],
        'cookie_name' => 'consent_v1',
        'proof_retention_years' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Jurisdictions
    |--------------------------------------------------------------------------
    */
    'jurisdictions' => [
        'gdpr' => [
            'label' => 'RGPD / GDPR',
            'requires_explicit_consent' => true,
            'gpc_binding' => false,
            'authorities' => [
                'CNIL' => 'https://www.cnil.fr',
                'ICO' => 'https://ico.org.uk',
            ],
        ],
        'canada_quebec' => [
            'label' => 'Loi 25 (Quebec)',
            'requires_explicit_consent' => true,
            'gpc_binding' => false,
            'authorities' => [
                'CAI' => 'https://www.cai.gouv.qc.ca',
            ],
        ],
        'pipeda' => [
            'label' => 'LPRPDE / PIPEDA',
            'requires_explicit_consent' => false,
            'authorities' => [
                'OPC' => 'https://www.priv.gc.ca',
            ],
        ],
        'ccpa' => [
            'label' => 'CCPA (California)',
            'requires_explicit_consent' => false,
            'gpc_binding' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie Categories
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'essential' => [
            'label_fr' => 'Strictement necessaire',
            'label_en' => 'Strictly Necessary',
            'required' => true,
            'cookies' => [
                [
                    'name' => 'XSRF-TOKEN',
                    'provider' => 'Application',
                    'purpose_fr' => 'Protection contre les attaques CSRF',
                    'purpose_en' => 'Protection against Cross-Site Request Forgery attacks',
                    'duration' => 'Session',
                ],
                [
                    'name' => 'laravel_session',
                    'provider' => 'Application',
                    'purpose_fr' => 'Gestion de la session utilisateur',
                    'purpose_en' => 'User session management',
                    'duration' => '2 heures / 2 hours',
                ],
                [
                    'name' => 'consent_v1',
                    'provider' => 'Application',
                    'purpose_fr' => 'Memorisation des choix de consentement',
                    'purpose_en' => 'Consent choices storage',
                    'duration' => '6-12 mois / 6-12 months',
                ],
            ],
        ],
        'analytics' => [
            'label_fr' => 'Statistiques',
            'label_en' => 'Analytics',
            'required' => false,
            'cookies' => [
                [
                    'name' => '_ga',
                    'provider' => 'Google LLC',
                    'purpose_fr' => 'Identifiant unique pour distinguer les utilisateurs',
                    'purpose_en' => 'Unique identifier to distinguish users',
                    'duration' => '2 ans / 2 years',
                ],
                [
                    'name' => '_gid',
                    'provider' => 'Google LLC',
                    'purpose_fr' => 'Identifiant unique pour distinguer les sessions',
                    'purpose_en' => 'Unique identifier to distinguish sessions',
                    'duration' => '24 heures / 24 hours',
                ],
            ],
        ],
        'marketing' => [
            'label_fr' => 'Marketing',
            'label_en' => 'Marketing',
            'required' => false,
            'cookies' => [
                [
                    'name' => '_fbp',
                    'provider' => 'Meta Platforms, Inc.',
                    'purpose_fr' => 'Suivi des conversions Facebook',
                    'purpose_en' => 'Facebook conversion tracking',
                    'duration' => '3 mois / 3 months',
                ],
                [
                    'name' => '_gcl_au',
                    'provider' => 'Google LLC',
                    'purpose_fr' => 'Suivi des conversions Google Ads',
                    'purpose_en' => 'Google Ads conversion tracking',
                    'duration' => '3 mois / 3 months',
                ],
            ],
        ],
        'personalization' => [
            'label_fr' => 'Personnalisation',
            'label_en' => 'Personalization',
            'required' => false,
            'cookies' => [
                [
                    'name' => 'locale',
                    'provider' => 'Application',
                    'purpose_fr' => 'Memoriser la langue preferee',
                    'purpose_en' => 'Remember preferred language',
                    'duration' => '1 an / 1 year',
                ],
                [
                    'name' => 'theme',
                    'provider' => 'Application',
                    'purpose_fr' => 'Memoriser le theme d\'affichage',
                    'purpose_en' => 'Remember display theme preference',
                    'duration' => '1 an / 1 year',
                ],
            ],
        ],
        'third_party' => [
            'label_fr' => 'Services tiers',
            'label_en' => 'Third-Party Services',
            'required' => false,
            'cookies' => [
                [
                    'name' => '__stripe_mid',
                    'provider' => 'Stripe, Inc.',
                    'purpose_fr' => 'Prevention de la fraude et gestion des paiements',
                    'purpose_en' => 'Fraud prevention and payment processing',
                    'duration' => '1 an / 1 year',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Third-Party Scripts (loaded conditionally after consent)
    |--------------------------------------------------------------------------
    */
    'scripts' => [
        [
            'category' => 'analytics',
            'name' => 'Google Analytics',
            'code' => '<script async src="https://www.googletagmanager.com/gtag/js?id='.env('GA_MEASUREMENT_ID', 'G-XXXXXXXXXX').'"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","'.env('GA_MEASUREMENT_ID', 'G-XXXXXXXXXX').'");</script>',
            'enabled' => env('PRIVACY_GA_ENABLED', false),
        ],
        [
            'category' => 'marketing',
            'name' => 'Facebook Pixel',
            'code' => '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");fbq("init","'.env('FB_PIXEL_ID', 'XXXXXXXXXXXXXXX').'");fbq("track","PageView");</script>',
            'enabled' => env('PRIVACY_FB_PIXEL_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Subject Rights
    |--------------------------------------------------------------------------
    */
    'rights' => [
        'types' => [
            'access',
            'rectification',
            'erasure',
            'portability',
            'opposition',
            'limitation',
            'withdrawal',
        ],
        'response_delay_days' => 30,
        'notification_email' => env('PRIVACY_DPO_EMAIL', 'dpo@yourcompany.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Privacy Control (GPC)
    |--------------------------------------------------------------------------
    */
    'gpc' => [
        'respect_in' => ['gdpr', 'ccpa'],
        'header' => 'Sec-GPC',
    ],

    /*
    |--------------------------------------------------------------------------
    | Minors Age Thresholds
    |--------------------------------------------------------------------------
    */
    'minors' => [
        'eu_age' => 16,
        'canada_age' => 13,
    ],
];
