<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Service : détermine le tier popularité + SLA de fraîcheur d'un outil,
 * retourne la liste des outils dûs pour re-audit selon le tier.
 *
 * Tiers (par rank clicks_count desc) :
 *  - top100   -> SLA 30j
 *  - mid      -> SLA 60j
 *  - longtail -> SLA 90j
 */

namespace Modules\Directory\Services\PricingAudit;

use Illuminate\Support\Collection;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolPricingAudit;

class PricingAuditScheduler
{
    public const SLA_TOP100 = 30;
    public const SLA_MID = 60;
    public const SLA_LONGTAIL = 90;
    public const TIER_TOP100_LIMIT = 100;
    public const TIER_MID_LIMIT = 200;

    /**
     * Tier d'un outil basé sur son rank clicks_count.
     */
    public function tierFor(Tool $tool): string
    {
        // Cache clicks_count rank en mémoire request (si appelé multiple fois)
        static $rankCache = [];

        if (! isset($rankCache[$tool->id])) {
            $rank = Tool::published()
                ->where('clicks_count', '>', $tool->clicks_count ?? 0)
                ->count() + 1;
            $rankCache[$tool->id] = $rank;
        }

        $rank = $rankCache[$tool->id];

        return match (true) {
            $rank <= self::TIER_TOP100_LIMIT => 'top100',
            $rank <= self::TIER_MID_LIMIT => 'mid',
            default => 'longtail',
        };
    }

    public function slaForTier(string $tier): int
    {
        return match ($tier) {
            'top100' => self::SLA_TOP100,
            'mid' => self::SLA_MID,
            default => self::SLA_LONGTAIL,
        };
    }

    /**
     * Liste les outils dont l'audit le plus récent est plus ancien que leur SLA tier.
     * Inclut les outils JAMAIS audités.
     *
     * @return Collection<Tool>
     */
    public function nextDueTools(int $limit = 10): Collection
    {
        // Sub-query : dernier audit par tool
        $lastAuditDates = ToolPricingAudit::query()
            ->selectRaw('directory_tool_id, MAX(audited_at) as last_audited_at')
            ->groupBy('directory_tool_id')
            ->pluck('last_audited_at', 'directory_tool_id');

        // Pull tous les outils published triés par clicks_count
        $allTools = Tool::published()
            ->orderByDesc('clicks_count')
            ->get(['id', 'name', 'url', 'clicks_count', 'pricing']);

        // Filtre par SLA dû selon tier
        $due = $allTools->filter(function (Tool $tool) use ($lastAuditDates) {
            $tier = $this->tierFor($tool);
            $sla = $this->slaForTier($tier);
            $lastAt = $lastAuditDates->get($tool->id);
            if (! $lastAt) {
                return true; // Jamais audité = dû
            }

            return now()->diffInDays($lastAt) >= $sla;
        });

        return $due->take($limit)->values();
    }

    /**
     * Détecte si l'audit le plus récent diffère du pricing courant.
     * Utile pour notifier l'admin de changements.
     */
    public function detectChange(Tool $tool, ToolPricingAudit $audit): ?array
    {
        if (! $audit->real_pricing || $audit->real_pricing === $tool->pricing) {
            return null;
        }

        return [
            'tool_id' => $tool->id,
            'name' => $tool->name,
            'old' => $tool->pricing,
            'new' => $audit->real_pricing,
            'confidence' => $audit->confidence,
            'audited_at' => $audit->audited_at->toIso8601String(),
        ];
    }
}
