<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\AI\Database\Factories\SlaPolicyFactory;
use Modules\AI\Enums\TicketPriority;

class SlaPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'priority',
        'first_response_hours',
        'resolution_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => TicketPriority::class,
            'first_response_hours' => 'integer',
            'resolution_hours' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPriority($query, TicketPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function calculateDueAt(Carbon $createdAt): Carbon
    {
        return $createdAt->copy()->addHours($this->resolution_hours);
    }

    protected static function newFactory(): SlaPolicyFactory
    {
        return SlaPolicyFactory::new();
    }
}
