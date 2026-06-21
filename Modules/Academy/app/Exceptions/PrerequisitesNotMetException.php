<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Levée quand un utilisateur tente de s'inscrire à un cours dont il n'a pas
 * complété tous les prérequis (Phase C / C4). Bloque l'inscription côté serveur.
 */

declare(strict_types=1);

namespace Modules\Academy\Exceptions;

class PrerequisitesNotMetException extends \RuntimeException
{
}
