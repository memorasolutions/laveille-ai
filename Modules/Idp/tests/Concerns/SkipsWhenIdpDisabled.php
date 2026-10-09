<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Idp\Tests\Concerns;

trait SkipsWhenIdpDisabled
{
    public function skipIfIdpModuleDisabled(): void
    {
        if (! \Nwidart\Modules\Facades\Module::find('Idp')?->isEnabled()) {
            $this->markTestSkipped('Module Idp désactivé dans ce déploiement.');
        }
    }
}
