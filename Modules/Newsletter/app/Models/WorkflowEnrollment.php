<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'subscriber_id',
        'current_step_id',
        'status',
        'enrolled_at',
        'completed_at',
        'next_run_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(EmailWorkflow::class, 'workflow_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class, 'subscriber_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowStepLog::class, 'enrollment_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    protected static function newFactory(): \Modules\Newsletter\Database\Factories\WorkflowEnrollmentFactory
    {
        return \Modules\Newsletter\Database\Factories\WorkflowEnrollmentFactory::new();
    }
}
