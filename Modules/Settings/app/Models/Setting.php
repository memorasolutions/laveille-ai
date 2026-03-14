<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Settings\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use Spatie\ResponseCache\Facades\ResponseCache;

class Setting extends Model
{
    use HasFactory, Searchable;

    protected static function booted(): void
    {
        static::saved(fn () => ResponseCache::clear());
        static::deleted(fn () => ResponseCache::clear());
    }

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    protected $fillable = ['group', 'key', 'value', 'type', 'description', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'key' => $this->key,
            'value' => is_string($this->value) ? $this->value : null,
            'description' => $this->description,
            'group' => $this->group,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return ! in_array($this->group, ['security', 'secrets']);
    }

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json' => json_decode($this->value ?? '{}', true),
            default => $this->value,
        };
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting !== null ? $setting->typed_value : $default;
        });
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
                'group' => $group,
            ]
        );

        Cache::forget("setting.{$key}");

        return $setting;
    }
}
