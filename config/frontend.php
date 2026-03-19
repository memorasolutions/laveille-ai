<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    'theme' => env('FRONTEND_THEME', 'bloggar'),

    'theme_modules' => [
        'fronttheme' => 'FrontTheme',
        'blog' => 'Blog',
        'pages' => 'Pages',
        'faq' => 'Faq',
        'newsletter' => 'Newsletter',
    ],

    'assets_path' => 'themes',

    'layouts' => [
        'home' => 'Home',
        'blog_list' => 'Blog List',
        'blog_single' => 'Blog Single',
    ],
];
