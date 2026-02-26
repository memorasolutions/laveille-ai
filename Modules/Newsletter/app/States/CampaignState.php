<?php

declare(strict_types=1);

namespace Modules\Newsletter\States;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class CampaignState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(DraftCampaignState::class)
            ->allowTransition(DraftCampaignState::class, SentCampaignState::class);
    }
}
