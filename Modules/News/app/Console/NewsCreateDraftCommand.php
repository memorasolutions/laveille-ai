<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;

/**
 * Création manuelle d'une fiche BROUILLON à partir d'un lien (design doc "Actus - composition
 * manuelle assistée" 2026-08-15, section "Améliorations en attente", point 1 - haute priorité).
 * Calque le geste supervisé du 17 août (fiche 33530), alors fait entièrement à la main : un post
 * X ou une annonce hors collecte RSS n'a aucune fiche à composer tant qu'elle n'a pas été créée
 * ici. Point d'entrée console de Modules\News\Models\NewsArticle::createManualDraft() (SEULE
 * implémentation - DRY strict), réutilisée telle quelle par
 * Modules\News\Http\Controllers\Admin\NewsCompositionController::createDraft(), la porte web
 * équivalente de l'écran de composition.
 *
 * Idempotente par URL : un second appel sur la même URL renvoie la fiche déjà créée (created:
 * false) plutôt que d'en créer une seconde - un rejeu du skill ou un double clic ne duplique
 * jamais une fiche.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class NewsCreateDraftCommand extends Command
{
    protected $signature = 'news:create-draft {url : URL de l\'original (article, post X...)} {--title= : Titre de travail ; défaut si absent}';

    protected $description = 'Crée (ou récupère si l\'URL existe déjà) une fiche brouillon à partir d\'un lien, prête pour /actu2.';

    public function handle(): int
    {
        $url = trim((string) $this->argument('url'));

        // ACTION : validation volontairement large (filter_var FILTER_VALIDATE_URL) - accepte
        // aussi bien un article classique qu'un lien x.com/twitter.com tel quel, sans
        // traitement spécial : aucun de ces formats n'est rejeté par cette règle.
        // MCP: SELF (<5 lignes)
        // RAISON: mandat explicite, aucune restriction de domaine.
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error("URL invalide : {$url}");

            return self::FAILURE;
        }

        $title = $this->option('title');
        $title = is_string($title) && trim($title) !== '' ? trim($title) : null;

        ['article' => $article, 'created' => $created] = NewsArticle::createManualDraft($url, $title);

        Log::channel('composition')->info('news:create-draft', [
            'article_id' => $article->id,
            'url' => $url,
            'created' => $created,
        ]);

        $this->line(json_encode([
            'id' => $article->id,
            'slug' => $article->slug,
            'url' => $article->url,
            'created' => $created,
            'mini_prompt' => '/actu2 '.$article->url.' fiche:'.$article->id,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
