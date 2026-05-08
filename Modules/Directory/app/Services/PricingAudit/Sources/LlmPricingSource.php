<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Driver : LLM analysis (qwen3-max via openrouter-free) sur le HTML brut
 * fetché. Poids 1 (cross-source, web-aware mais pas first-party render).
 *
 * Comme PpSearch, nécessite orchestration CLI (multi-ai-mcp côté Claude Code).
 * En PHP runtime utilise les résultats pré-calculés via setPrecomputed().
 */

namespace Modules\Directory\Services\PricingAudit\Sources;

use Modules\Directory\Models\Tool;
use Modules\Directory\Services\PricingAudit\PricingSourceResult;

class LlmPricingSource extends AbstractPricingSource
{
    public function name(): string
    {
        return 'llm';
    }

    public function weight(): int
    {
        return 1;
    }

    protected function doFetch(Tool $tool): PricingSourceResult
    {
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
                confidence: (int) ($precomputed['confidence'] ?? 65),
                rawPayload: $precomputed['raw'] ?? null,
            );
        }

        return new PricingSourceResult(
            sourceName: $this->name(),
            weight: $this->weight(),
            error: 'no precomputed llm result (requires CLI orchestration)',
        );
    }

    /** @var array<int, array> */
    private array $precomputed = [];

    /**
     * @param  array<int, array>  $byToolId
     */
    public function setPrecomputed(array $byToolId): void
    {
        $this->precomputed = $byToolId;
    }
}
