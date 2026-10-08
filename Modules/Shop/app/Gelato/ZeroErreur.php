<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

/** Point unique de lecture du drapeau shop.gelato_zero_erreur (DRY). */
final class ZeroErreur
{
    public static function enabled(): bool
    {
        return (bool) config('shop.gelato_zero_erreur', false);
    }
}
