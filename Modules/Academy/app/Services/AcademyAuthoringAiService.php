<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Service d'authoring IA — génère plans de cours + questions QCM en FR-QC
 * MCP: réutilise Modules\AI\Services\AiService (chatWithHistory) — aucun appel HTTP réinventé.
 * RAISON: Différenciateur LMS 2026 — formateur génère un brouillon depuis un prompt,
 *         relit, ajuste, publie. L'IA PROPOSE, jamais de publication automatique.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AcademyAuthoringAiService
{
    /**
     * Génère un plan de cours structuré à partir d'une invite libre.
     *
     * @param  array<string, mixed>  $opts  max_chapters (int), max_lessons_per_chapter (int)
     * @return array{chapters: array<int, array{title: string, lessons: array<int, array{title: string, objective: string}>}>}|array<never>
     */
    public function generateOutline(string $prompt, array $opts = []): array
    {
        if (! config('academy.ai_authoring_enabled', false)) {
            return [];
        }

        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            return [];
        }

        $maxChapters          = max(1, (int) Arr::get($opts, 'max_chapters', 5));
        $maxLessonsPerChapter = max(1, (int) Arr::get($opts, 'max_lessons_per_chapter', 4));

        $systemPrompt = <<<PROMPT
Tu es un expert en conception pédagogique. Réponds UNIQUEMENT avec un objet JSON valide, sans balises markdown, sans texte avant ou après le JSON.

Schéma attendu :
{
  "chapters": [
    {
      "title": "Titre du chapitre",
      "lessons": [
        {
          "title": "Titre de la leçon",
          "objective": "Objectif d'apprentissage court"
        }
      ]
    }
  ]
}

Contraintes :
- Maximum {$maxChapters} chapitres.
- Maximum {$maxLessonsPerChapter} leçons par chapitre.
- Français québécois uniquement.
- JSON strict : pas de clé supplémentaire, pas de commentaire.
PROMPT;

        try {
            /** @var \Modules\AI\Services\AiService $aiService */
            $aiService = app(\Modules\AI\Services\AiService::class);

            $response = $aiService->chatWithHistory([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ]);

            $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($response));

            /** @var array<string, mixed>|null $data */
            $data = json_decode((string) $clean, true);

            if (json_last_error() !== JSON_ERROR_NONE
                || ! is_array($data)
                || ! isset($data['chapters'])
                || ! is_array($data['chapters'])
                || empty($data['chapters'])) {
                return [];
            }

            foreach ($data['chapters'] as $chapter) {
                if (! is_array($chapter)
                    || ! isset($chapter['title'], $chapter['lessons'])
                    || ! is_string($chapter['title'])
                    || ! is_array($chapter['lessons'])) {
                    return [];
                }

                foreach ($chapter['lessons'] as $lesson) {
                    if (! is_array($lesson)
                        || ! isset($lesson['title'], $lesson['objective'])
                        || ! is_string($lesson['title'])
                        || ! is_string($lesson['objective'])) {
                        return [];
                    }
                }
            }

            return $data;
        } catch (\Throwable $e) {
            Log::warning('AcademyAuthoringAiService::generateOutline error', [
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Génère des questions QCM valides en français québécois.
     *
     * @return array<int, array{prompt: string, options: array<int, string>, correct_index: int, explanation: string}>
     */
    public function generateQuizQuestions(string $topicOrLessonText, int $count = 5): array
    {
        if (! config('academy.ai_authoring_enabled', false)) {
            return [];
        }

        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            return [];
        }

        $count = max(1, min($count, 20));

        $systemPrompt = <<<PROMPT
Tu es un concepteur pédagogique expert. Réponds UNIQUEMENT avec un tableau JSON valide, sans balises markdown, sans texte avant ou après.

Schéma attendu pour chaque question :
[
  {
    "prompt": "Énoncé de la question",
    "options": ["Option A", "Option B", "Option C", "Option D"],
    "correct_index": 0,
    "explanation": "Explication de la bonne réponse"
  }
]

Contraintes :
- Générer exactement {$count} questions.
- Chaque question a EXACTEMENT 4 options (tableau de 4 chaînes).
- correct_index est un entier entre 0 et 3.
- Français québécois uniquement.
- JSON strict : aucune clé supplémentaire.
PROMPT;

        try {
            /** @var \Modules\AI\Services\AiService $aiService */
            $aiService = app(\Modules\AI\Services\AiService::class);

            $response = $aiService->chatWithHistory([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $topicOrLessonText],
            ]);

            $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($response));

            /** @var array<int, mixed>|null $questions */
            $questions = json_decode((string) $clean, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($questions)) {
                return [];
            }

            foreach ($questions as $q) {
                if (! is_array($q)
                    || ! isset($q['prompt'], $q['options'], $q['correct_index'], $q['explanation'])
                    || ! is_string($q['prompt'])
                    || ! is_array($q['options'])
                    || count($q['options']) !== 4
                    || ! is_int($q['correct_index'])
                    || $q['correct_index'] < 0
                    || $q['correct_index'] > 3
                    || ! is_string($q['explanation'])) {
                    return [];
                }

                foreach ($q['options'] as $opt) {
                    if (! is_string($opt)) {
                        return [];
                    }
                }
            }

            return $questions;
        } catch (\Throwable $e) {
            Log::warning('AcademyAuthoringAiService::generateQuizQuestions error', [
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
