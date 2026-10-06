<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

use RuntimeException;

/**
 * Échec de soumission d'une commande Gelato.
 * $definitive = true  : rejet CONFIRMÉ avant création (4xx de validation) -> aucune commande n'existe, la clé peut être libérée.
 * $definitive = false : issue AMBIGUË (timeout, 5xx, réponse perdue, 2xx sans id) -> la commande peut exister, ne JAMAIS libérer la clé.
 */
class GelatoSubmitException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $definitive, public readonly ?int $httpStatus = null)
    {
        parent::__construct($message);
    }
}
