<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

return [
    'name' => 'Roadmap',

    'board_columns' => [
        'now' => ['under_review', 'in_progress'],
        'next' => ['planned'],
        'later' => ['completed', 'declined'],
    ],

    'default_hide_votes' => false,

    'ideas_per_page' => 20,
];
