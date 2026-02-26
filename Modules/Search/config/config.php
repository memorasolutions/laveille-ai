<?php

declare(strict_types=1);

return [
    'name' => 'Search',
    'models' => [
        \App\Models\User::class,
        \Modules\Blog\Models\Article::class,
        \Modules\SaaS\Models\Plan::class,
        \Modules\Blog\Models\Category::class,
        \Modules\Pages\Models\StaticPage::class,
        \Modules\Settings\Models\Setting::class,
    ],

    'types' => [
        'users' => ['label' => 'Utilisateurs', 'icon' => 'solar:users-group-two-rounded-outline'],
        'articles' => ['label' => 'Articles', 'icon' => 'solar:document-text-outline'],
        'plans' => ['label' => 'Plans', 'icon' => 'solar:star-outline'],
        'categories' => ['label' => 'Catégories', 'icon' => 'solar:tag-outline'],
        'pages' => ['label' => 'Pages', 'icon' => 'solar:widget-2-outline'],
        'settings' => ['label' => 'Paramètres', 'icon' => 'solar:settings-outline'],
    ],

    'per_page' => 15,
    'navbar_limit' => 3,
    'front_per_page' => 10,
];
