<?php

declare(strict_types=1);

namespace Modules\Decido\Enums;

enum PollType: string
{
    case DATE = 'date';
    case CLASSIC = 'classic';

    public function label(): string
    {
        return match ($this) {
            self::DATE => 'Sondage de dates',
            self::CLASSIC => 'Sondage classique',
        };
    }
}
