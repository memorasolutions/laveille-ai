<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Services;

use InvalidArgumentException;
use Modules\AI\Contracts\ChannelAdapterInterface;
use Modules\AI\Models\Channel;

class ChannelRegistry
{
    protected array $adapters = [];

    public function register(string $type, string $adapterClass): void
    {
        $this->adapters[$type] = $adapterClass;
    }

    public function adapterFor(Channel $channel): ChannelAdapterInterface
    {
        $type = $channel->type;

        if (! isset($this->adapters[$type])) {
            throw new InvalidArgumentException("No adapter registered for channel type: {$type}");
        }

        return app($this->adapters[$type]);
    }

    public function allRegistered(): array
    {
        return $this->adapters;
    }
}
