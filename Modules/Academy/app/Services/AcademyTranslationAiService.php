<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Service de traduction IA d'un champ texte — brouillon, jamais publié automatiquement
 * MCP: réutilise Modules\AI\Services\AiService (chatWithHistory) — aucun appel HTTP réinventé.
 * RAISON: Différenciateur LMS 2026 — le formateur traduit un champ de cours et VALIDE avant usage.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Support\Facades\Log;

class AcademyTranslationAiService
{
    /**
     * Traduit un texte d'une locale source vers une locale cible, via l'IA.
     * Ne modifie JAMAIS le modèle : retourne simplement le texte traduit (brouillon),
     * au formateur de le relire et de décider quoi en faire.
     */
    public function translateField(string $text, string $sourceLocale, string $targetLocale): string
    {
        if (! config('academy.ai_translation_enabled', false)) {
            return '';
        }

        if (trim($text) === '') {
            return '';
        }

        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            return '';
        }

        try {
            /** @var \Modules\AI\Services\AiService $aiService */
            $aiService = app(\Modules\AI\Services\AiService::class);

            $systemPrompt = "You are a professional translator. Faithfully translate the provided text from {$sourceLocale} to {$targetLocale}. Use a professional and pedagogical tone. NEVER alter the technical meaning. Do NOT translate proper names, brand names, or untranslatable technical terms (e.g., software names). If the text contains HTML, preserve the HTML tags exactly. Respond ONLY with the translated text, without any comments, quotes, or prefixes.";

            $response = $aiService->chatWithHistory([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $text],
            ]);

            if (empty(trim($response))) {
                return '';
            }

            return trim($response);
        } catch (\Throwable $e) {
            Log::warning('AcademyTranslationAiService::translateField error', [
                'exception' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
