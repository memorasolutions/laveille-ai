<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Contract DRY : toute source d'audit pricing implémente cette interface.
 * Drivers existants : PpSearch, BrowserFetch, Llm (qwen3-max), PlaywrightScreenshot.
 * Pattern réutilisable pour autres audits (descriptions, catégories, etc.).
 */

namespace Modules\Directory\Services\PricingAudit\Contracts;

use Modules\Directory\Models\Tool;
use Modules\Directory\Services\PricingAudit\PricingSourceResult;

interface PricingSourceContract
{
    /**
     * Récupère et analyse les données pricing pour un outil.
     */
    public function fetch(Tool $tool): PricingSourceResult;

    /**
     * Poids de cette source dans le vote pondéré (1-3).
     */
    public function weight(): int;

    /**
     * Identifiant unique de la source (ex 'ppsearch', 'browser', 'llm', 'screenshot').
     */
    public function name(): string;
}
