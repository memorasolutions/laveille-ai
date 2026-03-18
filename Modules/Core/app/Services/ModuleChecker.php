<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Services;

use Nwidart\Modules\Facades\Module;

final class ModuleChecker
{
    /** @var array<string, bool> */
    private static array $cache = [];

    public static function isAvailable(string $moduleName): bool
    {
        if (! class_exists(Module::class)) {
            return false;
        }

        try {
            return Module::has($moduleName) && Module::isEnabled($moduleName);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function classExists(string $fqcn): bool
    {
        return self::$cache[$fqcn] ??= class_exists($fqcn);
    }

    public static function resolve(string $fqcn): ?object
    {
        return self::classExists($fqcn) ? app($fqcn) : null;
    }

    public static function when(string $moduleName, callable $callback): mixed
    {
        return self::isAvailable($moduleName) ? $callback() : null;
    }
}
