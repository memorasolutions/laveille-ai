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

class AnalyticsCollectionRun extends Model
{
    protected $table = 'analytics_collection_runs';

    protected $fillable = [
        'source',
        'collected_for',
        'ran_at',
        'status',
        'rows_upserted',
        'message',
    ];

    protected $casts = [
        'ran_at' => 'datetime',
        'rows_upserted' => 'integer',
    ];

    /**
     * ACTION : même garde-fou que Modules\Analytics\Models\Ga4Daily::date() - voir son
     * docblock pour le raisonnement complet. 'ran_at' garde le cast natif 'datetime'
     * ci-dessus (aucune recherche exacte par égalité de chaîne ne porte sur ce champ) ;
     * seul 'collected_for', clé de la contrainte unique(source, collected_for) et des
     * updateOrCreate() qui la ciblent, a besoin de ce mutateur dédié.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: garde-fou "doit fonctionner sur SQLite ET MySQL" du brief (#2897).
     */
    protected function collectedFor(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value !== null ? Carbon::parse($value) : null,
            set: fn ($value) => $value !== null ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }
}
