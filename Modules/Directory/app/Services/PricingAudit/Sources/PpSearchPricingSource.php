<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Driver : délègue à perplexity-pro-playwright via une commande artisan
 * pp-search-pricing (orchestration depuis CLI Claude). Poids 1 (web freshness,
 * cross-source). En contexte test/CI : configurable mock.
 */

namespace Modules\Directory\Services\PricingAudit\Sources;

use Modules\Directory\Models\Tool;
use Modules\Directory\Services\PricingAudit\PricingSourceResult;

class PpSearchPricingSource extends AbstractPricingSource
{
    public function name(): string
    {
        return 'ppsearch';
    }

    public function weight(): int
    {
        return 1;
    }

    protected function doFetch(Tool $tool): PricingSourceResult
    {
        // pp_search nécessite l'orchestration MCP côté Claude Code (pas appelable depuis PHP runtime).
        // Cette source devient utilisable via la commande artisan tools:audit-pricing-tiered qui
        // injecte les résultats pré-calculés via setPrecomputed(). En usage standalone, retourne 'pending'.
        $precomputed = $this->precomputed[$tool->id] ?? null;
        if ($precomputed) {
            return new PricingSourceResult(
                sourceName: $this->name(),
                weight: $this->weight(),
                realPricing: $this->normalizePricing($precomputed['real_pricing'] ?? null),
                hasEducationDiscount: $precomputed['has_education_discount'] ?? null,
                educationUrl: $precomputed['education_url'] ?? null,
                evidenceQuote: $precomputed['evidence_quote'] ?? null,
                evidenceUrl: $precomputed['evidence_url'] ?? $tool->url,
                confidence: (int) ($precomputed['confidence'] ?? 70),
                rawPayload: $precomputed['raw'] ?? null,
            );
        }

        return new PricingSourceResult(
            sourceName: $this->name(),
            weight: $this->weight(),
            error: 'no precomputed pp_search result (requires CLI orchestration)',
        );
    }

    /** @var array<int, array> */
    private array $precomputed = [];

    /**
     * Injecte les résultats pp_search pré-calculés depuis l'orchestration CLI.
     *
     * @param  array<int, array>  $byToolId
     */
    public function setPrecomputed(array $byToolId): void
    {
        $this->precomputed = $byToolId;
    }
}
