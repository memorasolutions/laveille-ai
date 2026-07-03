<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Tests\Concerns;

/**
 * Garde-fou : si le module Sso est désactivé dans modules_statuses.json,
 * les tests qui l'utilisent sont SKIPPED (jamais en échec), même pattern que
 * Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled.
 */
trait SkipsWhenSsoDisabled
{
    public function skipIfSsoModuleDisabled(): void
    {
        if (! \Nwidart\Modules\Facades\Module::find('Sso')?->isEnabled()) {
            $this->markTestSkipped('Module Sso désactivé dans ce déploiement.');
        }
    }
}
