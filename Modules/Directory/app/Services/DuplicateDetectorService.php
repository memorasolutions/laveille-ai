<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Support\Collection;
use Modules\Core\Services\MetaScraperService;
use Modules\Directory\Models\Tool;

class DuplicateDetectorService
{
    /**
     * Detecte les doublons potentiels bases sur l'URL et le nom.
     * Retourne une Collection de ['tool' => Tool, 'confidence' => 'certain'|'probable'].
     */
    public static function findDuplicates(string $url, string $name): Collection
    {
        $matches = collect();
        $seenIds = [];

        $addMatch = function (Tool $tool, string $confidence) use ($matches, &$seenIds) {
            if (isset($seenIds[$tool->id]) && $seenIds[$tool->id] === 'certain') {
                return;
            }
            $seenIds[$tool->id] = $confidence;
            $matches->push(['tool' => $tool, 'confidence' => $confidence]);
        };

        // Etape 1 : root domain match (certain)
        $domain = MetaScraperService::extractRootDomain($url);
        if ($domain) {
            Tool::published()
                ->where('url', 'LIKE', "%{$domain}%")
                ->get()
                ->each(fn (Tool $tool) => $addMatch($tool, 'certain'));
        }

        // Etape 2 : fuzzy name match >= 85% (probable)
        $normalizedName = mb_strtolower(trim($name));
        Tool::published()->get()->each(function (Tool $tool) use ($normalizedName, $addMatch) {
            similar_text($normalizedName, mb_strtolower($tool->name), $percent);
            if ($percent >= 85) {
                $addMatch($tool, 'probable');
            }
        });

        // Etape 3 : redirect chain → re-check domain (certain)
        try {
            $finalUrl = MetaScraperService::resolveRedirectChain($url);
            $finalDomain = MetaScraperService::extractRootDomain($finalUrl);
            if ($finalDomain && $finalDomain !== $domain) {
                Tool::published()
                    ->where('url', 'LIKE', "%{$finalDomain}%")
                    ->get()
                    ->each(fn (Tool $tool) => $addMatch($tool, 'certain'));
            }
        } catch (\Exception) {
            // Redirect chain failed, skip
        }

        return $matches->unique(fn ($m) => $m['tool']->id)->values();
    }
}
