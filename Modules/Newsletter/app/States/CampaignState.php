<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

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
