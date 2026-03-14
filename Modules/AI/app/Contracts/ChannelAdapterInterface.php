<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Contracts;

use Modules\AI\Models\Channel;
use Modules\AI\Models\ChannelMessage;

interface ChannelAdapterInterface
{
    public function send(array $message, Channel $channel): array;

    public function receive(array $payload, Channel $channel): ?ChannelMessage;
}
