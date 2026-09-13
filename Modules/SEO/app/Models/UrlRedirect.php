<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\SEO\Database\Factories\UrlRedirectFactory;
use Modules\Tenancy\Traits\BelongsToTenant;

class UrlRedirect extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'url_redirects';

    protected $fillable = [
        'from_url',
        'to_url',
        'status_code',
        'is_active',
        'note',
        'tenant_id',
    ];

    protected $attributes = [
        'hits' => 0,
        'is_active' => true,
        'status_code' => 301,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status_code' => 'integer',
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function recordHit(): void
    {
        $this->increment('hits', 1, ['last_hit_at' => now()]);
    }

    /**
     * Find an active redirect for the given URL path.
     * Tries exact match first, then wildcard patterns (* → any segment).
     */
    public static function findRedirect(string $url): ?self
    {
        // Exact match
        $redirect = static::active()->where('from_url', $url)->first();

        if ($redirect) {
            return $redirect;
        }

        // Wildcard match: load patterns containing *
        $wildcards = static::active()->where('from_url', 'like', '%*%')->get();

        foreach ($wildcards as $candidate) {
            if (Str::is($candidate->from_url, $url)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Résout une redirection active pour un chemin donné (avec mise en cache d'une heure)
     * et, si elle est trouvée, enregistre la visite avant de la retourner.
     *
     * Cette logique vit ici, dans le modèle du module SEO, et non dans le câblage de
     * bootstrap/app.php : bootstrap est un fichier de CÂBLAGE applicatif, alors que
     * résoudre une redirection est une règle métier du module SEO. L'y ranger la rend
     * testable directement, sans passer par une requête HTTP complète, et garde le module
     * cohérent avec lui-même. Ce n'est PAS un argument de non-duplication : après ce
     * changement, le gestionnaire d'exception 404 est le seul appelant restant.
     *
     * COMPORTEMENT DU CACHE, volontairement contre-intuitif : ne pas le « corriger ».
     * Cache::remember ne distingue pas une clé absente d'une valeur null déjà mise en
     * cache, donc l'ABSENCE de redirection n'est jamais mise en cache et chaque 404 sans
     * redirection refait la recherche en base. C'est un coût, mais c'est AUSSI ce qui fait
     * qu'une redirection tout juste créée prend effet immédiatement, au lieu d'attendre
     * jusqu'à une heure. Ne pas mettre l'absence en cache tant qu'il n'existe pas
     * d'invalidation à la création, à la modification et à la suppression d'une
     * redirection (ticket #2521, qui porte la condition exacte).
     */
    public static function resolveForPath(string $path): ?self
    {
        $redirect = Cache::remember(
            "url_redirect:{$path}",
            3600,
            fn () => self::findRedirect($path),
        );

        if ($redirect) {
            $redirect->recordHit();
            Cache::forget("url_redirect:{$path}");

            return $redirect;
        }

        return null;
    }

    protected static function newFactory(): UrlRedirectFactory
    {
        return UrlRedirectFactory::new();
    }
}
