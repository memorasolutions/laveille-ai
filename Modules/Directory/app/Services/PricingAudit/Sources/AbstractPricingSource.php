<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Directory\Services\PricingAudit\Sources;

use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\PricingAudit\Contracts\PricingSourceContract;
use Modules\Directory\Services\PricingAudit\PricingSourceResult;
use Throwable;

/**
 * Abstract base class : log + try/catch DRY pour tous drivers.
 */
abstract class AbstractPricingSource implements PricingSourceContract
{
    public function fetch(Tool $tool): PricingSourceResult
    {
        try {
            return $this->doFetch($tool);
        } catch (Throwable $e) {
            Log::error('PricingAudit source error', [
                'source' => $this->name(),
                'tool_id' => $tool->id,
                'error' => $e->getMessage(),
            ]);

            return new PricingSourceResult(
                sourceName: $this->name(),
                weight: $this->weight(),
                error: substr($e->getMessage(), 0, 250),
            );
        }
    }

    abstract protected function doFetch(Tool $tool): PricingSourceResult;

    /**
     * Helper DRY : normaliser les valeurs pricing brutes vers les valeurs canoniques DB.
     */
    protected function normalizePricing(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = strtolower(trim($raw));

        return match (true) {
            // Tester d'abord les valeurs canoniques DB déjà normalisées (avec underscore).
            $raw === 'free_trial' || str_contains($raw, 'free trial') => 'free_trial',
            $raw === 'open_source' || str_contains($raw, 'open source') || str_contains($raw, 'open-source') => 'open_source',
            $raw === 'freemium' => 'freemium',
            $raw === 'enterprise' || str_contains($raw, 'enterprise') => 'enterprise',
            str_contains($raw, 'freemium') => 'freemium',
            $raw === 'paid' || str_contains($raw, 'paid') || str_contains($raw, 'subscription') || str_contains($raw, 'payant') => 'paid',
            $raw === 'free' || str_contains($raw, 'free') || str_contains($raw, 'gratuit') => 'free',
            default => $raw,
        };
    }
}
