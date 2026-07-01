<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Service tuteur IA ancré au cours — RAG contexte leçon, prompt strict, zéro hallucination
 * SELF: < 5 lignes de logique propre dans les méthodes court-circuitées
 * RAISON: Différenciateur LMS 2026 — apprenant peut interroger le contenu 24/7 en FR-QC
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Support\Facades\Log;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

class AcademyTutorService
{
    /** Taille maximale du contexte RAG en caractères (environ 1 500 tokens) */
    public const MAX_CONTEXT_CHARS = 6000;

    /** Nombre maximal de tours de conversation conservés dans l'historique */
    public const MAX_HISTORY_TURNS = 3;

    /**
     * Pose une question au tuteur IA en contexte d'une leçon et d'un cours.
     *
     * @param  array<int, array<string, mixed>>  $history
     * @return array{answer: string, lesson_ref: string, error: bool}
     */
    public function ask(Lesson $lesson, Course $course, string $question, array $history = []): array
    {
        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            return [
                'answer'     => "Le service d'intelligence artificielle n'est pas disponible pour l'instant.",
                'lesson_ref' => $lesson->title,
                'error'      => true,
            ];
        }

        try {
            $context  = $this->buildContext($lesson, $course);
            $messages = $this->buildMessages(
                context:     $context,
                lessonTitle: $lesson->title,
                courseTitle: $course->title,
                question:    $question,
                history:     $history
            );

            /** @var \Modules\AI\Services\AiService $aiService */
            $aiService = app(\Modules\AI\Services\AiService::class);
            $response  = $aiService->chatWithHistory($messages);

            if (empty(trim($response))) {
                throw new \RuntimeException('Réponse vide du service IA.');
            }

            return [
                'answer'     => $response,
                'lesson_ref' => $lesson->title,
                'error'      => false,
            ];
        } catch (\Exception $e) {
            // Loi 25 : on ne logue pas le contenu de la question (PII potentiel)
            Log::warning('AcademyTutorService::ask error', [
                'lesson_id' => $lesson->id,
                'course_id' => $course->id,
                'exception' => $e->getMessage(),
            ]);

            return [
                'answer'     => 'Désolé, une erreur est survenue. Veuillez réessayer dans quelques instants.',
                'lesson_ref' => $lesson->title,
                'error'      => true,
            ];
        }
    }

    /**
     * Construit le contexte RAG à partir du contenu textuel de la leçon et du cours.
     * Priorise les items de type « doc » (rich_text markdown). Tronque à MAX_CONTEXT_CHARS.
     */
    public function buildContext(Lesson $lesson, Course $course): string
    {
        if (! $lesson->relationLoaded('lessonItems')) {
            $lesson->load('lessonItems');
        }

        $parts = [];

        // Titre et résumé de la leçon (méta-donnée primaire)
        $parts[] = 'Leçon : ' . $lesson->title;
        if (! empty($lesson->summary)) {
            $parts[] = 'Résumé : ' . $lesson->summary;
        }

        // Corps textuel des items de type « doc » (contenu pédagogique principal)
        foreach ($lesson->lessonItems as $item) {
            if ($item->type === 'doc' && ! empty($item->payload['rich_text'])) {
                $html = LessonItem::renderRichText($item->payload['rich_text']);
                $text = trim(strip_tags($html));
                if ($text !== '') {
                    if ($item->title) {
                        $parts[] = '--- ' . $item->title . ' ---';
                    }
                    $parts[] = $text;
                }
            }
        }

        // Informations du cours (contexte général)
        $parts[] = 'Cours : ' . $course->title;
        if (! empty($course->subtitle)) {
            $parts[] = 'Sous-titre : ' . $course->subtitle;
        }
        if (! empty($course->description)) {
            $parts[] = 'Description : ' . $course->description;
        }

        $full = implode("\n\n", $parts);

        // Tronquer en préservant le début (contenu de la leçon priorisé)
        if (strlen($full) > self::MAX_CONTEXT_CHARS) {
            $full = substr($full, 0, self::MAX_CONTEXT_CHARS);
        }

        return $full;
    }

    /**
     * Construit le tableau de messages pour l'appel à l'API (format OpenRouter).
     *
     * @param  array<int, array<string, mixed>>  $history
     * @return array<int, array{role: string, content: string}>
     */
    public function buildMessages(
        string $context,
        string $lessonTitle,
        string $courseTitle,
        string $question,
        array $history
    ): array {
        $systemPrompt = <<<PROMPT
Tu es le tuteur IA du cours « {$courseTitle} ». Tu réponds UNIQUEMENT à partir du CONTENU FOURNI ci-dessous.

Règles absolues :
1. Si la réponse n'est pas dans le contenu fourni, réponds exactement : « Je ne trouve pas cette information dans la leçon « {$lessonTitle} ». Consulte ton formateur pour cette question. »
2. Cite toujours la leçon par son titre dans ta réponse.
3. Réponds en français québécois clair, concis et encourageant.
4. Jamais d'invention, jamais de généralisation hors du contexte fourni.
5. Tu n'es pas un assistant général — tu es ancré exclusivement à ce cours.

CONTENU DE LA LEÇON « {$lessonTitle} » :
{$context}
PROMPT;

        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        // Historique : conserver au plus MAX_HISTORY_TURNS tours (2 msg/tour)
        $maxMessages = self::MAX_HISTORY_TURNS * 2;
        if (count($history) > $maxMessages) {
            $history = array_slice($history, -$maxMessages);
        }

        foreach ($history as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                $messages[] = ['role' => (string) $msg['role'], 'content' => (string) $msg['content']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        return $messages;
    }
}
