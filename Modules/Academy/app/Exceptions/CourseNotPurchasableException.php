<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Exceptions;

use RuntimeException;

/**
 * Levée quand un cours ne peut pas être acheté (gratuit, déjà inscrit, prix absent).
 */
class CourseNotPurchasableException extends RuntimeException
{
    //
}
