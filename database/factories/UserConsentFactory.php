<?php


/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

namespace Database\Factories;

use App\Models\UserConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserConsentFactory extends Factory
{
    protected $model = UserConsent::class;

    public function definition(): array
    {
        return [
            'consent_token' => UserConsent::generateToken(),
            'ip_hash' => hash('sha256', $this->faker->ipv4),
            'user_agent' => $this->faker->userAgent,
            'choices' => [
                'essential' => true,
                'analytics' => $this->faker->boolean,
                'marketing' => $this->faker->boolean,
                'personalization' => $this->faker->boolean,
                'third_party' => $this->faker->boolean,
            ],
            'jurisdiction' => $this->faker->randomElement(['gdpr', 'canada_quebec', 'pipeda', 'ccpa']),
            'policy_version' => '1.0',
            'region_detected' => $this->faker->randomElement(['CA', 'FR', 'US', 'DE', 'GB']),
            'gpc_enabled' => $this->faker->boolean(20),
            'expires_at' => now()->addDays($this->faker->numberBetween(30, 365)),
        ];
    }
}
