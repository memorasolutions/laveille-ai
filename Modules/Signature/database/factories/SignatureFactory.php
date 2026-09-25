<?php

declare(strict_types=1);

namespace Modules\Signature\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Signature\Models\Signature;

class SignatureFactory extends Factory
{
    protected $model = Signature::class;

    public function definition(): array
    {
        return [
            'admin_token_hash' => hash('sha256', fake()->uuid()),
            'template' => Signature::TEMPLATES[0],
            'content' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'job_title' => fake()->jobTitle(),
                'organization' => fake()->company(),
                'email' => fake()->safeEmail(),
                'phone' => '514-555-0100',
                'social_links' => [],
                'mention_lines' => [],
            ],
            'schema_version' => 1,
            'status' => Signature::STATUS_ACTIVE,
            'last_owner_activity_at' => now(),
        ];
    }
}
