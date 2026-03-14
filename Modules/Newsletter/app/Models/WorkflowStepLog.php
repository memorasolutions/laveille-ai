<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'step_id',
        'status',
        'metadata',
        'executed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'executed_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(WorkflowEnrollment::class, 'enrollment_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'step_id');
    }

    protected static function newFactory(): \Modules\Newsletter\Database\Factories\WorkflowStepLogFactory
    {
        return \Modules\Newsletter\Database\Factories\WorkflowStepLogFactory::new();
    }
}
