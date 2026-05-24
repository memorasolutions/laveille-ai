<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AuthorPushSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'author_profile_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }
}
