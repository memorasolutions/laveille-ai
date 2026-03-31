<?php

declare(strict_types=1);

return [
    'name' => 'Voting',

    'thresholds' => [
        'noticed' => (int) (class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('voting.threshold_noticed', env('VOTING_THRESHOLD_NOTICED', 2)) : env('VOTING_THRESHOLD_NOTICED', 2)),
        'approved' => (int) (class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('voting.threshold_approved', env('VOTING_THRESHOLD_APPROVED', 5)) : env('VOTING_THRESHOLD_APPROVED', 5)),
        'favorite' => (int) (class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('voting.threshold_favorite', env('VOTING_THRESHOLD_FAVORITE', 10)) : env('VOTING_THRESHOLD_FAVORITE', 10)),
    ],

    'rate_limit' => (int) (class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('voting.rate_limit', env('VOTING_RATE_LIMIT', 50)) : env('VOTING_RATE_LIMIT', 50)),

    'reputation' => [
        'vote_cast' => (int) (class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('voting.reputation_vote_cast', 1) : 1),
        'content_community_approved' => (int) (class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('voting.reputation_community_approved', 15) : 15),
    ],

    'badge_styles' => [
        'none' => ['color' => '#9CA3AF', 'bg' => '#F3F4F6', 'label' => '', 'icon' => ''],
        'noticed' => ['color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'Remarqué', 'icon' => '👁️'],
        'approved' => ['color' => '#10B981', 'bg' => '#F0FDF4', 'label' => 'Approuvé par la communauté', 'icon' => '✅'],
        'favorite' => ['color' => '#F59E0B', 'bg' => '#FFFBEB', 'label' => 'Favori de la communauté', 'icon' => '⭐'],
        'admin' => ['color' => '#0B7285', 'bg' => '#F0FAFB', 'label' => 'Vérifié par l\'équipe', 'icon' => '🛡️'],
    ],
];
