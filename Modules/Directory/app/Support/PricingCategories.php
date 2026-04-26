<?php

declare(strict_types=1);

namespace Modules\Directory\Support;

final class PricingCategories
{
    private function __construct() {}

    public const FREE = 'free';
    public const FREEMIUM = 'freemium';
    public const PAID = 'paid';
    public const OPEN_SOURCE = 'open_source';
    public const ENTERPRISE = 'enterprise';
    public const FREE_TRIAL = 'free_trial';

    public static function values(): array
    {
        return [
            self::FREE,
            self::FREEMIUM,
            self::PAID,
            self::OPEN_SOURCE,
            self::ENTERPRISE,
            self::FREE_TRIAL,
        ];
    }

    public static function labels(): array
    {
        return [
            self::FREE => __('Gratuit'),
            self::FREEMIUM => __('Freemium'),
            self::PAID => __('Payant'),
            self::OPEN_SOURCE => __('Open source'),
            self::ENTERPRISE => __('Entreprise'),
            self::FREE_TRIAL => __('Essai gratuit'),
        ];
    }

    public static function emojis(): array
    {
        return [
            self::FREE => '🆓',
            self::FREEMIUM => '💎',
            self::PAID => '💰',
            self::OPEN_SOURCE => '🔓',
            self::ENTERPRISE => '🏢',
            self::FREE_TRIAL => '⏱️',
        ];
    }

    public static function colors(): array
    {
        return [
            self::FREE => ['#D1FAE5', '#065F46'],
            self::FREEMIUM => ['#DBEAFE', '#1E40AF'],
            self::PAID => ['#FEF3C7', '#92400E'],
            self::OPEN_SOURCE => ['#CCFBF1', '#115E59'],
            self::ENTERPRISE => ['#EDE9FE', '#5B21B6'],
            self::FREE_TRIAL => ['#FEF9C3', '#713F12'],
        ];
    }

    public static function optionsWithEducation(): array
    {
        return array_merge(self::labels(), [
            'education' => __('🎓 Tarif éducation'),
        ]);
    }
}
