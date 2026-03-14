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
            self::UnderReview => 'En révision',
            self::Planned => 'Planifié',
            self::InProgress => 'En cours',
            self::Completed => 'Complété',
            self::Declined => 'Décliné',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UnderReview => 'primary',
            self::Planned => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Declined => 'secondary',
        };
    }
}
