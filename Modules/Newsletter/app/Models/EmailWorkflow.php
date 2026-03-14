<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenancy\Traits\BelongsToTenant;

class EmailWorkflow extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'email_workflows';

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'status',
        'tenant_id',
        'created_by',
    ];

    protected $casts = [
        'trigger_config' => 'array',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'workflow_id')->orderBy('position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(WorkflowEnrollment::class, 'workflow_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    protected static function newFactory(): \Modules\Newsletter\Database\Factories\EmailWorkflowFactory
    {
        return \Modules\Newsletter\Database\Factories\EmailWorkflowFactory::new();
    }
}
