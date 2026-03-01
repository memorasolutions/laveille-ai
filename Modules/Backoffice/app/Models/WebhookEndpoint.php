<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Webhooks\Models\WebhookCall;

class WebhookEndpoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'url', 'secret', 'events', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'events' => 'array',
        ];
    }

    public function calls(): HasMany
    {
        return $this->hasMany(WebhookCall::class);
    }

    protected static function newFactory(): \Modules\Backoffice\Database\Factories\WebhookEndpointFactory
    {
        return \Modules\Backoffice\Database\Factories\WebhookEndpointFactory::new();
    }
}
