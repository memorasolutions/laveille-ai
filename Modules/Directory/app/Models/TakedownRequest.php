<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TakedownRequest extends Model
{
    protected $table = 'directory_takedown_requests';

    protected $fillable = [
        'directory_tool_id',
        'target_url',
        'requester_name',
        'requester_email',
        'requester_organization',
        'requester_role',
        'right_type',
        'right_details',
        'description',
        'declaration_accepted',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'declaration_accepted' => 'boolean',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'directory_tool_id');
    }
}
