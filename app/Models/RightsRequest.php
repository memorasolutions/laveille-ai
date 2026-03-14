<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RightsRequest extends Model
{
    use HasFactory;

    protected $table = 'rights_requests';

    protected $fillable = [
        'reference',
        'name',
        'email',
        'request_type',
        'description',
        'file_path',
        'status',
        'jurisdiction',
        'deadline_at',
        'responded_at',
        'admin_notes',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('deadline_at', '<', now());
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->reference)) {
                $model->reference = 'DR-'.date('Y').'-'.str_pad((string) (static::count() + 1), 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && $this->deadline_at->isPast();
    }

    public function markCompleted(): bool
    {
        $this->status = 'completed';
        $this->responded_at = now();

        return $this->save();
    }
}
