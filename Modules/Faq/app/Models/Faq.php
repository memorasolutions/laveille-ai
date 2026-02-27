<?php

declare(strict_types=1);

namespace Modules\Faq\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'category', 'order', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function scopeByCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order');
    }

    public function safeAnswer(): string
    {
        return clean($this->answer);
    }
}
