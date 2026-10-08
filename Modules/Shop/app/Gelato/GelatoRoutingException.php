<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

use RuntimeException;

/** Un article ne peut pas être routé vers Gelato (ex. produit catalogue sans storeProductVariantId). */
class GelatoRoutingException extends RuntimeException {}
