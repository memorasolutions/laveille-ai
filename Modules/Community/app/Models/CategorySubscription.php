<?php

declare(strict_types=1);

namespace Modules\Community\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategorySubscription extends Model
{
    protected $fillable = [
        'user_id',
        'category_tag',
        'module',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeForCategory($query, string $categoryTag)
    {
        return $query->where('category_tag', $categoryTag);
    }
}
