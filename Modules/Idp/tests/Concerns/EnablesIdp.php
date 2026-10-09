<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

namespace Modules\Idp\Tests\Concerns;

/**
 * Active l'IdP AVANT la création de l'application : le provider de Passport n'est enregistré
 * dans register() que si IDP_ENABLED est vrai, donc le drapeau doit exister dès le boot.
 */
trait EnablesIdp
{
    protected function refreshApplication(): void
    {
        putenv('IDP_ENABLED=true');
        $_ENV['IDP_ENABLED'] = $_SERVER['IDP_ENABLED'] = 'true';

        parent::refreshApplication();
    }

    /** Retire le drapeau de l'environnement (à appeler dans afterEach). */
    public static function disableIdpEnv(): void
    {
        putenv('IDP_ENABLED');
        unset($_ENV['IDP_ENABLED'], $_SERVER['IDP_ENABLED']);
    }
}
