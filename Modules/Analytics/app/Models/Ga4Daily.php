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

class Ga4Daily extends Model
{
    protected $table = 'analytics_ga4_daily';

    protected $fillable = [
        'date',
        'url',
        'sessions',
        'active_users',
        'screen_page_views',
        'engaged_sessions',
        'avg_engagement_time_seconds',
        'conversions',
        'content_id',
        'content_type',
    ];

    protected $casts = [
        'sessions' => 'integer',
        'active_users' => 'integer',
        'screen_page_views' => 'integer',
        'engaged_sessions' => 'integer',
        'avg_engagement_time_seconds' => 'integer',
        'conversions' => 'integer',
        'content_id' => 'integer',
    ];

    /**
     * ACTION : accesseur/mutateur dédié plutôt que le cast natif 'date' - fromDateTime()
     * d'Eloquent formate TOUJOURS avec le format complet de la connexion (Y-m-d H:i:s),
     * même pour un cast 'date' : sur SQLite (aucune contrainte de type de colonne) la
     * valeur stockée porte l'heure, et where('date', '2026-09-29') - utilisé par
     * updateOrCreate()/firstOrNew() - ne la retrouve plus. Sur MySQL la vraie colonne
     * DATE tronque l'heure silencieusement, donc ce défaut ne se voit qu'en test.
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
