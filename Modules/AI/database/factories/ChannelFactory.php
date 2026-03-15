<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\AI\Models\Channel;

class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['email', 'whatsapp', 'telegram']);

        return [
            'type' => $type,
            'name' => fake()->company().' '.$type,
            'credentials' => [],
            'settings' => [],
            'is_active' => true,
            'inbound_secret' => Str::random(40),
        ];
    }
}
