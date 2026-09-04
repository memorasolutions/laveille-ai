<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\CompositionPromptBuilder;
use Modules\News\Services\NewsImageService;

/**
 * Point d'entrée LECTURE SEULE du skill Claude Code local /actu2 (design doc "Actus - composition
 * manuelle assistée" 2026-08-15, section "Implémentation /actu2 - volet serveur (2026-08-17)") :
 * sort un JSON canonique décrivant l'état courant d'une fiche, sur stdout, sans jamais écrire.
 * Le skill l'appelle en tout premier (avant toute décision de rédaction) pour connaître ce qui
 * est déjà en base et la version de politique de composition en vigueur.
 *
 * N'écrit RIEN - contrairement à Modules\News\Console\NewsApplyCommand (SEULE porte d'écriture
 * bornée) et Modules\News\Console\NewsSourceCommand (récolte de l'ORIGINAL) : cette commande ne
 * fait qu'un SELECT et un json_encode(), jamais un update().
 *
 * ACTION : défaut 1 (2026-08-28, mandat "aucune porte de LECTURE du résumé composé") -
 * structured_summary (le résumé composé, quand il existe) est désormais rendu SYSTÉMATIQUEMENT,
 * jamais derrière une option : le contrat de cette commande est déjà de décrire « ce qui est
 * déjà en base » avant toute décision de rédaction (voir le paragraphe ci-dessus), et une clé
 * JSON ajoutée ne casse jamais un appelant qui lit des champs par nom - aucun consommateur connu
 * (skill /actu2, tests) ne compare l'ensemble exact des clés du JSON.
 * MCP: SELF (<5 lignes utiles)
 * RAISON: mandat 2026-08-28 - une correction éditoriale réelle était à l'arrêt faute de pouvoir
 * relire le résumé composé autrement qu'en le reconstruisant depuis le HTML rendu.
 *
 * ACTION : meta_description ajoutée au JSON (2026-08-30, tâche #1942) - cette clé, désormais
 * modifiable via NewsApplyCommand (--payload=), était invisible du brief : impossible de savoir,
 * avant de corriger une fiche, si une valeur figée existait déjà. Même doctrine que
 * structured_summary ci-dessus : champ ajouté sans option, jamais caché derrière un flag.
 * MCP: SELF (<5 lignes)
 * RAISON: tâche #1942 - « décrire ce qui est déjà en base avant toute décision de rédaction »
 * vaut aussi pour ce champ, maintenant qu'il est écrivable par cette même porte.
 *
 * ACTION : publish_readiness ajouté au JSON (2026-09-04, ticket #2237) - cette commande est le
 * SEUL endroit du code que le skill /actu2 nomme « préflight » (étape 0), mais elle ne vérifiait
 * QUE is_published : rien n'exposait si la fiche remplissait déjà seo_title/summary/
 * editorial_proof_pairs/image_credit ni si ses paires de preuve étaient valides. Résultat mesuré
 * la nuit du 2026-09-03 : 7 fiches sur 9 soumises à la publication ont été refusées par
 * NewsArticle::publishReadinessCheck() alors que le prévol les avait laissées passer, faute d'un
 * verdict à consulter avant --publish. DRY strict : publish_readiness DÉLÈGUE cette même méthode,
 * déjà la SEULE source de vérité de la règle « prêt à publier » (voir son docblock) - jamais une
 * réimplémentation, même partielle, des mêmes conditions.
 * MCP: SELF (1 ligne utile)
 * RAISON: ticket #2237 - le prévol de /actu2 doit vérifier ce que la publication exige réellement,
 * pas seulement si la fiche est déjà publiée.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class NewsBriefCommand extends Command
{
    protected $signature = 'news:brief {article : id de la fiche news_articles}';

    protected $description = 'Sort un JSON canonique (lecture seule) décrivant une fiche - point d\'entrée du skill /actu2.';

    public function __construct(private readonly NewsImageService $imageService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $articleId = (int) $this->argument('article');
        $article = NewsArticle::find($articleId);

        if (! $article) {
            $this->error("Fiche introuvable : {$articleId}.");

            return self::FAILURE;
        }

        $this->line(json_encode([
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'url' => $article->url,
            'resolved_url' => $article->resolved_url,
            'is_published' => (bool) $article->is_published,
            'source_content_hash' => $article->source_content_hash,
            'source_captured_at' => $article->source_captured_at?->toIso8601String(),
            'updated_at' => $article->updated_at?->toIso8601String(),
            'primary_sources' => $article->primary_sources ?? [],
            'meta_description' => $article->meta_description,
            'structured_summary' => $article->structured_summary,
            'nature_original' => $article->nature_original,
            'niveau_preuve' => $article->niveau_preuve,
            'has_image' => $this->imageService->exists($article->id),
            'publish_readiness' => $article->publishReadinessCheck(),
            'policy_version' => CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION,
            'site_url' => url('/actualites/'.$article->slug),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
