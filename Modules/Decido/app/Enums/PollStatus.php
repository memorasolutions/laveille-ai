<?php

declare(strict_types=1);

namespace Modules\Decido\Enums;

enum PollStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::OPEN => 'Ouvert',
            self::CLOSED => 'Fermé',
        };
    }
}
