<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sso\Models\ScimToken;
use Modules\Sso\Models\SsoConfiguration;

/**
 * @extends Factory<ScimToken>
 */
class ScimTokenFactory extends Factory
{
    protected $model = ScimToken::class;

    public function definition(): array
    {
        return [
            'sso_configuration_id' => SsoConfiguration::factory(),
            'name' => 'Jeton de test',
            'token_hash' => hash('sha256', $this->faker->uuid()),
            'last_used_at' => null,
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }
}
