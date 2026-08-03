<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Driver pp_search : 2 modes coexistants (#60 S89).
 *
 * 1) Direct HTTP via OpenRouter Perplexity Sonar Pro (recommandé prod, ~$0.005/req)
 *    - Lance une recherche web avec un prompt JSON-strict.
 *    - Parse la réponse, extrait pricing/edu_discount/evidence.
 *    - Retry 1 fois si parse JSON échoue.
 *    - Active si OPENROUTER_API_KEY défini ET pp_search.driver != 'precomputed'.
 *
 * 2) Precomputed (fallback CLI orchestré depuis Claude Code MCP)
 *    - Conservé pour pipelines batch dry-run où on injecte des résultats déjà produits.
 *
 * Poids 1 (web freshness, cross-source).
 */

namespace Modules\Directory\Services\PricingAudit\Sources;

use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;
use Modules\Directory\Services\PricingAudit\PricingSourceResult;

class PpSearchPricingSource extends AbstractPricingSource
{
    /** @var array<int, array> */
    private array $precomputed = [];

    public function __construct(
        private ?OpenRouterService $openRouter = null,
    ) {
        $this->openRouter ??= app(OpenRouterService::class);
    }

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
        // 1) Mode precomputed (CLI orchestration) prioritaire si déjà injecté.
        $precomputed = $this->precomputed[$tool->id] ?? null;
        if ($precomputed) {
            return $this->resultFromPrecomputed($precomputed, $tool);
        }

        // 2) Mode direct via OpenRouter Sonar Pro.
        $apiKey = config('directory.openrouter_api_key');
        if (! $apiKey) {
            return new PricingSourceResult(
                sourceName: $this->name(),
                weight: $this->weight(),
                error: 'no precomputed and OPENROUTER_API_KEY missing',
            );
        }

        return $this->fetchViaOpenRouter($tool);
    }

    /**
     * Injecte les résultats pp_search pré-calculés depuis l'orchestration CLI.
     *
     * @param  array<int, array>  $byToolId
     */
    public function setPrecomputed(array $byToolId): void
    {
        $this->precomputed = $byToolId;
    }

    private function resultFromPrecomputed(array $row, Tool $tool): PricingSourceResult
    {
        return new PricingSourceResult(
            sourceName: $this->name(),
            weight: $this->weight(),
            realPricing: $this->normalizePricing($row['real_pricing'] ?? null),
            hasEducationDiscount: $row['has_education_discount'] ?? null,
            educationUrl: $row['education_url'] ?? null,
            evidenceQuote: $row['evidence_quote'] ?? null,
            evidenceUrl: $row['evidence_url'] ?? $tool->url,
            confidence: (int) ($row['confidence'] ?? 70),
            rawPayload: $row['raw'] ?? null,
        );
    }

    private function fetchViaOpenRouter(Tool $tool): PricingSourceResult
    {
        $prompt = $this->buildSearchPrompt($tool);

        $raw = $this->openRouter->search($prompt);
        if ($raw === '') {
            return new PricingSourceResult(
                sourceName: $this->name(),
                weight: $this->weight(),
                error: 'openrouter empty response',
            );
        }

        $parsed = $this->extractJson($raw);
        if ($parsed === null) {
            // Retry une fois avec instruction renforcée
            $retryPrompt = $prompt . "\n\nIMPORTANT: previous response was unparseable. Output ONLY raw JSON (no ```json fence, no commentary).";
            $raw2 = $this->openRouter->search($retryPrompt);
            $parsed = $this->extractJson($raw2);
            if ($parsed === null) {
                Log::warning('PpSearch driver: JSON parse failed twice', [
                    'tool_id' => $tool->id,
                    'raw_head' => substr($raw, 0, 200),
                ]);

                return new PricingSourceResult(
                    sourceName: $this->name(),
                    weight: $this->weight(),
                    error: 'json parse failed',
                    rawPayload: substr($raw, 0, 1000),
                );
            }
            $raw = $raw2;
        }

        $confidence = (int) max(0, min(100, $parsed['confidence'] ?? 70));

        return new PricingSourceResult(
            sourceName: $this->name(),
            weight: $this->weight(),
            realPricing: $this->normalizePricing($parsed['real_pricing'] ?? null),
            hasEducationDiscount: isset($parsed['has_education_discount']) ? (bool) $parsed['has_education_discount'] : null,
            educationUrl: $parsed['education_url'] ?? null,
            evidenceQuote: isset($parsed['evidence_quote']) ? mb_substr((string) $parsed['evidence_quote'], 0, 240) : null,
            evidenceUrl: $parsed['evidence_url'] ?? $tool->url,
            confidence: $confidence,
            rawPayload: substr($raw, 0, 1500),
        );
    }

    private function buildSearchPrompt(Tool $tool): string
    {
        $name = (string) $tool->name;
        $url = (string) ($tool->url ?? '');
        $hint = $url ? "(homepage: {$url})" : '';

        return <<<PROMPT
            Search the web for the **current 2026 pricing** of the AI tool "{$name}" {$hint}.
            Pay attention to free plans, free trials, paid tiers, and EDUCATIONAL or STUDENT discounts.

            Return STRICT JSON ONLY (no markdown, no commentary) with this schema:
            {
              "real_pricing": "free" | "freemium" | "free_trial" | "paid" | "open_source" | "enterprise",
              "has_education_discount": true,
              "education_url": "https://...",
              "evidence_quote": "<short verbatim quote from the source>",
              "evidence_url": "<full source URL where you found the pricing>",
              "confidence": 0-100
            }

            Rules:
            - "free": tool is fully free, no paid tier
            - "freemium": free tier + paid upgrades
            - "free_trial": only a time-limited trial then paid
            - "paid": no free option (subscription/license required)
            - "open_source": MIT/Apache/GPL self-host
            - "enterprise": custom pricing/contact sales only
            - has_education_discount=true only if the vendor offers a discount or free plan specifically for students/teachers/schools
            - confidence : 90-100 if quoted from official pricing page, 70-90 if recent reputable secondary source, <70 if guessing
            PROMPT;
    }

    /**
     * Best-effort JSON extraction (gère ```json fences et préambule).
     */
    private function extractJson(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Strip markdown code fences éventuelles
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', (string) $raw);
        $raw = trim((string) $raw);

        // Parse direct
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fallback : extraire le premier objet { ... }
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
