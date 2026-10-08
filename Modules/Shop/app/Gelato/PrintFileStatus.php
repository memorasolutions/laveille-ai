<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

/** Machine à états du fichier d'impression : DRAFT -> PREPARED -> MOCKUP_READY -> APPROVED -> SELLABLE. */
enum PrintFileStatus: string
{
    case Draft = 'DRAFT';
    case Prepared = 'PREPARED';
    case MockupReady = 'MOCKUP_READY';
    case Approved = 'APPROVED';
    case Sellable = 'SELLABLE';

    /** États dans lesquels le fichier est commandable (sous réserve du hash). */
    public function isOrderable(): bool
    {
        return in_array($this, [self::Approved, self::Sellable], true);
    }
}
