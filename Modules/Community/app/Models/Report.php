<?php

declare(strict_types=1);

namespace Modules\Community\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    use HasFactory;
    use \Modules\Core\Traits\HasModerationStatus;

    protected $table = 'reports';

    protected $fillable = [
        'reportable_type',
        'reportable_id',
        'user_id',
        'reason',
        'details',
        'status',
        'reviewed_at',
        'resolution_notes',
        'handled_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Signalements en attente depuis plus de 48h (retard sur l'engagement de traitement). */
    public function scopeOverdue($query)
    {
        return $query->pending()->where('created_at', '<', now()->subHours(48));
    }

    public function markResolved(int $reviewerId, string $status, ?string $notes = null): void
    {
        $this->update([
            'status' => $status,
            'reviewed_at' => now(),
            'resolution_notes' => $notes,
            'handled_by' => $reviewerId,
        ]);
    }
}
