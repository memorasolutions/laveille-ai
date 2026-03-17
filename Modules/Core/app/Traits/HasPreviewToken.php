<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Support\Str;

trait HasPreviewToken
{
    public static function bootHasPreviewToken(): void
    {
        static::creating(function ($model): void {
            $model->preview_token = Str::random(64);
        });
    }

    public function generatePreviewToken(): string
    {
        $token = Str::random(64);
        $this->preview_token = $token;
        $this->save();

        return $token;
    }

    public function previewUrl(): string
    {
        if (! $this->preview_token) {
            return '';
        }

        return route('preview.show', $this->preview_token);
    }

    public function revokePreviewToken(): void
    {
        $this->preview_token = null;
        $this->save();
    }
}
