<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class GscDaily extends Model
{
    protected $table = 'analytics_gsc_daily';

    protected $fillable = [
        'date',
        'url',
        'clicks',
        'impressions',
        'ctr',
        'position',
        'content_id',
        'content_type',
    ];

    protected $casts = [
        'clicks' => 'integer',
        'impressions' => 'integer',
        'ctr' => 'decimal:4',
        'position' => 'decimal:2',
        'content_id' => 'integer',
    ];

    /**
     * ACTION : même garde-fou que Modules\Analytics\Models\Ga4Daily::date() - voir son
     * docblock pour le raisonnement complet (fromDateTime() d'Eloquent stocke toujours
     * un horodatage complet, même pour un cast 'date', ce qui casse updateOrCreate() sur
     * SQLite).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: garde-fou "doit fonctionner sur SQLite ET MySQL" du brief (#2897).
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value !== null ? Carbon::parse($value) : null,
            set: fn ($value) => $value !== null ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }
}
