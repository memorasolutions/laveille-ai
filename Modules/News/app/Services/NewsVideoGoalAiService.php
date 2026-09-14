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
    private const FALLBACK_CAROUSEL = "Impossible de générer le carrousel pour le moment (service IA indisponible). Réessaie dans quelques minutes ou rédige les diapositives manuellement à partir des actualités sélectionnées.";

    private const FALLBACK_MESSAGE = "Impossible de générer l'objectif de vidéo pour le moment (service IA indisponible). Réessaie dans quelques minutes ou rédige-le manuellement à partir des actualités sélectionnées.";

    /**
     * Génère le CARROUSEL demandé par le fondateur le 2026-09-14 : « sous forme de carroussel
     * (choisir ça ou le prompteur) choix que je vais faire ».
     *
     * POURQUOI UNE SORTIE DISTINCTE PLUTÔT QU'UN COMPROMIS : un prompteur et un support visuel
     * tirent en sens opposés. Le prompteur veut peu de mots, gros, séquencés au rythme de la
     * parole ; un support visuel veut de la densité. Une même diapositive qui doit faire les deux
     * fait mal les deux. Le fondateur a tranché : deux sorties, et c'est LUI qui choisit à chaque
     * génération. Ce carrousel-ci sert d'abord à LUI pendant qu'il parle, donc ce sont les
     * contraintes du prompteur qui gouvernent sa mise en forme.
     *
     * @param  Collection<int, \Modules\News\Models\NewsArticle>  $articles
     */
    public function generateCarousel(Collection $articles): string
    {
        if ($articles->isEmpty()) {
            return "Aucune actualité sélectionnée : impossible de générer un carrousel.";
        }

        return $this->askAi(
            $this->buildCarouselSystemPrompt(),
            $this->buildCarouselUserPrompt($articles),
            $articles,
            self::FALLBACK_CAROUSEL
        );
    }

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

        return $this->askAi(
            $this->buildSystemPrompt(),
            $this->buildUserPrompt($articles),
            $articles,
            self::FALLBACK_MESSAGE
        );
    }

    /**
     * Mécanique d'appel commune aux deux formats (objectif et carrousel). Extraite le 2026-09-14
     * plutôt que recopiée : l'appel à l'IA, le nettoyage des clôtures markdown, la journalisation
     * d'une réponse vide et le repli en cas d'indisponibilité sont IDENTIQUES pour les deux. Seuls
     * les prompts diffèrent, et c'est exactement ce qui doit différer.
     *
     * @param  Collection<int, \Modules\News\Models\NewsArticle>  $articles
     */
    private function askAi(string $systemPrompt, string $userPrompt, Collection $articles, string $fallback): string
    {
        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            Log::warning('NewsVideoGoalAiService — Modules\AI\Services\AiService introuvable.');

            return $fallback;
        }

        try {
            /** @var \Modules\AI\Services\AiService $aiService */
            $aiService = app(\Modules\AI\Services\AiService::class);

            $response = $aiService->chatWithHistory([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ]);

            $clean = trim((string) preg_replace('/^```(?:[a-z]*)?\s*|\s*```$/m', '', trim((string) $response)));

            if ($clean === '') {
                Log::warning('NewsVideoGoalAiService — réponse IA vide.', [
                    'article_ids' => $articles->pluck('id')->all(),
                ]);

                // Le repli est celui du FORMAT demandé, jamais une constante en dur : sinon un
                // carrousel indisponible renverrait le message de l'objectif de vidéo, et le
                // fondateur croirait s'être trompé de bouton.
                return $fallback;
            }

            return $clean;
        } catch (AiBudgetExceededException $e) {
            Log::warning('NewsVideoGoalAiService — budget IA mensuel dépassé.', [
                'exception' => $e->getMessage(),
            ]);

            return "Budget IA mensuel dépassé. La génération n'a pas pu aboutir – réessaie le mois prochain ou rédige le contenu manuellement.";
        } catch (\Throwable $e) {
            Log::warning('NewsVideoGoalAiService error', [
                'exception' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

    /**
     * Consignes du CARROUSEL. Elles diffèrent de celles de l'objectif sur un point qui décide de
     * tout : ce carrousel sera lu PAR STÉPHANE PENDANT QU'IL PARLE, face caméra. C'est donc un
     * prompteur avant d'être un support visuel - d'où les bornes de mots, une idée par diapositive,
     * et l'ordre de passage imposé.
     */
    private function buildCarouselSystemPrompt(): string
    {
        return <<<PROMPT
Tu es un assistant éditorial pour laveille.ai, une plateforme québécoise de veille en IA et en
éducation. Ta tâche : transformer des actualités déjà sélectionnées en un CARROUSEL de diapositives
qui servira DEUX usages en même temps, et le premier commande le second :

1. PROMPTEUR : l'animateur LIT ces diapositives face caméra pendant qu'il parle. Le texte doit donc
   se lire d'un coup d'oeil, à deux mètres d'un écran.
2. SUPPORT VISUEL : ces mêmes diapositives sont montrées au spectateur dans la vidéo.

CONSIGNES STRICTES :
- Une diapositive = UNE SEULE idée. Jamais deux faits sur la même.
- MAXIMUM 20 mots par diapositive, titre compris. C'est une borne dure, pas une moyenne.
- Phrases courtes, verbe conjugué, aucune subordonnée empilée : ce qui se lit mal se dit mal.
- Structure : une diapositive d'ouverture, puis 2 à 3 diapositives PAR actualité, puis une
  diapositive de clôture qui renvoie à laveille.ai.
- Pour chaque actualité, la première diapositive porte le FAIT (qui a fait quoi), la seconde porte
  le CHIFFRE ou la nuance décisive avec son unité et sa source, la troisième (facultative) porte ce
  que ça change concrètement pour l'audience.
- N'invente AUCUN fait, AUCUN chiffre, AUCUNE source qui ne soit pas dans les actualités fournies.
  S'il n'y a pas de chiffre, n'en fabrique pas : écris la nuance à la place.
- Français du Québec. Pas de jargon marketing, pas de superlatif, pas de question rhétorique.
- Jamais de tiret cadratin.

FORMAT DE SORTIE, à respecter exactement :
DIAPO 1 - [TITRE COURT]
[texte de la diapositive]

DIAPO 2 - [TITRE COURT]
[texte de la diapositive]

... et ainsi de suite. Rien d'autre : pas de préambule, pas de conclusion, pas de balises markdown,
pas de commentaire sur ton propre travail.
PROMPT;
    }

    /**
     * @param  Collection<int, \Modules\News\Models\NewsArticle>  $articles
     */
    private function buildCarouselUserPrompt(Collection $articles): string
    {
        $blocks = $articles->map(function ($article): string {
            $title = $article->seo_title ?: $article->title;
            $flattened = trim((string) $article->flattenStructuredSummary());

            if ($flattened === '') {
                $flattened = trim((string) ($article->summary ?? ''));
            }

            return "- Titre : {$title}\n  Contenu : {$flattened}";
        })->implode("\n\n");

        $count = $articles->count();
        $ouverture = 1;
        $cloture = 1;
        $minDiapos = $ouverture + ($count * 2) + $cloture;
        $maxDiapos = $ouverture + ($count * 3) + $cloture;

        return "Voici {$count} actualité(s) à transformer en carrousel :\n\n{$blocks}\n\n" .
            "Produis le carrousel : {$minDiapos} à {$maxDiapos} diapositives, dans l'ordre des " .
            "actualités ci-dessus, en respectant la borne de 20 mots par diapositive.";
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
                // ACTION : description ne véhicule plus jamais le texte source (design doc
                // "Actus - zéro copie du texte source", 2026-08-13, section 4.1).
                // MCP: SELF (<5 lignes)
                $flattened = trim((string) ($article->summary ?? ''));
            }

            return "- Titre : {$title}\n  Contenu : {$flattened}";
        })->implode("\n\n");

        $count = $articles->count();

        return "Voici {$count} actualité(s) sélectionnée(s) pour la vidéo :\n\n{$blocks}\n\n" .
            "Rédige maintenant l'objectif de vidéo (3-5 phrases, français du Québec) tel que décrit dans les consignes.";
    }
}
