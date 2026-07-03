<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sso\Models\SsoConfiguration;

/**
 * @extends Factory<SsoConfiguration>
 */
class SsoConfigurationFactory extends Factory
{
    protected $model = SsoConfiguration::class;

    public function definition(): array
    {
        return [
            'organization_slug' => $this->faker->unique()->slug(2),
            'tenant_id' => null,
            'name' => $this->faker->company(),
            'is_active' => true,
            'sp_entity_id' => 'https://laveille.ai/sso/saml/metadata',
            'idp_entity_id' => 'https://idp.example.com/entity',
            'idp_sso_url' => 'https://idp.example.com/sso',
            'idp_x509_cert' => $this->fakeCertificate(),
            'attribute_mapping' => [
                'email' => 'email',
                'name' => 'name',
            ],
        ];
    }

    /**
     * Certificat X.509 auto-signé STATIQUE de test (jamais utilisé en prod).
     * Généré une fois pour les tests — voir tests/Concerns/SamlTestFixtures.
     */
    private function fakeCertificate(): string
    {
        return \Modules\Sso\Tests\Concerns\SamlTestFixtures::certificatePem();
    }
}
