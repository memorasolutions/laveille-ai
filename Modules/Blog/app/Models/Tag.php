<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Tenancy\Traits\BelongsToTenant;

class Tag extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'tenant_id',
    ];

    protected $attributes = [
        'color' => '#6366f1',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag): void {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
