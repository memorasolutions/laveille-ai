<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Events;

use Modules\AI\Models\Channel;
use Modules\AI\Models\ChannelMessage;

class ChannelMessageReceived
{
    public function __construct(
        public ChannelMessage $channelMessage,
        public Channel $channel,
    ) {}
}
