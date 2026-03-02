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
use Modules\Notifications\Models\EmailTemplate;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'type',
        'config',
        'position',
        'template_id',
    ];

    protected $casts = [
        'config' => 'array',
        'position' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(EmailWorkflow::class, 'workflow_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowStepLog::class, 'step_id');
    }

    protected static function newFactory(): \Modules\Newsletter\Database\Factories\WorkflowStepFactory
    {
        return \Modules\Newsletter\Database\Factories\WorkflowStepFactory::new();
    }
}
