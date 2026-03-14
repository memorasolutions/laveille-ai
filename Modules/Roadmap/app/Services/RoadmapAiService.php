<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Services;

use Illuminate\Support\Collection;
use Modules\Roadmap\Models\Idea;

class RoadmapAiService
{
    public function categorize(string $title, string $description): ?string
    {
        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            return null;
        }

        try {
            $aiService = app(\Modules\AI\Services\AiService::class);

            $response = $aiService->chat(
                "Classify this idea into ONE category: feature, bug, improvement, ux. Title: {$title}. Description: {$description}. Reply with ONLY the category word."
            );

            $response = strtolower(trim($response));

            return match (true) {
                str_contains($response, 'bug') => 'bug',
                str_contains($response, 'ux') => 'ux',
                str_contains($response, 'improvement') => 'improvement',
                str_contains($response, 'feature') => 'feature',
                default => null,
            };
        } catch (\Exception) {
            return null;
        }
    }

    public function detectDuplicates(string $title, int $boardId, int $limit = 3): Collection
    {
        $words = array_filter(explode(' ', trim($title)));
        $firstWord = $words[0] ?? '';

        if (strlen($firstWord) < 3) {
            return collect();
        }

        return Idea::where('board_id', $boardId)
            ->where('title', 'LIKE', "%{$firstWord}%")
            ->limit($limit)
            ->get();
    }
}
