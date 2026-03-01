<?php

declare(strict_types=1);

namespace Modules\FormBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class FormSubmission extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'form_id',
        'data',
        'status',
        'ip_address',
        'read_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function markAsRead(): bool
    {
        return $this->update(['read_at' => Carbon::now()]);
    }

    public function isNew(): bool
    {
        return is_null($this->read_at);
    }
}
