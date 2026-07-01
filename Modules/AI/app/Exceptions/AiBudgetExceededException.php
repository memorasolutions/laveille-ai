<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Exception dédiée au disjoncteur budgétaire IA (`ai.monthly_budget`).
 * RAISON: Distingue un dépassement de budget (attendu, à traiter proprement côté
 *         appelant) d'une panne technique générique. Les services Academy (Tuteur,
 *         Feedback, Authoring, Traduction) capturent déjà `\Throwable`/`\Exception`
 *         autour de `AiService::chatWithHistory()` — cette exception y est donc
 *         absorbée SANS aucune modification de leur code (DRY strict).
 */

declare(strict_types=1);

namespace Modules\AI\Exceptions;

class AiBudgetExceededException extends \RuntimeException
{
}
