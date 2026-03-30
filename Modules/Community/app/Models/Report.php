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
    ];

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
