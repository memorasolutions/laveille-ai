<?php

declare(strict_types=1);

namespace Modules\Privacy\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    protected $fillable = ['slug', 'title', 'content', 'is_active', 'updated_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->active()->first();
    }

    public function updatedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
