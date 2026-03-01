<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Widget\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Widget extends Model
{
    /** @var list<string> */
    public const ZONES = ['sidebar', 'footer', 'after_content'];

    /** @var list<string> */
    public const TYPES = ['html', 'recent_posts', 'newsletter', 'social_links', 'cta_button', 'custom_text'];

    /** @var array<string, string> */
    public const ZONE_LABELS = [
        'sidebar' => 'Barre latérale',
        'footer' => 'Pied de page',
        'after_content' => 'Après le contenu',
    ];

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        'html' => 'HTML personnalisé',
        'recent_posts' => 'Articles récents',
        'newsletter' => 'Newsletter',
        'social_links' => 'Liens sociaux',
        'cta_button' => 'Bouton d\'action',
        'custom_text' => 'Texte personnalisé',
    ];

    /** @var list<string> */
    protected $fillable = [
        'zone', 'type', 'title', 'content', 'settings', 'is_active', 'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn (Widget $w) => Cache::forget("widgets_{$w->zone}"));
        static::deleted(fn (Widget $w) => Cache::forget("widgets_{$w->zone}"));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForZone(Builder $query, string $zone): Builder
    {
        return $query->where('zone', $zone);
    }
}
