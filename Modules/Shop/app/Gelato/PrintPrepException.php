<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

use RuntimeException;

/** Refus ou panne du moteur print-prep. Le code reprend ceux du contrat (NON_CONFORME, ZONE_INCONNUE...). */
class PrintPrepException extends RuntimeException
{
    public const NON_CONFORME = 'NON_CONFORME';
    public const ZONE_INCONNUE = 'ZONE_INCONNUE';
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const NOT_CONFIGURED = 'NOT_CONFIGURED';
    public const UNREACHABLE = 'UNREACHABLE';
    public const INVALID_RESPONSE = 'INVALID_RESPONSE';

    public function __construct(
        public readonly string $errorCode,
        string $message = '',
        public readonly array $details = [],
    ) {
        parent::__construct($message !== '' ? $message : $errorCode);
    }

    /** Refus métier (à remonter à l'admin) par opposition à une panne technique. */
    public function isRefusal(): bool
    {
        return in_array($this->errorCode, [self::NON_CONFORME, self::ZONE_INCONNUE], true);
    }
}
