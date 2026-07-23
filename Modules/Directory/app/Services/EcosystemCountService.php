<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Directory\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Directory\Models\Tool;

class EcosystemCountService
{
    public const CACHE_KEY = 'directory.ecosystem_counts';

    /**
     * Retourne ['openai' => 6, 'google' => 4, ...] — nombre d'outils PUBLIÉS (status =
     * 'published', cf. Tool::scopePublished()) par ecosystem_tag. UNE SEULE requête agrégée
     * GROUP BY (jamais de withCount()/N+1 par carte sur les 433+ outils de l'annuaire),
     * résultat mis en cache indéfiniment et invalidé par ToolObserver sur saved()/deleted().
     */
    public function counts(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Tool::published()
                ->whereNotNull('ecosystem_tag')
                ->selectRaw('ecosystem_tag, count(*) as cnt')
                ->groupBy('ecosystem_tag')
                ->pluck('cnt', 'ecosystem_tag')
                ->all();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
