<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait Pest : si le module Academy est désactivé dans modules_statuses.json,
 * tous les tests du fichier sont marqués SKIPPED (suite verte).
 * Quand le module est activé, les tests s'exécutent normalement.
 *
 * Usage dans un fichier Pest (après uses()) :
 *   uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);
 *
 * Note : Pest exécute setUpAcademySkipCheck() via le hook beforeEach()
 * configuré dans chaque fichier de test Academy (voir la ligne beforeEach()
 * en tête de chaque fichier AcademyM*.php).
 *
 * Ce trait ne redéfinit PAS setUp() pour éviter la collision avec
 * Pest\Concerns\Testable::setUp.
 */

declare(strict_types=1);

namespace Modules\Academy\Tests\Concerns;

trait SkipsWhenAcademyDisabled
{
    /**
     * Vérifie si le module Academy est activé.
     * À appeler dans un beforeEach() Pest au lieu de setUp() pour éviter
     * les collisions de traits avec Pest\Concerns\Testable.
     *
     * @return bool true si le module est activé
     */
    public function isAcademyEnabled(): bool
    {
        $statusesFile = base_path('modules_statuses.json');

        if (! file_exists($statusesFile)) {
            return false;
        }

        $statuses = json_decode((string) file_get_contents($statusesFile), true) ?? [];

        return ($statuses['Academy'] ?? false) === true;
    }

    /**
     * Passe le test si le module est désactivé.
     * Appeler dans beforeEach() des fichiers de test.
     */
    public function skipIfAcademyDisabled(): void
    {
        if (! $this->isAcademyEnabled()) {
            $this->markTestSkipped('Academy module disabled — tests skipped.');
        }
    }
}
