<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Read-only settings contract, used by Core middleware
 * to avoid direct dependency on the Settings module.
 */
interface SettingsReaderInterface
{
    public function get(string $key, mixed $default = null): mixed;
}
