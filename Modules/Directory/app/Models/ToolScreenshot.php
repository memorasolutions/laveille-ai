<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolScreenshot extends Model
{
    protected $table = 'directory_screenshots';

    protected $fillable = [
        'directory_tool_id', 'user_id', 'image_path', 'caption',
        'is_approved', 'votes_count', 'sort_order',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'directory_tool_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
