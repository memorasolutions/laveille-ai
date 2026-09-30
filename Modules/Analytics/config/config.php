<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

return [
    'name' => 'Analytics',

    'enabled' => true,

    // Collecte serveur GA4 + Search Console (compte de service unique). Aucune valeur en dur :
    // tant qu'un des trois identifiants manque, les commandes analytics:sync-* restent inertes
    // (ligne 'skipped' dans analytics_collection_runs), sans jamais lever d'exception.
    'ga4_property_id' => env('ANALYTICS_GA4_PROPERTY_ID'),
    'gsc_site_url' => env('ANALYTICS_GSC_SITE_URL'),
    'google_credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),

    // Résolveurs de contenu (transforment une URL normalisée en entité applicative :
    // fiche News, terme Dictionary, outil Directory...), enregistrés par d'autres
    // modules. Vide par défaut : la couche de données ne dépend d'aucun résolveur.
    //
    // ORDRE SIGNIFICATIF : le premier préfixe qui correspond gagne, donc le plus spécifique en
    // premier ('/actualites/dossier/' AVANT '/actualites/'). Chaque entrée : prefix, type,
    // resolver (null | callable | ['model', 'translatable', 'scope', 'where']).
    // Le résolveur du modèle reprend la recherche de slug de sa route publique.
    'content_resolvers' => [
        // Dossier d'entité : aucun modèle, content_id reste null.
        ['prefix' => '/actualites/dossier/', 'type' => 'dossier', 'resolver' => null],
        ['prefix' => '/actualites/', 'type' => 'news_article', 'resolver' => [
            'model' => \Modules\News\Models\NewsArticle::class, 'scope' => 'published',
        ]],
        ['prefix' => '/glossaire/', 'type' => 'term', 'resolver' => [
            'model' => \Modules\Dictionary\Models\Term::class, 'translatable' => true, 'scope' => 'published',
        ]],
        ['prefix' => '/blog/', 'type' => 'blog_article', 'resolver' => [
            'model' => \Modules\Blog\Models\Article::class, 'translatable' => true, 'scope' => 'published',
        ]],
        // Outils : la route publique filtre sur is_active, pas sur un scope published.
        ['prefix' => '/outils/', 'type' => 'tool', 'resolver' => [
            'model' => \Modules\Tools\Models\Tool::class, 'where' => ['is_active' => true],
        ]],
        ['prefix' => '/annuaire/', 'type' => 'directory', 'resolver' => [
            'model' => \Modules\Directory\Models\Tool::class, 'translatable' => true, 'scope' => 'published',
        ]],
    ],
];
