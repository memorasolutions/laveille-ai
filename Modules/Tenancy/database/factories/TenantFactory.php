<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenancy\Models\Tenant;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->slug(2),
            'domain' => $this->faker->unique()->domainName(),
            'settings' => ['timezone' => 'America/Toronto'],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withOwner(?\App\Models\User $user = null): static
    {
        return $this->state(function () use ($user) {
            return ['owner_id' => $user?->id ?? \App\Models\User::factory()->create()->id];
        });
    }
}
