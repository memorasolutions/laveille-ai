<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Testimonials\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $table = 'testimonials';

    protected $fillable = [
        'author_name',
        'author_title',
        'author_avatar',
        'content',
        'rating',
        'order',
        'is_approved',
    ];

    protected $attributes = [
        'is_approved' => false,
        'rating' => 5,
        'order' => 0,
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'rating' => 'integer',
        'order' => 'integer',
    ];

    public function scopeApproved(Builder $query): void
    {
        $query->where('is_approved', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order', 'asc');
    }

    public function safeContent(): string
    {
        return clean($this->content);
    }
}
