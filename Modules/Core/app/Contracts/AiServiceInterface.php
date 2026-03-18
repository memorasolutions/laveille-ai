<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface AiServiceInterface
{
    /** @return array{title: string, description: string} */
    public function generateSeoMeta(string $content, string $locale = 'fr'): array;

    /** @return array{flagged: bool, reason: ?string} */
    public function moderateContent(string $content): array;

    public function translateText(string $text, string $targetLocale): string;

    public function generateSummary(string $content, int $maxWords = 100): string;

    public function chat(string $prompt, ?string $systemPrompt = null): string;
}
