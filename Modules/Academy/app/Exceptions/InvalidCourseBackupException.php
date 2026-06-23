<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F15 - Levée quand un fichier de sauvegarde de cours est invalide, corrompu ou
 * dans un format non pris en charge. Le message est destiné à l'affichage (FR clair) ;
 * l'appelant (composant d'import) l'attrape et le présente sans planter (jamais de 500).
 */

declare(strict_types=1);

namespace Modules\Academy\Exceptions;

use RuntimeException;

class InvalidCourseBackupException extends RuntimeException
{
}
