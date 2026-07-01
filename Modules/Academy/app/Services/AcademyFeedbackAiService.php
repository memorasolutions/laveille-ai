<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Service de FEEDBACK IA sur remises rédigées — évalue selon la RUBRIQUE
 *         du devoir, propose un feedback constructif FR-QC + une note SUGGÉRÉE.
 * MCP: réutilise Modules\AI\Services\AiService (déjà en prod) — aucun appel HTTP réinventé.
 * RAISON: Différenciateur LMS 2026 — correction assistée ; le formateur garde le
 *         dernier mot (l'IA PROPOSE, ne note JAMAIS l'apprenant automatiquement).
 *
 * Loi 25 : on n'expédie que la consigne + la rubrique + le TEXTE de la remise
 * (nécessaire à l'évaluation) ; jamais le nom/courriel de l'apprenant.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use Illuminate\Support\Facades\Log;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Submission;

class AcademyFeedbackAiService
{
    /** Taille maximale du texte de la remise envoyé à l'IA (≈ 3 000 tokens). */
    public const MAX_BODY_CHARS = 12000;

    /**
     * Évalue une remise selon la rubrique de son devoir et retourne une PROPOSITION
     * (feedback + note suggérée). N'écrit RIEN : le composant Livewire persistera le
     * brouillon après validation formateur. Échec gracieux : error=true, jamais d'exception.
     *
     * @return array{feedback: string, suggested_score: float|null, max_points: int, error: bool}
     */
    public function evaluate(Submission $submission, Assignment $assignment): array
    {
        $maxPoints = (int) $assignment->max_points;

        $fail = fn (): array => [
            'feedback'        => '',
            'suggested_score' => null,
            'max_points'      => $maxPoints,
            'error'           => true,
        ];

        // Portabilité : si le module AI est absent/désactivé, on dégrade sans casser.
        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            return $fail();
        }

        $body = trim((string) $submission->body);
        if ($body === '') {
            // Rien à évaluer (remise sans texte rédigé) — on ne devine pas de note.
            return $fail();
        }

        try {
            $messages = $this->buildMessages($assignment, $body, $maxPoints);

            /** @var \Modules\AI\Services\AiService $aiService */
            $aiService = app(\Modules\AI\Services\AiService::class);
            $response  = $aiService->chatWithHistory($messages);

            if (trim($response) === '') {
                throw new \RuntimeException('Réponse vide du service IA.');
            }

            $parsed = $this->parseResponse($response, $maxPoints);

            if ($parsed['feedback'] === '') {
                throw new \RuntimeException('Feedback IA illisible.');
            }

            return [
                'feedback'        => $parsed['feedback'],
                'suggested_score' => $parsed['suggested_score'],
                'max_points'      => $maxPoints,
                'error'           => false,
            ];
        } catch (\Throwable $e) {
            // Loi 25 : on ne logue pas le contenu de la remise (PII potentiel).
            Log::warning('AcademyFeedbackAiService::evaluate error', [
                'submission_id' => $submission->id,
                'assignment_id' => $assignment->id,
                'exception'     => $e->getMessage(),
            ]);

            return $fail();
        }
    }

    /**
     * Construit les messages (format OpenRouter). Le prompt système impose la rubrique
     * comme référentiel, un feedback structuré par critère en FR-QC, la citation
     * d'extraits, et un format de sortie STRICT pour extraire la note suggérée.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function buildMessages(Assignment $assignment, string $body, int $maxPoints): array
    {
        $body        = $this->truncate($body, self::MAX_BODY_CHARS);
        $consigne    = $this->plainText((string) $assignment->instructions);
        $consigneTxt = $consigne !== '' ? $consigne : '(Aucune consigne détaillée fournie.)';
        $rubrique    = $this->buildRubricText($assignment, $maxPoints);
        $titre       = (string) $assignment->title;

        $systemPrompt = <<<PROMPT
Tu es un correcteur pédagogique expert. Tu évalues la remise d'un apprenant pour le devoir « {$titre} », UNIQUEMENT selon la RUBRIQUE fournie et à partir du SEUL contenu de la remise.

Règles absolues :
1. Évalue STRICTEMENT selon la rubrique ci-dessous. Ne juge que le contenu fourni ; n'invente rien, ne suppose pas d'information hors texte.
2. Rédige un feedback constructif, bienveillant et précis, en français québécois, STRUCTURÉ PAR CRITÈRE de la rubrique. Pour chaque critère : ce qui est réussi, ce qui manque, une piste d'amélioration concrète.
3. Cite de courts extraits VERBATIM de la remise (entre guillemets) pour appuyer chaque observation.
4. Sois juste et cohérent : la note doit refléter le feedback (pas de note généreuse avec un feedback sévère, ni l'inverse).
5. Tu PROPOSES une note ; c'est le formateur qui tranche. N'affirme jamais une note « définitive ».

RUBRIQUE / BARÈME (sur {$maxPoints} points) :
{$rubrique}

CONSIGNE DU DEVOIR :
{$consigneTxt}

FORMAT DE SORTIE OBLIGATOIRE (respecte-le exactement) :
NOTE_SUGGEREE: <nombre entre 0 et {$maxPoints}>
---
<feedback détaillé structuré par critère, en markdown>
PROMPT;

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "REMISE DE L'APPRENANT À ÉVALUER :\n\n" . $body],
        ];
    }

    /**
     * Décrit la rubrique en texte : critères + niveaux + points, ou le barème simple
     * si le devoir n'a pas de grille. Sert de référentiel d'évaluation dans le prompt.
     */
    private function buildRubricText(Assignment $assignment, int $maxPoints): string
    {
        if (! $assignment->hasRubric()) {
            return "Note globale sur {$maxPoints} points selon la qualité, l'exactitude et la clarté de la remise au regard de la consigne.";
        }

        $lines = [];
        foreach ($assignment->rubricCriteria()->with('levels')->get() as $criterion) {
            $levels = $criterion->levels;
            if ($levels->isEmpty()) {
                continue;
            }

            $lines[] = '• ' . trim((string) $criterion->description);
            foreach ($levels as $level) {
                $lines[] = '   - ' . trim((string) $level->description) . ' (' . (int) $level->points . ' pt)';
            }
        }

        return $lines === []
            ? "Note globale sur {$maxPoints} points selon la qualité de la remise."
            : implode("\n", $lines);
    }

    /**
     * Extrait la note suggérée (ligne NOTE_SUGGEREE) et le feedback du texte brut IA.
     * La note est bornée SERVEUR à [0..maxPoints] ; toute valeur absente/illisible → null.
     * Tolérant : accepte le séparateur « --- » optionnel et une note en tête ou en fin.
     *
     * @return array{feedback: string, suggested_score: float|null}
     */
    public function parseResponse(string $response, int $maxPoints): array
    {
        $suggested = null;

        if (preg_match('/NOTE_SUGGEREE\s*[:=]\s*([0-9]+(?:[.,][0-9]+)?)/iu', $response, $m) === 1) {
            $value     = (float) str_replace(',', '.', $m[1]);
            $suggested = max(0.0, min($value, (float) $maxPoints));
        }

        // Retire la ligne de note et un éventuel séparateur « --- » pour ne garder que le feedback.
        $feedback = preg_replace('/^.*NOTE_SUGGEREE\s*[:=].*$/imu', '', $response) ?? $response;
        $feedback = preg_replace('/^\s*-{3,}\s*$/mu', '', $feedback) ?? $feedback;
        $feedback = trim((string) $feedback);

        return [
            'feedback'        => $feedback,
            'suggested_score' => $suggested,
        ];
    }

    /** Convertit un markdown/HTML éventuel en texte brut compact pour le prompt. */
    private function plainText(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? $value);
    }

    /** Tronque en préservant le début (jamais couper au milieu d'un multi-octet). */
    private function truncate(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }
}
