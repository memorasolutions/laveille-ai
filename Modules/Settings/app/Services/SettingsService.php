<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Settings\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Settings\Models\Setting;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): Setting
    {
        return Setting::set($key, $value, $type, $group);
    }

    public function has(string $key): bool
    {
        return Cache::remember("setting.exists.{$key}", 3600, function () use ($key) {
            return Setting::where('key', $key)->exists();
        });
    }

    public function forget(string $key): bool
    {
        $deleted = Setting::where('key', $key)->delete();
        Cache::forget("setting.{$key}");
        Cache::forget("setting.exists.{$key}");

        return $deleted > 0;
    }

    public function all(?string $group = null): array
    {
        $query = Setting::query();

        if ($group) {
            $query->where('group', $group);
        }

        return $query->pluck('value', 'key')->toArray();
    }

    public function clearCache(): void
    {
        $keys = Setting::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("setting.{$key}");
            Cache::forget("setting.exists.{$key}");
        }
    }
}
