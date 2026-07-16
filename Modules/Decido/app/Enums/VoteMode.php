<?php

declare(strict_types=1);

namespace Modules\Decido\Enums;

enum VoteMode: string
{
    case YES_NO_MAYBE = 'yes_no_maybe';
    case SINGLE_CHOICE = 'single_choice';
    case APPROVAL = 'approval';

    public function label(): string
    {
        return match ($this) {
            self::YES_NO_MAYBE => 'Oui / Non / Peut-être',
            self::SINGLE_CHOICE => 'Choix unique',
            self::APPROVAL => 'Approbation',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function forType(PollType $type): array
    {
        return match ($type) {
            PollType::DATE => [self::YES_NO_MAYBE],
            PollType::CLASSIC => [self::SINGLE_CHOICE, self::APPROVAL],
        };
    }
}
