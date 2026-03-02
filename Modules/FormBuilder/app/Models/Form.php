<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FormBuilder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\FormBuilder\Database\Factories\FormFactory;
use Modules\Tenancy\Traits\BelongsToTenant;

class Form extends Model
{
    use BelongsToTenant, HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'settings',
        'is_published',
        'tenant_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'settings' => 'array',
        'is_published' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Form $form): void {
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->title);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    protected static function newFactory(): FormFactory
    {
        return FormFactory::new();
    }
}
