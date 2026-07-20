<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Service IA — synthétise un « objectif de vidéo » à partir d'actualités sélectionnées.
 * MCP: réutilise Modules\AI\Services\AiService (chatWithHistory) — aucun appel HTTP réinventé.
 * RAISON: Le superadmin sélectionne des actualités publiées sur une plage de dates libre et veut
 *         un texte prêt à coller dans le champ « Objectif de la vidéo » (#prompteur-goal-textarea)
 *         du Prompteur BYOA (100% client-side, aucune intégration serveur entre les deux outils).
 */

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\AI\Exceptions\AiBudgetExceededException;

class NewsVideoGoalAiService
{
    private const FALLBACK_MESSAGE = "Impossible de générer l'objectif de vidéo pour le moment (service IA indisponible). Réessaie dans quelques minutes ou rédige-le manuellement à partir des actualités sélectionnées.";

    /**
     * Génère un paragraphe court (3-5 phrases) en français du Québec décrivant l'objectif d'une
     * vidéo couvrant les actualités sélectionnées. Retourne TOUJOURS une chaîne non vide : soit
     * le texte généré par l'IA, soit un message d'erreur explicite affichable tel quel côté vue.
     *
     * @param  Collection<int, \Modules\News\Models\NewsArticle>  $articles
     */
    public function generateGoal(Collection $articles): string
    {
        if ($articles->isEmpty()) {
            return "Aucune actualité sélectionnée : impossible de générer un objectif de vidéo.";
        }

        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            Log::warning('NewsVideoGoalAiService::generateGoal — Modules\AI\Services\AiService introuvable.');

            return self::FALLBACK_MESSAGE;
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($articles);

        try {
            /** @var \Modules\AI\Services\AiService $aiService */
            $aiService = app(\Modules\AI\Services\AiService::class);

            $response = $aiService->chatWithHistory([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ]);

            $clean = trim((string) preg_replace('/^```(?:[a-z]*)?\s*|\s*```$/m', '', trim((string) $response)));

            if ($clean === '') {
                Log::warning('NewsVideoGoalAiService::generateGoal — réponse IA vide.', [
                    'article_ids' => $articles->pluck('id')->all(),
                ]);

                return self::FALLBACK_MESSAGE;
            }

            return $clean;
        } catch (AiBudgetExceededException $e) {
            Log::warning('NewsVideoGoalAiService::generateGoal — budget IA mensuel dépassé.', [
                'exception' => $e->getMessage(),
            ]);

            return "Budget IA mensuel dépassé. L'objectif de vidéo n'a pas pu être généré — réessaie le mois prochain ou rédige-le manuellement.";
        } catch (\Throwable $e) {
            Log::warning('NewsVideoGoalAiService::generateGoal error', [
                'exception' => $e->getMessage(),
            ]);

            return self::FALLBACK_MESSAGE;
        }
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Tu es un assistant éditorial pour une plateforme québécoise de veille technologique en IA/éducation
(laveille.ai). Ta tâche : rédiger l'« objectif de vidéo » à partir d'une liste d'actualités déjà
sélectionnées par l'admin. Ce texte sera collé TEL QUEL dans le champ « Objectif de la vidéo » d'un
outil de génération de prompt vidéo (Prompteur), qui décrit en 1-2 phrases le but de la vidéo.

CONSIGNES STRICTES :
- Rédige un paragraphe COURT de 3 à 5 phrases, en français du Québec (québécois écrit standard).
- Ce n'est PAS un résumé des actualités : c'est un objectif de vidéo. Explique quel est le BUT de la
  vidéo, QUELLES actualités elle couvre (thème général, pas la liste exhaustive) et POURQUOI c'est
  pertinent pour l'audience (enseignants, professionnels, curieux de tech/IA).
- Utilise un ton clair, concret, orienté audience — jamais de jargon marketing creux.
- N'invente AUCUN fait qui ne provient pas des actualités fournies.
- Réponds UNIQUEMENT avec le paragraphe final, sans titre, sans guillemets, sans balises markdown,
  sans préambule (« Voici... »), sans liste à puces.
PROMPT;
    }

    /**
     * @param  Collection<int, \Modules\News\Models\NewsArticle>  $articles
     */
    private function buildUserPrompt(Collection $articles): string
    {
        $blocks = $articles->map(function ($article): string {
            $title = $article->seo_title ?: $article->title;
            $flattened = trim((string) $article->flattenStructuredSummary());

            if ($flattened === '') {
                $flattened = trim((string) ($article->summary ?? $article->description ?? ''));
            }

            return "- Titre : {$title}\n  Contenu : {$flattened}";
        })->implode("\n\n");

        $count = $articles->count();

        return "Voici {$count} actualité(s) sélectionnée(s) pour la vidéo :\n\n{$blocks}\n\n" .
            "Rédige maintenant l'objectif de vidéo (3-5 phrases, français du Québec) tel que décrit dans les consignes.";
    }
}
