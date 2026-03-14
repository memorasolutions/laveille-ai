<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenancy\Traits\BelongsToTenant;

class ContactMessage extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'email', 'subject', 'message', 'status', 'ip_address', 'read_at', 'tenant_id'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function scopeUnread(Builder $query): void
    {
        $query->where('status', 'new');
    }

    public function scopeRead(Builder $query): void
    {
        $query->where('status', 'read');
    }

    public function markAsRead(): void
    {
        if ($this->status === 'new') {
            $this->update(['status' => 'read', 'read_at' => now()]);
        }
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }
}
