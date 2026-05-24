<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AuthorAffiliateLink extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'author_profile_id',
        'slug',
        'destination_url',
        'label',
    ];

    protected $casts = [
        'clicks_count' => 'integer',
    ];

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }
}
