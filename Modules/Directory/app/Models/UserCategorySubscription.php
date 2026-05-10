<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCategorySubscription extends Model
{
    protected $table = 'user_category_subscriptions';

    protected $fillable = [
        'user_id',
        'directory_category_id',
        'frequency',
        'last_notified_at',
    ];

    protected $casts = [
        'last_notified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'directory_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('frequency', ['weekly', 'daily']);
    }
}
