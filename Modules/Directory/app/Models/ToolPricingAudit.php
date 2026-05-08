<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolPricingAudit extends Model
{
    protected $table = 'tool_pricing_audits';

    protected $fillable = [
        'directory_tool_id',
        'audited_at',
        'real_pricing',
        'has_education_discount',
        'education_url',
        'confidence',
        'weighted_score',
        'sources',
        'evidence',
        'screenshot_path',
        'review_status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'audited_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'has_education_discount' => 'boolean',
        'sources' => 'array',
        'evidence' => 'array',
        'confidence' => 'integer',
        'weighted_score' => 'integer',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'directory_tool_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isFresh(int $maxDays = 30): bool
    {
        return $this->audited_at && $this->audited_at->diffInDays(now()) <= $maxDays;
    }

    public function freshnessTier(): string
    {
        if (! $this->audited_at) {
            return 'never';
        }
        $days = $this->audited_at->diffInDays(now());
        if ($days < 30) {
            return 'fresh';
        }
        if ($days < 90) {
            return 'aging';
        }

        return 'stale';
    }
}
