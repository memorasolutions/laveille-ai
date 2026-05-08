<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Directory\Services\PricingAudit;

/**
 * Data class : résultat d'une source d'audit pricing.
 * Immutable, sérialisable JSON pour persistence.
 */
final class PricingSourceResult
{
    public function __construct(
        public readonly string $sourceName,
        public readonly int $weight,
        public readonly ?string $realPricing = null,
        public readonly ?bool $hasEducationDiscount = null,
        public readonly ?string $educationUrl = null,
        public readonly ?string $evidenceQuote = null,
        public readonly ?string $evidenceUrl = null,
        public readonly int $confidence = 0, // 0-100 self-reported par la source
        public readonly ?string $rawPayload = null,
        public readonly ?string $screenshotPath = null,
        public readonly ?string $error = null,
    ) {}

    public function isValid(): bool
    {
        return $this->error === null && $this->realPricing !== null;
    }

    public function toArray(): array
    {
        return [
            'source' => $this->sourceName,
            'weight' => $this->weight,
            'real_pricing' => $this->realPricing,
            'has_education_discount' => $this->hasEducationDiscount,
            'education_url' => $this->educationUrl,
            'evidence_quote' => $this->evidenceQuote,
            'evidence_url' => $this->evidenceUrl,
            'confidence' => $this->confidence,
            'screenshot_path' => $this->screenshotPath,
            'error' => $this->error,
        ];
    }
}
