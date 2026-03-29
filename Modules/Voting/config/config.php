<?php

declare(strict_types=1);

return [
    'name' => 'Voting',

    'thresholds' => [
        'noticed' => (int) env('VOTING_THRESHOLD_NOTICED', 2),
        'approved' => (int) env('VOTING_THRESHOLD_APPROVED', 5),
        'favorite' => (int) env('VOTING_THRESHOLD_FAVORITE', 10),
    ],

    'rate_limit' => (int) env('VOTING_RATE_LIMIT', 50), // votes par heure

    'reputation' => [
        'vote_cast' => 1,
        'content_community_approved' => 15,
    ],

    'badge_styles' => [
        'none' => ['color' => '#9CA3AF', 'bg' => '#F3F4F6', 'label' => '', 'icon' => ''],
        'noticed' => ['color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'Remarqué', 'icon' => '👁️'],
        'approved' => ['color' => '#10B981', 'bg' => '#F0FDF4', 'label' => 'Approuvé par la communauté', 'icon' => '✅'],
        'favorite' => ['color' => '#F59E0B', 'bg' => '#FFFBEB', 'label' => 'Favori de la communauté', 'icon' => '⭐'],
        'admin' => ['color' => '#0B7285', 'bg' => '#F0FAFB', 'label' => 'Vérifié par l\'équipe', 'icon' => '🛡️'],
    ],
];
