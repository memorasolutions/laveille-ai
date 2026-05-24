<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorActivityLog extends Model
{
    use HasFactory;

    protected $table = 'author_activity_logs';

    protected $fillable = [
        'author_profile_id',
        'event_type',
        'event_meta',
        'recorded_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'event_meta' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }
}
