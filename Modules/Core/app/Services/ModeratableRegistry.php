<?php

declare(strict_types=1);

namespace Modules\Core\Services;

class ModeratableRegistry
{
    protected static array $types = [];

    public static function register(string $key, string $modelClass): void
    {
        self::$types[$key] = $modelClass;
    }

    public static function resolve(string $key): ?string
    {
        return self::$types[$key] ?? null;
    }

    public static function all(): array
    {
        return self::$types;
    }

    public static function has(string $key): bool
    {
        return isset(self::$types[$key]);
    }
}
