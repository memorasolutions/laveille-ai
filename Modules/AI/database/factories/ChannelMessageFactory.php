<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AI\Models\Channel;
use Modules\AI\Models\ChannelMessage;

class ChannelMessageFactory extends Factory
{
    protected $model = ChannelMessage::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'external_id' => fake()->uuid(),
            'direction' => 'inbound',
            'status' => 'received',
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'sender' => fake()->email(),
            'recipient' => fake()->email(),
            'occurred_at' => now(),
        ];
    }
}
