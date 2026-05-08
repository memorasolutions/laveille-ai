<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Service principal d'audit pricing : orchestre les drivers, calcule le vote
 * pondéré, persiste l'audit dans tool_pricing_audits.
 */

namespace Modules\Directory\Services\PricingAudit;

use Illuminate\Support\Collection;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolPricingAudit;
use Modules\Directory\Services\PricingAudit\Contracts\PricingSourceContract;

class ToolPricingAuditor
{
    /** @var array<PricingSourceContract> */
    private array $sources = [];

    public function __construct(array $sources = [])
    {
        $this->sources = $sources;
    }

    public function addSource(PricingSourceContract $source): self
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * Audite un outil : exécute tous les drivers + vote pondéré + persistance.
     */
    public function auditTool(Tool $tool): ToolPricingAudit
    {
        $results = collect($this->sources)
            ->map(fn (PricingSourceContract $s) => $s->fetch($tool));

        $consensus = $this->runConsensus($results);

        return $this->persistAudit($tool, $results, $consensus);
    }

    /**
     * Vote pondéré : agrège les valeurs réelles de chaque source.
     * Retourne ['real_pricing' => string|null, 'has_education_discount' => bool|null,
     * 'weighted_score' => 0-100, 'confidence' => 0-100].
     */
    public function runConsensus(Collection $results): array
    {
        $valid = $results->filter(fn (PricingSourceResult $r) => $r->isValid());

        if ($valid->isEmpty()) {
            return [
                'real_pricing' => null,
                'has_education_discount' => null,
                'education_url' => null,
                'weighted_score' => 0,
                'confidence' => 0,
            ];
        }

        // Pricing : weighted vote (somme des poids par valeur)
        $pricingVotes = $valid->groupBy(fn (PricingSourceResult $r) => $r->realPricing)
            ->map(fn (Collection $bucket) => $bucket->sum('weight'));
        $totalWeightAll = $this->sources ? array_sum(array_map(fn (PricingSourceContract $s) => $s->weight(), $this->sources)) : 1;
        $maxPricingWeight = $pricingVotes->max();
        $winningPricing = $pricingVotes->filter(fn ($v) => $v === $maxPricingWeight)->keys()->first();

        // Education discount : majority sur les sources qui ont une opinion non-null
        $eduValid = $valid->filter(fn (PricingSourceResult $r) => $r->hasEducationDiscount !== null);
        $eduDiscount = null;
        if ($eduValid->isNotEmpty()) {
            $eduWeightTrue = $eduValid->filter(fn ($r) => $r->hasEducationDiscount === true)->sum('weight');
            $eduWeightFalse = $eduValid->filter(fn ($r) => $r->hasEducationDiscount === false)->sum('weight');
            $eduDiscount = $eduWeightTrue > $eduWeightFalse;
        }

        $eduUrl = $valid->firstWhere('hasEducationDiscount', true)?->educationUrl;

        // Weighted score = (poids du pricing gagnant / total poids des sources) * 100
        $weightedScore = (int) round(($maxPricingWeight / max(1, $totalWeightAll)) * 100);

        // Confidence agrégée = moyenne pondérée des confidences des sources qui ont voté pour le winning pricing
        $winnerSources = $valid->filter(fn (PricingSourceResult $r) => $r->realPricing === $winningPricing);
        $confidence = $winnerSources->isNotEmpty()
            ? (int) round($winnerSources->avg('confidence'))
            : 0;

        return [
            'real_pricing' => $winningPricing,
            'has_education_discount' => $eduDiscount,
            'education_url' => $eduUrl,
            'weighted_score' => $weightedScore,
            'confidence' => $confidence,
        ];
    }

    /**
     * Persiste l'audit complet dans tool_pricing_audits.
     */
    public function persistAudit(Tool $tool, Collection $results, array $consensus): ToolPricingAudit
    {
        $sourcesData = $results->map(fn (PricingSourceResult $r) => $r->toArray())->all();
        $screenshot = $results->firstWhere(fn (PricingSourceResult $r) => $r->screenshotPath !== null)?->screenshotPath;
        $primaryEvidence = $results->first(fn (PricingSourceResult $r) => $r->isValid() && $r->evidenceQuote);

        return ToolPricingAudit::create([
            'directory_tool_id' => $tool->id,
            'audited_at' => now(),
            'real_pricing' => $consensus['real_pricing'],
            'has_education_discount' => $consensus['has_education_discount'],
            'education_url' => $consensus['education_url'],
            'confidence' => $consensus['confidence'],
            'weighted_score' => $consensus['weighted_score'],
            'sources' => $sourcesData,
            'evidence' => $primaryEvidence ? [
                'quote' => $primaryEvidence->evidenceQuote,
                'url' => $primaryEvidence->evidenceUrl,
                'source' => $primaryEvidence->sourceName,
            ] : null,
            'screenshot_path' => $screenshot,
            'review_status' => 'pending',
        ]);
    }
}
