<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Enums;

enum IdeaStatus: string
{
    case UnderReview = 'under_review';
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::UnderReview => __('En révision'),
            self::Planned => __('Planifié'),
            self::InProgress => __('En cours'),
            self::Completed => __('Complété'),
            self::Declined => __('Décliné'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UnderReview => '#6366f1',
            self::Planned => '#0ea5e9',
            self::InProgress => '#f59e0b',
            self::Completed => '#22c55e',
            self::Declined => '#6b7280',
        };
    }

    public function column(): string
    {
        return match ($this) {
            self::UnderReview => 'now',
            self::Planned => 'next',
            self::InProgress => 'now',
            self::Completed => 'later',
            self::Declined => 'later',
        };
    }
}
