<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

/** Le verrou de synchronisation Gelato a expiré ou a été repris : plus aucune écriture n'est permise. */
class SyncLockLostException extends \RuntimeException {}
