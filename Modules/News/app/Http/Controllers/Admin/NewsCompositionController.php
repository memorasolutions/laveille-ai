<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Core\Services\TranslationService;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\CompositionPayloadNormalizer;
use Modules\News\Services\CompositionPromptBuilder;
use Modules\News\Services\NewsImageService;
use Modules\News\Services\SourceMarkdownFetcher;

/**
 * Écran de composition manuelle d'une actualité (Phase A puis Phase B - design doc "Actus -
 * composition manuelle assistée", 2026-08-15). Phase A : sélection d'UNE actualité déjà
 * collectée (réutilisation du composant partagé news-article-picker), édition du titre et du
 * résumé publiés, persistance du texte source collé (jamais publié - voir section 5.2 du design
 * doc et la migration 2026_08_16_150800_add_internal_source_text_to_news_articles). Phase B :
 * génération du prompt de rédaction (generatePrompt, calqué sur ConcentreBuilderController) et
 * fiche de preuve éditoriale (storeProofPair/destroyProofPair, section 7 du design doc et la
 * migration 2026_08_16_160000_add_editorial_proof_pairs_to_news_articles).
 *
 * Complément de conservation (section 5.2, dans update() ci-dessous) : date de capture et
 * empreinte SHA-256 du texte source, calculées automatiquement à chaque collage/modification
 * réelle et qui SURVIVENT à sa suppression - migration
 * 2026_08_16_170000_add_source_provenance_to_news_articles.
 *
 * Phase D (design doc section 5.3/5.4) : prompt d'image fixe (generateImagePrompt, jamais de
 * génération programmée) et dépôt manuel validé du fichier rapporté de Gemini (uploadImage) -
 * traitement délégué à NewsImageService::processFromUploadedFile(), aucun service concurrent.
 *
 * Explicitement HORS PÉRIMÈTRE ici (phase suivante) : extraits invoqués en dehors des paires de
 * preuve, journal de suppression et effacement en cascade du texte intégral (Phase C, au-delà du
 * complément 5.2 déjà couvert ici). La bascule de publication existe déjà ailleurs
 * (Modules\News\Http\Controllers\AdminNewsController::toggleArticle).
 *
 * RÉVISION 2026-08-17 (design doc, section "Révision 2026-08-17 - prompt d'orchestration Claude
 * Code CLI") : generatePrompt() cible désormais Claude Code CLI comme exécutant complet et
 * accepte le texte source EN LIGNE (paramètre source_text, persisté avec la même règle que
 * update() via applySourceProvenance()). La seule porte d'écriture BORNÉE pour l'agent est la
 * commande `php artisan news:apply` (Modules\News\Console\NewsApplyCommand) - cette commande
 * n'écrit jamais is_published/published_at et ne l'a jamais fait.
 *
 * RÉVISION 2026-08-17 (design doc, section "Récupération automatique Markdown + Publier-et-purger
 * (2026-08-17)") : fetchSource() récupère automatiquement le texte source complet en Markdown à
 * la sélection d'une actualité (Modules\News\Services\SourceMarkdownFetcher, HTTP puis repli
 * Puppeteer, jamais de contournement de paywall). publish() EST DÉSORMAIS le SEUL endroit de ce
 * contrôleur qui écrit is_published/published_at (le paragraphe ci-dessus, à propos de
 * generatePrompt()/news:apply, décrivait la porte d'écriture de CONTENU de l'agent - la
 * publication elle-même reste un geste du propriétaire, exclusivement humain, jamais déclenché
 * par l'agent). C'est une exception VOULUE et NOMMÉE, pas un oubli : décision du propriétaire
 * 2026-08-17, arbitrée par le panel de 5 IA - « publier = purger », un seul geste qui bascule
 * is_published, horodate published_at et purge internal_source_text dans la MÊME transaction,
 * plutôt que deux actions séparées (bascule ailleurs dans /admin/news/articles, puis purge
 * manuelle oubliable) qui laisseraient une fenêtre où une fiche publiée garde encore son texte
 * source intégral en base.
 *
 * RÉVISION 2026-08-17 (fin de journée) - décision du propriétaire qui RENVERSE l'arbitrage
 * ci-dessus : l'agent Claude Code CLI publie désormais lui-même la fiche, en toute fin de son
 * prompt d'orchestration, via `php artisan news:apply {id} --publish`
 * (Modules\News\Console\NewsApplyCommand) - PUIS donne au propriétaire le lien public direct
 * pour une inspection APRÈS publication, plutôt qu'avant. Mitigation retenue : cette porte
 * applique EXACTEMENT les mêmes prérequis et la même revalidation que le bouton manuel publish()
 * ci-dessous - les deux chemins délèguent à la même méthode unique
 * NewsArticle::publishReadinessCheck() puis à NewsArticle::publishAndPurgeSource() (DRY strict,
 * voir les doc-blocs de ces deux méthodes). publish() ci-dessous reste le SEUL endroit HTTP qui
 * écrit is_published/published_at ; NewsApplyCommand (--publish) est désormais le SEUL autre
 * endroit du code entier autorisé à le faire - jamais un Eloquent/SQL/tinker direct par l'agent,
 * jamais un autre chemin.
 *
 * ADDENDUM (même jour, découvert en production) : structured_summary (résumé MACHINE de la
 * collecte) affiche EN PRIORITÉ sur summary côté fiche publique - publish() ci-dessous l'efface
 * donc désormais juste avant publishAndPurgeSource() (NewsArticle::logStructuredSummaryOverride(),
 * DRY avec NewsApplyCommand --payload), pour que la composition manuelle soit enfin visible.
 * VOLONTAIREMENT PAS dans update() : l'admin peut retoucher le texte sans forcer la bascule.
 *
 * LOT 4a (design doc "extension de l'écran de composition des actualités", 2026-09-03, section 2
 * et section 11) : l'écran devient une PORTE D'ÉCRITURE de plein droit pour l'humain, avec les
 * mêmes règles que la porte de l'agent (`php artisan news:apply`). update() accepte désormais
 * 'title', 'image_credit', 'nature_original', 'niveau_preuve' (mêmes bornes que
 * NewsApplyCommand, section 2.5) sous un verrou optimiste proportionné ('expected_updated_at',
 * section 2.6, actif SEULEMENT si le payload porte au moins une de ces clés riches - zéro
 * changement de comportement sur le chemin seo_title/summary/internal_source_text déjà stable).
 * storeProofPair() délègue désormais à CompositionPayloadNormalizer::validateProofPair() (section
 * 2.2), qui fusionne ce qui était ici et la boucle équivalente de NewsApplyCommand::
 * normalizeProofPairs() - une seule règle de validation, jamais deux copies qui pourraient
 * diverger.
 *
 * LOT 4b (même design doc, section 2.5 et section 11) : update() accepte en plus 'composed_summary'
 * (fusion sous-clé par sous-clé sur le résumé déjà en base via CompositionPayloadNormalizer::
 * normalizeComposedSummary()/overlayComposedSummary(), même doctrine que NewsApplyCommand
 * --payload) et 'primary_sources' (remplacement complet, plafond 10, via
 * CompositionPayloadNormalizer::normalizePrimarySources()) - les deux rejoignent RICH_FIELDS,
 * donc le verrou optimiste de la section 2.6 s'applique déjà à eux sans rien à changer là. Deux
 * routes dédiées, symétriques de proof-pairs.store/destroy, complètent ce lot :
 * storeRelatedTool()/destroyRelatedTool(), qui délèguent à NewsToolSyncAction::attachBySlug()/
 * detachBySlug() (section 2.4, méthodes promues au Lot 1) - additif/soustractif par slug UNIQUE,
 * jamais un remplacement de la liste complète (contrairement à tool_ids[] de l'écran classique).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class NewsCompositionController extends Controller
{
    /**
     * Clés dont la présence dans le payload de update() active le verrou optimiste (design doc
     * 2026-09-03, section 2.6) : le verrou porte sur la RICHESSE du champ écrit, pas sur l'ordre
     * de livraison des lots. 'composed_summary'/'primary_sources' rejoignent la validation de
     * update() au Lot 4b (voir plus bas) - cette liste était déjà complète depuis le Lot 4a, elle
     * n'a rien à changer ici.
     */
    private const RICH_FIELDS = ['title', 'composed_summary', 'primary_sources', 'image_credit', 'nature_original', 'niveau_preuve'];

    public function __construct(
        private readonly CompositionPromptBuilder $promptBuilder,
        private readonly NewsImageService $imageService,
        private readonly SourceMarkdownFetcher $sourceFetcher,
        private readonly NewsToolSyncAction $toolSync,
    ) {
    }

    public function index(): View
    {
        return view('news::admin.composition-builder', [
            'candidatesEndpoint' => route('admin.news.composition.candidates'),
            // Améliorations en attente (2026-08-17), point 1 - « Créer une fiche depuis un
            // lien », voir createDraft() ci-dessous.
            'createDraftEndpoint' => route('admin.news.composition.create-draft'),
            'showEndpointTemplate' => route('admin.news.composition.show', ['article' => '__SLUG__']),
            'updateEndpointTemplate' => route('admin.news.composition.update', ['article' => '__SLUG__']),
            'deleteSourceTextEndpointTemplate' => route('admin.news.composition.destroy-source-text', ['article' => '__SLUG__']),
            // ── Récupération automatique Markdown + Publier-et-purger (design doc 2026-08-15,
            // révision 2026-08-17) ──
            'fetchSourceEndpointTemplate' => route('admin.news.composition.fetch-source', ['article' => '__SLUG__']),
            'publishEndpointTemplate' => route('admin.news.composition.publish', ['article' => '__SLUG__']),
            // Signature éditoriale (2026-08-21) : bouton « J'ai relu » de l'écran, seul geste
            // humain qui date la relecture d'une fiche DÉJÀ publiée (voir markReviewed()).
            'markReviewedEndpointTemplate' => route('admin.news.composition.mark-reviewed', ['article' => '__SLUG__']),
            'generatePromptEndpointTemplate' => route('admin.news.composition.generate-prompt', ['article' => '__SLUG__']),
            'proofPairsStoreEndpointTemplate' => route('admin.news.composition.proof-pairs.store', ['article' => '__SLUG__']),
            'proofPairsDestroyEndpointTemplate' => route('admin.news.composition.proof-pairs.destroy', ['article' => '__SLUG__', 'pair' => '__PAIR_ID__']),
            'generateImagePromptEndpointTemplate' => route('admin.news.composition.generate-image-prompt', ['article' => '__SLUG__']),
            'uploadImageEndpointTemplate' => route('admin.news.composition.upload-image', ['article' => '__SLUG__']),
            // Lot 4b (design doc 2026-09-03, section 2.5) - outils liés, action immédiate comme
            // les preuves éditoriales (2 routes dédiées, pas dans update()).
            'relatedToolsStoreEndpointTemplate' => route('admin.news.composition.related-tools.store', ['article' => '__SLUG__']),
            'relatedToolsDestroyEndpointTemplate' => route('admin.news.composition.related-tools.destroy', ['article' => '__SLUG__', 'slug' => '__TOOL_SLUG__']),
            'articlesIndexUrl' => route('admin.news.articles.index'),
            // Catalogue des outils publiés pour le sélecteur TomSelect (Lot 4b, section 2.7) -
            // chargé UNE FOIS ici (indépendant de la fiche sélectionnée), jamais recopié dans
            // show() qui, lui, ne renvoie que les outils DÉJÀ liés à la fiche courante. Garde
            // module_enabled : Directory reste désactivable (règle projet "un module retiré ne
            // casse jamais le site").
            'availableTools' => $this->availableToolsForPicker(),
        ]);
    }

    /**
     * Catalogue des outils PUBLIÉS de l'annuaire, sous la forme {slug, label} attendue par le
     * sélecteur TomSelect du panneau « Outils liés » (Lot 4b, design doc 2026-09-03, section
     * 2.7). Le slug voyage dans la locale COURANTE de l'admin (cohérent avec l'URL publique de
     * l'outil) ; attachBySlug()/detachBySlug() résolvent de toute façon contre TOUTES les
     * traductions du slug (Modules\News\Actions\NewsToolSyncAction), donc ce choix d'affichage
     * n'a aucune incidence sur ce qui est réellement accepté à l'écriture.
     *
     * @return array<int, array{slug: string, label: string}>
     */
    private function availableToolsForPicker(): array
    {
        if (! class_exists(\Modules\Directory\Models\Tool::class)) {
            return [];
        }

        $locale = app()->getLocale();

        return \Modules\Directory\Models\Tool::published()
            ->get(['id', 'slug', 'name'])
            ->map(fn (\Modules\Directory\Models\Tool $t) => [
                'slug' => $t->getTranslation('slug', $locale, false) ?: (string) $t->slug,
                'label' => $t->getTranslation('name', $locale, false)
                    ?: $t->getTranslation('name', 'fr_CA', false)
                    ?: $t->getTranslation('name', 'en', false)
                    ?: (string) $t->slug,
            ])
            ->sortBy('label', SORT_FLAG_CASE | SORT_STRING)
            ->values()
            ->all();
    }

    /**
     * Liste des actualités déjà collectées, pour la colonne "disponibles" du composant partagé
     * news-article-picker.js (voir public/assets/admin/news-article-picker.js). Même forme JSON
     * que ConcentreBuilderController::newsForWeek(), sans le regroupement par acteur (inutile ici
     * : une seule actualité est composée à la fois, pas de tri par cluster à faire).
     */
    public function candidates(): JsonResponse
    {
        // Filtre sur created_at (le moment où NOUS avons collecté), et non sur pub_date (la date
        // annoncée par la source). Demande du 2026-08-23 : « je veux les articles du jour
        // seulement ». Une source date souvent son article de la veille au soir ; filtrer sur
        // pub_date ferait disparaître de l'écran un article récolté ce matin, et la purge
        // nocturne le supprimerait avant même qu'il ait pu être vu. L'affichage et le tri
        // restent sur pub_date, qui est ce que le lecteur comprend.
        $fuseau = config('app.timezone', 'America/Toronto');
        $jour = now($fuseau)->toDateString();

        // ACTION : plafond de 200 RETIRÉ (demande du propriétaire, 2026-08-24) - 452 des 652
        // actualités collectées le 23 août restaient invisibles derrière cette limite. La
        // traduction n'est plus le facteur bloquant : elle est désormais PRÉCALCULÉE en base par
        // Modules\News\Console\TranslateTitlesCommand (voir titresTraduits() ci-dessous), qui ne
        // tente plus qu'un rattrapage synchrone borné à 40 titres, jamais l'ensemble du lot.
        // MCP: SELF (<5 lignes)
        // RAISON: design doc "Actus - composition manuelle assistée", section traduction précalculée.
        $pourLeJour = static fn (string $date) => NewsArticle::query()
            ->with('source')
            ->whereDate('created_at', $date)
            ->orderByDesc('pub_date')
            ->get();

        $articles = $pourLeJour($jour);
        $estRepli = false;

        // La collecte tourne à l'heure (cron `news:fetch`, minute 15) : entre minuit et le premier
        // passage, la journée est vide. Plutôt qu'un écran vide et muet, on montre le dernier jour
        // qui a des articles ET on le DIT - `est_repli` permet à l'écran de l'afficher.
        if ($articles->isEmpty()) {
            $dernierJour = NewsArticle::query()->max('created_at');
            if ($dernierJour) {
                $jour = \Illuminate\Support\Carbon::parse($dernierJour)->setTimezone($fuseau)->toDateString();
                $articles = $pourLeJour($jour);
                $estRepli = $articles->isNotEmpty();
            }
        }

        $traduction = $this->titresTraduits($articles);

        return response()->json([
            'jour_affiche' => $jour,
            'est_repli' => $estRepli,
            'traduction_statut' => $traduction['statut'],
            'traduction_motif' => $traduction['motif'],
            'items' => $articles->map(fn (NewsArticle $a) => [
                'id' => $a->id,
                'title' => $traduction['titres'][$a->id] ?? ($a->seo_title ?: $a->title),
                'title_original' => $a->title,
                'slug' => $a->slug,
                'site_url' => url('/actualites/'.$a->slug),
                'source_url' => $a->url,
                'summary' => mb_strimwidth((string) ($a->summary ?? ''), 0, 220, '…'),
                'pub_date' => $a->pub_date?->toIso8601String(),
                'pub_date_short' => $a->pub_date?->isoFormat('D MMM HH:mm'),
                'category_tag' => $a->category_tag,
                'image_url' => $a->image_url,
                'source_name' => $a->source?->name,
                'source_language' => $a->source?->language ?? 'unknown',
                // Filtre par compagnie d'IA (2026-08-29, demande du fondateur) - null tant que la
                // source d'origine n'est pas taggée par Modules\News\Database\Seeders\
                // OfficialCompanySourcesSeeder ; le filtre côté client (news-article-picker.js)
                // s'auto-masque déjà quand aucun article n'a de compagnie renseignée.
                'source_company' => $a->source?->company,
                'source_is_official' => (bool) ($a->source?->is_official ?? false),
                'actor_cluster' => null,
                'cluster_color' => null,
                'favicon' => $a->url ? 'https://www.google.com/s2/favicons?domain='.parse_url($a->url, PHP_URL_HOST).'&sz=32' : null,
                // Réutilise le badge "🔁 déjà utilisée" du partial partagé pour signaler qu'une
                // composition a déjà été commencée sur cette fiche (texte source déjà collé).
                'already_used' => filled($a->internal_source_text),
            ])->values(),
        ]);
    }

    /**
     * Nombre maximum de titres traduits À LA VOLÉE par requête d'écran (rattrapage synchrone).
     * La très grande majorité des titres arrive déjà traduite (colonne 'title_fr', écrite en
     * amont par Modules\News\Console\TranslateTitlesCommand) - cette borne ne couvre que ce que
     * le passage horaire n'a pas encore rattrapé, pour que l'écran reste rapide même avec
     * plusieurs centaines de fiches du jour (design doc, section traduction précalculée,
     * 2026-08-24).
     */
    private const RATTRAPAGE_SYNCHRONE_MAX = 40;

    /**
     * Donne aux actualités dont la source n'est pas francophone leur meilleur titre français
     * disponible.
     *
     * Demande du fondateur, réitérée le 2026-08-23 : lire une liste moitié anglaise ralentit le
     * tri éditorial. Le titre ORIGINAL reste rendu séparément (`title_original`), il n'est jamais
     * perdu ni écrasé en base - cette traduction est un confort d'affichage, pas une écriture
     * (sauf le rattrapage ci-dessous, qui n'écrit rien non plus : seule la commande planifiée
     * persiste 'title_fr').
     *
     * RÉVISION 2026-08-24 (retrait du plafond de candidates(), voir ci-dessus) : traduire
     * l'ensemble du lot à la volée sur le chemin synchrone de l'écran a immobilisé l'écran une
     * première fois (2026-08-23, budget dépassé). Cette méthode ne traduit donc plus l'ensemble :
     * elle LIT d'abord 'title_fr' (déjà écrit par Modules\News\Console\TranslateTitlesCommand,
     * planifiée toutes les heures), et ne tente un appel réseau QUE sur ce qui n'a pas encore de
     * 'title_fr', borné à RATTRAPAGE_SYNCHRONE_MAX titres. Le reste s'affiche en version
     * originale et sera rattrapé au prochain passage horaire - jamais de blocage de l'écran, peu
     * importe le nombre de fiches du jour.
     *
     * Si quoi que ce soit échoue pendant le rattrapage, on rend les originaux ET le statut le
     * dit, pour que l'écran puisse afficher « traduction indisponible » plutôt que de laisser
     * croire à un oubli - c'est précisément l'ambiguïté qui a fait reposer la question du
     * 2026-08-23.
     *
     * @param  \Illuminate\Support\Collection<int, NewsArticle>  $articles
     * @return array{titres: array<int, string>, statut: string, motif: string|null}
     */
    private function titresTraduits(\Illuminate\Support\Collection $articles): array
    {
        $aTraduire = $articles->filter(static function (NewsArticle $a): bool {
            $langue = mb_strtolower((string) ($a->source?->language ?? ''));

            // Le seo_title est déjà une réécriture éditoriale française : ne pas y toucher.
            return $langue !== '' && ! str_starts_with($langue, 'fr') && blank($a->seo_title);
        })->values();

        if ($aTraduire->isEmpty()) {
            return ['titres' => [], 'statut' => 'ok', 'motif' => null];
        }

        // Ce qui a déjà 'title_fr' (posé par la commande planifiée) est lu directement, jamais
        // retraduit ni renvoyé au fournisseur.
        $parId = [];
        $sansTitleFr = collect();
        foreach ($aTraduire as $article) {
            if (filled($article->title_fr)) {
                $parId[$article->id] = $article->title_fr;
            } else {
                $sansTitleFr->push($article);
            }
        }

        if ($sansTitleFr->isEmpty()) {
            return ['titres' => $parId, 'statut' => 'ok', 'motif' => null];
        }

        // Rattrapage synchrone BORNÉ : le reste (au-delà de la borne) s'affiche en version
        // originale, la commande planifiée horaire le rattrapera au prochain passage.
        $rattrapage = $sansTitleFr->take(self::RATTRAPAGE_SYNCHRONE_MAX)->values();

        // Rien de ce qui touche à la traduction ne doit pouvoir abattre l'écran. Le 2026-08-23,
        // une cascade sans budget a bloqué ce point d'accès au-delà de la coupure de Cloudflare :
        // l'écran affichait « 0 actualité » alors que 526 articles étaient collectés. Le budget
        // est désormais borné côté service, ET l'appel lui-même ne porte plus que sur au plus 40
        // titres (jamais l'ensemble du lot) ; ce filet attrape ce qui resterait, et l'écran
        // répond toujours - avec les titres originaux et le motif affiché, jamais avec une page
        // vide.
        try {
            $resultat = TranslationService::translateBatch(
                $rattrapage->map(static fn (NewsArticle $a) => (string) $a->title)->all()
            );
        } catch (\Throwable $e) {
            return ['titres' => $parId, 'statut' => 'indisponible', 'motif' => $e->getMessage()];
        }

        foreach ($rattrapage as $i => $article) {
            $traduit = $resultat['titres'][$i] ?? null;
            if (is_string($traduit) && trim($traduit) !== '') {
                $parId[$article->id] = $traduit;
            }
        }

        return ['titres' => $parId, 'statut' => $resultat['statut'], 'motif' => $resultat['motif']];
    }

    /**
     * ACTION : « Créer une fiche depuis un lien » (design doc "Actus - composition manuelle
     * assistée" 2026-08-15, section "Améliorations en attente", point 1) - porte web de
     * Modules\News\Models\NewsArticle::createManualDraft(), SEULE implémentation (DRY strict),
     * réutilisée TELLE QUELLE par Modules\News\Console\NewsCreateDraftCommand (`php artisan
     * news:create-draft`), le point d'entrée console équivalent. Idempotente par URL : un
     * second appel sur la même URL renvoie la fiche déjà créée (created: false) plutôt que d'en
     * créer une seconde.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: DRY explicite exigé par le mandat, aucune logique dupliquée entre les deux portes.
     */
    public function createDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2000', 'url'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        ['article' => $article, 'created' => $created] = NewsArticle::createManualDraft(
            $validated['url'],
            $validated['title'] ?? null
        );

        Log::channel('composition')->info('createDraft (écran) - création manuelle', [
            'article_id' => $article->id,
            'url' => $article->url,
            'created' => $created,
        ]);

        return response()->json([
            'id' => $article->id,
            'slug' => $article->slug,
            'url' => $article->url,
            'created' => $created,
            'mini_prompt' => '/actu2 '.$article->url.' fiche:'.$article->id,
        ]);
    }

    /**
     * Détail complet d'UNE actualité pour préremplir le formulaire de composition. Volontairement
     * distinct de candidates() (qui tronque le résumé et n'inclut jamais le texte source) : le
     * texte intégral n'est transmis au navigateur admin qu'au moment où l'actualité est
     * effectivement sélectionnée, jamais dans la liste en vrac - minimisation cohérente avec le
     * design doc section 4.
     */
    public function show(NewsArticle $article): JsonResponse
    {
        return response()->json([
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'seo_title' => $article->seo_title,
            'summary' => $article->summary,
            'internal_source_text' => $article->internal_source_text,
            // Fiche de preuve éditoriale (Phase B) : présente ici, JAMAIS dans candidates() -
            // même règle que internal_source_text ci-dessus.
            'editorial_proof_pairs' => $article->editorial_proof_pairs ?? [],
            // Complément de conservation (design doc section 5.2) : présent ici (admin
            // uniquement), jamais dans candidates() ni dans une vue publique - même règle que
            // internal_source_text et editorial_proof_pairs ci-dessus.
            'source_captured_at' => $article->source_captured_at?->toIso8601String(),
            'source_content_hash' => $article->source_content_hash,
            // ACTION : bonification panel 2026-08-17 (soir) - contrairement aux champs internes
            // ci-dessus, primary_sources/image_credit ne sont PAS secrets (ils sont affichés tels
            // quels sur la fiche publique) : présents ici pour le bloc lecture seule du volet
            // "Édition manuelle (filet de secours)" de composition-builder.blade.php - jamais
            // édités depuis cet écran, seul NewsApplyCommand (--payload) les écrit.
            // MCP: SELF (<5 lignes)
            // RAISON: design doc, section "Bonification panel 2026-08-17 (soir)".
            'primary_sources' => $article->primary_sources ?? [],
            'image_credit' => $article->image_credit,
            // ACTION : Lot 4b (design doc 2026-09-03, section 2.8) - composed_summary rejoint le
            // bloc lecture pour devenir ÉDITABLE (voir update() plus bas). C'est le MÊME tableau
            // que structured_summary (résumé MACHINE OU composé - NewsArticle::hasComposedSummary()
            // reste le seul point qui distingue les deux) : exposé sous ce nom de payload pour que
            // le panneau de composition n'ait jamais besoin de connaître le nom de la colonne
            // interne, exactement comme update() ci-dessous accepte 'composed_summary' en entrée
            // et écrit 'structured_summary' en sortie.
            // MCP: SELF (<5 lignes)
            // RAISON: design doc 2026-09-03, section 2.5 - même nom des deux côtés du contrat HTTP.
            'composed_summary' => is_array($article->structured_summary) ? $article->structured_summary : [],
            // ACTION : garde-fou anti-écrasement silencieux (mesuré en revue de ce lot) - le
            // client a besoin de savoir si ce résumé est DÉJÀ composé (humain/agent) ou encore
            // MACHINE (collecte RSS, mêmes noms de sous-clés que composed_summary - AiSummaryService
            // écrit hook/key_points/why_important/angle_qc_ca) pour décider s'il doit envoyer
            // 'composed_summary' à update() : hasComposedSummary() est le point UNIQUE de cette
            // distinction (DRY, même méthode que NewsApplyCommand). Sans ce signal, un
            // enregistrement qui ne touche QUE image_credit enverrait quand même un
            // composed_summary reconstruit depuis les 8 champs pré-remplis, et
            // overlayComposedSummary() poserait composed:true sur un résumé encore machine -
            // jamais une perte de contenu (les valeurs machine sont réécrites identiques), mais un
            // changement de statut interne fait dans le dos de l'admin, qui protégerait ensuite ce
            // résumé d'un remplacement légitime par un futur `summary` (même piège que celui déjà
            // documenté deux fois pour ce module - CONTRAINTES-SOUS-AGENTS.md, "une nouvelle clé
            // est soit du contenu, soit une méta-donnée").
            // MCP: SELF (<10 lignes utiles)
            // RAISON: zéro casse (CLAUDE.md règle 1) - jamais de changement de statut hors du geste explicite de l'admin.
            'composed_summary_active' => $article->hasComposedSummary(),
            // Outils DÉJÀ liés (Lot 4b, section 2.7) - jamais la liste des outils disponibles
            // (celle-ci voyage une seule fois, indépendante de la fiche, voir index() et
            // availableToolsForPicker() ci-dessus). Le slug renvoyé ici est celui qui identifie
            // réellement le lien pour destroyRelatedTool() (route DELETE .../related-tools/{slug}).
            // MCP: SELF (<5 lignes)
            // RAISON: design doc 2026-09-03, section 2.8 - "outils déjà liés".
            'related_tools' => $this->relatedToolsPayload($article),
            // ACTION : Lot 4a (design doc 2026-09-03, section 2.8) - nature_original/niveau_preuve
            // rejoignent le bloc lecture pour devenir ÉDITABLES (voir update() plus bas) ; les
            // listes d'options voyagent avec CHAQUE fiche plutôt que par un endpoint séparé, pour
            // que le <select> du panneau de composition ne recopie JAMAIS ces valeurs en dur côté
            // Blade (source unique : NewsArticle::NATURE_ORIGINAL_VALUES/NIVEAU_PREUVE_VALUES).
            // MCP: SELF (<5 lignes)
            // RAISON: design doc 2026-09-03, section 2.7 - "jamais recopiés en dur dans le Blade".
            'nature_original' => $article->nature_original,
            'niveau_preuve' => $article->niveau_preuve,
            'nature_original_options' => NewsArticle::NATURE_ORIGINAL_VALUES,
            'niveau_preuve_options' => NewsArticle::NIVEAU_PREUVE_VALUES,
            'is_published' => (bool) $article->is_published,
            // ACTION : signature éditoriale (2026-08-21) - l'écran doit savoir si la fiche est
            // DÉJÀ signée, pour n'offrir le bouton « J'ai relu » qu'à celles qui ne le sont pas.
            // MCP: SELF (<5 lignes)
            // RAISON: la signature est un geste humain unique, jamais reposé deux fois.
            'reviewed_at' => $article->reviewed_at?->toIso8601String(),
            'reviewed_by' => $article->reviewed_by,
            'site_url' => url('/actualites/'.$article->slug),
            // Lien vers l'article ORIGINAL chez l'éditeur (demande du propriétaire 2026-08-17,
            // manque « source formelle » pointé par le panel) : resolved_url prime sur url
            // (les flux RSS servent souvent une URL de redirection).
            'source_url' => $article->resolved_url ?: $article->url,
            'updated_at' => $article->updated_at?->toIso8601String(),
            // Standard d'images (design doc section 5.4) : indique au front si une image (dépôt
            // manuel ou image de repli générée) existe déjà pour cette fiche, pour l'aperçu.
            'has_image' => $this->imageService->exists($article->id),
            'image_url' => $this->imageService->exists($article->id)
                ? asset($this->imageService->getPublicPath($article->id)).'?v='.($article->updated_at?->timestamp ?? time())
                : null,
        ]);
    }

    /**
     * Sauvegarde le titre publié (seo_title), le résumé publié (summary), le texte source
     * interne (internal_source_text) et, depuis le Lot 4a (design doc 2026-09-03, section 2.5),
     * les champs riches 'title', 'image_credit', 'nature_original' et 'niveau_preuve' - mêmes
     * bornes et mêmes règles de validation que la porte de l'agent (NewsApplyCommand --payload).
     * N'écrit jamais dans 'description' (colonne purgée, ne plus jamais réutiliser - voir la
     * migration).
     *
     * ACTION : verrou optimiste (design doc, section 2.6) - actif UNIQUEMENT si le payload porte
     * au moins une clé de self::RICH_FIELDS ; les trois champs historiques continuent de
     * s'écrire sans aucun verrou, comportement observable strictement inchangé pour eux.
     * MCP: SELF (<5 lignes utiles, le reste est validation/contrat HTTP)
     * RAISON: design doc 2026-09-03, section 2.6 - le risque d'écrasement concurrent humain/agent
     *         n'existe qu'à partir des champs que ce lot ouvre à l'admin.
     */
    public function update(Request $request, NewsArticle $article): JsonResponse
    {
        // 'sometimes' avant 'nullable' : un champ ABSENT du corps JSON reste absent de
        // $validated (donc jamais écrasé à null) ; un champ présent mais vide/null est bien
        // accepté et écrit. Sans 'sometimes', un appel qui n'enverrait que internal_source_text
        // aurait silencieusement vidé seo_title et summary.
        $validated = $request->validate([
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
            // Borne large mais finie : évite un payload démesuré côté admin sans empêcher de
            // coller l'intégralité d'un article de fond.
            'internal_source_text' => ['sometimes', 'nullable', 'string', 'max:200000'],
            // ── Lot 4a (design doc 2026-09-03, section 2.5) ──────────────────────────────────
            // Mêmes bornes que NewsApplyCommand.php : 200 pour 'title' (pas 255, comme
            // 'seo_title'), 255 pour 'image_credit'. 'nullable' laisse passer un titre vide
            // JUSQU'AU garde ci-dessous (colonne 'title' NOT NULL en base - un titre vide y
            // échouerait en 500 plutôt qu'en 422 propre sans ce garde explicite).
            'title' => ['sometimes', 'nullable', 'string', 'max:200'],
            'image_credit' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nature_original' => ['sometimes', 'nullable', 'string'],
            'niveau_preuve' => ['sometimes', 'nullable', 'string'],
            // ── Lot 4b (design doc 2026-09-03, section 2.5) ──────────────────────────────────
            // 'nullable' sur composed_summary couvre uniquement la FORME du payload JSON (un
            // objet ou son absence explicite null) - un null de premier niveau est traité plus
            // bas comme un NO-OP (rien à fusionner), jamais comme un ordre d'effacer tout le
            // résumé composé : la fiche de test le prouve. Vider une SOUS-clé précise se fait en
            // envoyant l'objet complet avec cette sous-clé à null (même doctrine que le CLI,
            // CompositionPayloadNormalizer::normalizeComposedSummary()). primary_sources n'est PAS
            // nullable (remplacement complet, une fiche sans aucune source envoie [], jamais
            // null) ; max:10 miroir serveur du plafond déjà imposé par normalizePrimarySources().
            'composed_summary' => ['sometimes', 'nullable', 'array'],
            'primary_sources' => ['sometimes', 'array', 'max:10'],
            // Verrou optimiste (2.6) : chaîne ISO 8601 comparée telle quelle à l'updated_at
            // courant de la fiche - jamais interprétée comme une date par Laravel (une
            // différence de fuseau/format ferait alors échouer une comparaison pourtant valide).
            'expected_updated_at' => ['sometimes', 'nullable', 'string'],
        ]);

        if (array_intersect(array_keys($validated), self::RICH_FIELDS) !== []) {
            if (($validated['expected_updated_at'] ?? null) !== $article->updated_at?->toIso8601String()) {
                return response()->json([
                    'error' => "Cette fiche a été modifiée depuis l'ouverture de cet écran - recharge-la avant d'enregistrer.",
                ], 409);
            }
        }
        unset($validated['expected_updated_at']);

        // ACTION : nature_original/niveau_preuve (2.2 - "choix de ne pas envelopper cette
        // ligne") - une valeur NULLE explicite est un retrait volontaire (le <select> du panneau
        // propose une option vide pour corriger une classification erronée) et saute la
        // vérification ; une valeur NON NULLE doit appartenir au vocabulaire du modèle, sans
        // quoi la fiche porterait une étiquette qu'aucune vue ne sait traduire.
        // MCP: SELF (<5 lignes)
        // RAISON: design doc 2026-09-03, section 2.5 - même garde-fou que NewsApplyCommand,
        //         élargi ici pour permettre à un humain d'effacer une classification erronée.
        if (array_key_exists('nature_original', $validated)
            && $validated['nature_original'] !== null
            && ! array_key_exists($validated['nature_original'], NewsArticle::NATURE_ORIGINAL_VALUES)) {
            return response()->json([
                'error' => 'nature_original invalide (attendu : '.implode(', ', array_keys(NewsArticle::NATURE_ORIGINAL_VALUES)).').',
            ], 422);
        }

        if (array_key_exists('niveau_preuve', $validated)
            && $validated['niveau_preuve'] !== null
            && ! array_key_exists($validated['niveau_preuve'], NewsArticle::NIVEAU_PREUVE_VALUES)) {
            return response()->json([
                'error' => 'niveau_preuve invalide (attendu : '.implode(', ', array_keys(NewsArticle::NIVEAU_PREUVE_VALUES)).').',
            ], 422);
        }

        // ACTION : composed_summary (Lot 4b, section 2.5) - même service que NewsApplyCommand
        // --payload (CompositionPayloadNormalizer::normalizeComposedSummary() puis
        // overlayComposedSummary()), jamais une seconde implémentation. Un objet composed_summary
        // ABSENT du payload ne touche à rien (comportement 'sometimes' déjà en place) ; un objet
        // présent mais littéralement `null` est un NO-OP délibéré, pas un effacement - le
        // formulaire envoie toujours un objet aux 8 sous-clés, jamais null au premier niveau ;
        // traiter null comme "efface tout structured_summary" romprait la règle absolue "zéro
        // suppression de données utilisateur" sur un signal ambigu (ex. un état non encore
        // hydraté côté client). Effacer une SOUS-clé précise reste possible : voir la note de la
        // règle de validation ci-dessus.
        // MCP: SELF (<5 lignes utiles)
        // RAISON: design doc 2026-09-03, section 2.5 - "fusion sous-clé par sous-clé identique au CLI".
        if (array_key_exists('composed_summary', $validated) && $validated['composed_summary'] !== null) {
            $composedResult = CompositionPayloadNormalizer::normalizeComposedSummary($validated['composed_summary']);
            if (! $composedResult['ok']) {
                return response()->json(['error' => $composedResult['error']], 422);
            }

            $existing = $article->hasComposedSummary() ? (array) $article->structured_summary : [];
            $validated['structured_summary'] = CompositionPayloadNormalizer::overlayComposedSummary($existing, $composedResult['value']);
        }
        unset($validated['composed_summary']);

        // ACTION : primary_sources (Lot 4b, section 2.5) - remplacement complet (pas une fusion,
        // contrairement à composed_summary ci-dessus), même normalisation que le CLI
        // (CompositionPayloadNormalizer::normalizePrimarySources(), plafond 10 déjà imposé une
        // première fois par la règle de validation 'max:10' au-dessus - défense en profondeur,
        // jamais la seule garde).
        // MCP: SELF (<5 lignes)
        // RAISON: design doc 2026-09-03, section 2.5 - "même sémantique que normalizePrimarySources()".
        if (array_key_exists('primary_sources', $validated)) {
            $sourcesResult = CompositionPayloadNormalizer::normalizePrimarySources($validated['primary_sources']);
            if (! $sourcesResult['ok']) {
                return response()->json(['error' => $sourcesResult['error']], 422);
            }
            $validated['primary_sources'] = $sourcesResult['value'];
        }

        // ACTION : title (2.5) - le slug ne doit JAMAIS bouger sur une fiche déjà publiée. C'est
        // la condition --enrich du CLI (NewsApplyCommand.php:382), transposée : ici, la fiche
        // déjà publiée EST la condition (aucun flag à passer). Un titre vide/blanc est refusé
        // explicitement (422) plutôt que laissé heurter la contrainte NOT NULL de la colonne en
        // base (500 muette) - la colonne ne peut de toute façon jamais être vidée.
        // MCP: SELF (<5 lignes utiles)
        // RAISON: design doc 2026-09-03, section 2.5.
        if (array_key_exists('title', $validated)) {
            if ($validated['title'] === null || trim($validated['title']) === '') {
                return response()->json([
                    'error' => 'title doit être une chaîne non vide de 200 caractères maximum.',
                ], 422);
            }

            $validated['title'] = lv_strip_em_dash(trim($validated['title']));
            if (! $article->is_published) {
                $validated['slug'] = NewsArticle::generateUniqueSlug($validated['title'], $article->id);
            }
        }

        $this->applySourceProvenance($validated, $article);

        $article->update($validated);

        $frais = $article->fresh();

        return response()->json([
            'success' => true,
            'updated_at' => $frais->updated_at?->toIso8601String(),
            // Le slug suit le titre tant que la fiche n'est PAS publiée (garde ci-dessus). Sans ce
            // renvoi, l'interface garde l'ancien slug et son PROCHAIN enregistrement part vers une
            // URL qui ne résout plus : 404 « Ressource introuvable » et modifications perdues dès
            // le second enregistrement consécutif. Mesuré en QC visuelle le 2026-09-03 ; le défaut
            // est actif en production depuis v1.250.0, où l'écriture du titre est apparue.
            'slug' => $frais->slug,
        ]);
    }

    /**
     * ACTION : complément de conservation (design doc section 5.2) - date de capture et
     * empreinte SHA-256 recalculées UNIQUEMENT quand un texte source non vide est effectivement
     * collé ou modifié (hash différent de celui déjà en base), jamais sur un texte vide ou
     * inchangé. Extraite d'update() (révision 2026-08-17) pour être réutilisée telle quelle par
     * generatePrompt() ci-dessous, qui accepte désormais le texte source EN LIGNE - DRY : une
     * seule règle de provenance, deux points d'entrée. Un vidage manuel du champ laisse ces deux
     * valeurs INTACTES, exactement comme la suppression dédiée destroySourceText() - même
     * garde-fou "survit à la suppression" (5.2), quel que soit le chemin emprunté.
     *
     * ACTION : implémentation /actu2 (2026-08-17) - le calcul lui-même est désormais DÉLÉGUÉ à
     * NewsArticle::sourceProvenanceUpdates(), extraite pour être réutilisée SANS DUPLICATION par
     * Modules\News\Console\NewsSourceCommand (porte serveur du skill /actu2). Cette méthode reste
     * ici uniquement pour son rôle de garde d'entrée (champ absent ou vide → aucun calcul).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: DRY explicite, une seule implémentation de la provenance à travers le code.
     */
    private function applySourceProvenance(array &$fields, NewsArticle $article): void
    {
        if (! array_key_exists('internal_source_text', $fields) || blank($fields['internal_source_text'])) {
            return;
        }

        $fields = array_merge($fields, $article->sourceProvenanceUpdates($fields['internal_source_text']));
    }

    /**
     * Supprime UNIQUEMENT le texte source interne, à tout moment, sans toucher au reste de la
     * fiche (titre, résumé, statut de publication) - décision 5.2 du design doc : "supprimable à
     * tout moment". Action dédiée plutôt que surchargée dans update() pour rester testable en
     * isolation et pour que le bouton "Supprimer le texte source" ait un contrat sans ambiguïté.
     */
    public function destroySourceText(NewsArticle $article): JsonResponse
    {
        // ACTION : n'écrit QUE 'internal_source_text'. 'editorial_proof_pairs' est
        // volontairement intouché : les extraits déjà déclarés dans les paires de preuve
        // restent les extraits INVOQUÉS, ce qui rend le texte intégral supprimable sans perdre
        // la preuve éditoriale (design doc section 5.2 et 7).
        // MCP: SELF (<5 lignes)
        // RAISON: garde-fou explicite, la même méthode gérait déjà seulement ce champ.
        $article->update(['internal_source_text' => null]);

        // ACTION : Lot 4a (design doc 2026-09-03, section 2.6) - 'updated_at' renvoyé pour que le
        // front puisse le reporter sur selectedArticle : sans cela, cette écriture (comme celle
        // de fetchSource() et des paires de preuve, voir plus bas) ferait échouer le PROCHAIN
        // update() riche avec un 409 auto-infligé, alors qu'aucun autre acteur n'a touché la
        // fiche.
        // MCP: SELF (<5 lignes)
        // RAISON: le verrou optimiste (2.6) doit détecter une écriture EXTERNE, jamais la propre
        //         écriture de l'onglet ouvert.
        return response()->json([
            'success' => true,
            'updated_at' => $article->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Récupération automatique du texte source en Markdown (design doc, section "Récupération
     * automatique Markdown + Publier-et-purger (2026-08-17)") - délègue entièrement à
     * SourceMarkdownFetcher::fetch(). Refuse d'écraser un texte source déjà présent sauf
     * confirmation explicite ('replace'), pour ne jamais perdre en silence un texte collé ou
     * retouché à la main.
     *
     * set_time_limit(40) explicite : le repli Puppeteer peut approcher les 20 secondes à lui
     * seul, et php-fpm coupe sinon la requête sans message exploitable côté admin (504 muette,
     * arbitrage du panel de 5 IA).
     */
    public function fetchSource(Request $request, NewsArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'replace' => ['sometimes', 'boolean'],
        ]);
        $replace = (bool) ($validated['replace'] ?? false);

        if (filled($article->internal_source_text) && ! $replace) {
            return response()->json([
                'error' => 'un texte source existe déjà',
            ], 409);
        }

        $url = $article->resolved_url ?: $article->url;
        if (blank($url)) {
            return response()->json([
                'error' => "Cette fiche n'a pas d'URL source à récupérer.",
            ], 422);
        }

        // Jamais en console : en CLI (tests Pest, artisan) set_time_limit plafonne le PROCESSUS
        // entier - la suite complète mourait 40 s après le premier test passé ici (fatal
        // « Maximum execution time » élucidé le 2026-08-17). La 504 muette n'existe qu'en web.
        if (! app()->runningInConsole()) {
            set_time_limit(40);
        }

        $result = $this->sourceFetcher->fetch($url, $article->title);

        Log::channel('composition')->info('fetch-source - tentative', [
            'article_id' => $article->id,
            'success' => $result['success'],
            'method' => $result['acquisition']['method'] ?? null,
            'http_status' => $result['acquisition']['http_status'] ?? null,
            'error' => $result['error'],
        ]);

        if (! $result['success']) {
            // ACTION : échec → ne persiste RIEN (aucun champ, ni internal_source_text ni
            // source_acquisition) - un texte source déjà présent (cas 'replace') reste intact.
            // MCP: SELF (<5 lignes)
            // RAISON: garde-fou explicite du mandat, tout-ou-rien.
            return response()->json(['error' => $result['error']], 422);
        }

        $update = [
            'internal_source_text' => $result['markdown'],
            'source_acquisition' => $result['acquisition'],
        ];
        $this->applySourceProvenance($update, $article);
        $article->update($update);

        // ACTION : Lot 4a (design doc 2026-09-03, section 2.6) - 'updated_at' renvoyé, même
        // raison que destroySourceText() ci-dessus. Ce point compte le PLUS : loadArticle()
        // (front) appelle fetchSource() AUTOMATIQUEMENT dès qu'une fiche sans texte source est
        // ouverte - sans ce report, la toute première sauvegarde de champs riches sur une fiche
        // fraîchement collectée échouerait TOUJOURS en 409, alors que personne d'autre n'a rien
        // modifié.
        // MCP: SELF (<5 lignes)
        // RAISON: le verrou optimiste (2.6) doit détecter une écriture EXTERNE, jamais la propre
        //         écriture automatique de l'écran qu'on vient d'ouvrir.
        return response()->json([
            'success' => true,
            'markdown' => $result['markdown'],
            'acquisition' => $result['acquisition'],
            'updated_at' => $article->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Génère le prompt d'orchestration Claude Code CLI (Phase B, design doc section 5.1 et 7,
     * révision 2026-08-17) à partir du texte source déjà collé, du titre de travail et d'un
     * angle éditorial optionnel. Calqué sur ConcentreBuilderController::generate() : aucune
     * écriture "métier" ici (le seul écrit possible est la persistance du texte source lui-même,
     * identique à update()) - l'écran reste un assistant de composition, jamais un générateur
     * (section 5.3 du design doc).
     *
     * ACTION : accepte désormais 'source_text' EN LIGNE (révision 2026-08-17) - corrige le
     * blocage "Colle d'abord le texte source" quand l'admin colle le texte puis clique
     * directement sur Générer sans passer par Enregistrer d'abord. S'il est fourni et non blanc,
     * il est persisté AVANT génération avec EXACTEMENT la même logique que update()
     * (applySourceProvenance ci-dessus), puis le garde 422 s'applique au champ persisté - jamais
     * à un état intermédiaire non sauvegardé.
     * MCP: SELF (<5 lignes)
     * RAISON: réutilise applySourceProvenance() déjà extraite pour update(), aucune divergence
     * de règle entre les deux points d'entrée.
     */
    public function generatePrompt(Request $request, NewsArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'angle' => ['nullable', 'string', 'max:500'],
            'source_text' => ['nullable', 'string', 'max:200000'],
        ]);

        if (filled($validated['source_text'] ?? null)) {
            $update = ['internal_source_text' => $validated['source_text']];
            $this->applySourceProvenance($update, $article);
            $article->update($update);
        }

        if (blank($article->internal_source_text)) {
            return response()->json([
                'error' => 'Colle d\'abord le texte source de cette actualité avant de générer le prompt.',
            ], 422);
        }

        // Backfill défensif : garantit que l'empreinte utilisée dans le prompt (que
        // NewsApplyCommand comparera au mot près) est TOUJOURS celle persistée en base, même
        // pour une fiche dont le texte source aurait été inséré avant l'ajout du complément de
        // conservation (section 5.2) ou par un chemin autre que update()/ce contrôleur.
        if (blank($article->source_content_hash)) {
            $article->update([
                'source_content_hash' => hash('sha256', $article->internal_source_text),
                'source_captured_at' => $article->source_captured_at ?? now('America/Toronto'),
            ]);
        }

        $prompt = $this->promptBuilder->build($article, $validated['angle'] ?? '');

        return response()->json([
            'success' => true,
            'prompt' => $prompt,
            'token_estimate' => (int) ceil(mb_strlen($prompt) / 4),
        ]);
    }

    /**
     * Ajoute une paire de la fiche de preuve éditoriale (Phase B, section 7 du design doc) :
     * une phrase du résumé publié, mise en regard d'un extrait exact du texte source, avec une
     * décision binaire fait/analyse. Une paire déclarée « fait » DOIT être une sous-chaîne
     * exacte (après normalisation raisonnable, cf. EditorialProofNormalizer) du texte source
     * collé - c'est ce qui rend la paraphrase mécaniquement impossible sur les passages déclarés
     * factuels. Une paire déclarée « analyse » n'est soumise à aucune vérification de
     * sous-chaîne : le liant éditorial n'a pas à être une citation (section 5.1 du design doc).
     *
     * ACTION : bonification panel 2026-08-17 (soir) - 3e type accepté, « primary_fact » (fait
     * confirmé à la SOURCE PRIMAIRE) : exige un 'source_url' (URL http/https valide) et son
     * excerpt est la citation exacte de l'ORIGINAL - volontairement PAS vérifiée en sous-chaîne du
     * texte source collé pour l'agent (potentiellement une paraphrase ou un texte secondaire) :
     * c'est précisément sa raison d'être. Même règle appliquée par NewsApplyCommand
     * (normalizeProofPairs()) - aucune divergence entre les deux portes d'écriture.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc, section "Bonification panel 2026-08-17 (soir)".
     *
     * ACTION : correctif todo #1984, second point d'entrée (2026-08-29) - sur une fiche DÉJÀ
     * PUBLIÉE, NewsArticle::publishAndPurgeSource() a mis internal_source_text à null, donc
     * $article->internal_source_text arrive systématiquement vide ici. Contre une chaîne vide,
     * EditorialProofNormalizer::containsExact() ne peut JAMAIS réussir - une paire "fact"
     * pourtant légitime se voyait refusée avec un message qui accusait à tort l'extrait, alors
     * que la vraie cause est l'absence de texte à comparer. Ce chemin reste ATTEIGNABLE en
     * production (aucune garde is_published ici ni côté route, contrairement à `news:apply` hors
     * --enrich) : un onglet resté ouvert depuis avant la publication, ou tout appel direct à cette
     * route, y arrive toujours. La revalidation (verifyFactPair(), source absente -> paire
     * ACCEPTÉE et signalée 'source_verified' => false plutôt que refusée en silence) vit
     * désormais dans CompositionPayloadNormalizer::validateProofPair() - voir la note Lot 4a
     * ci-dessous.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: todo #1984, même famille de défaut que son premier correctif.
     *
     * LOT 4a (design doc 2026-09-03, section 2.2) : la vérification de forme, le type autorisé
     * et la revalidation "fait"/"fait primaire" ne sont plus écrites ICI - elles sont DÉLÉGUÉES à
     * CompositionPayloadNormalizer::validateProofPair(), la même méthode que
     * NewsApplyCommand::normalizeProofPairs() appelle pour un LOT de paires (Lot 1, v1.248.2).
     * Diff minimal sur un endpoint déjà correct : la validation Laravel ci-dessous reste
     * inchangée (elle protège la FORME de la requête HTTP, hors du périmètre du service partagé),
     * seule la logique métier qui suivait est remplacée par un seul appel.
     */
    public function storeProofPair(Request $request, NewsArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'statement' => ['required', 'string', 'max:1000'],
            'excerpt' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'in:fact,analysis,primary_fact'],
            'source_url' => ['required_if:type,primary_fact', 'nullable', 'url:http,https', 'max:2000'],
        ]);

        $result = CompositionPayloadNormalizer::validateProofPair((string) $article->internal_source_text, $validated);

        if (! $result['ok']) {
            return response()->json(['error' => $result['reason']], 422);
        }

        $pairs = $article->editorial_proof_pairs ?? [];
        $pairs[] = $result['entry'];

        $article->update(['editorial_proof_pairs' => $pairs]);

        // 'updated_at' reporté côté front (voir le commentaire de fetchSource() ci-dessus, même
        // raison, Lot 4a section 2.6) : un ajout de preuve ne doit jamais auto-infliger un 409 à
        // la sauvegarde des champs riches qui suivrait dans le même onglet.
        return response()->json([
            'success' => true,
            'pairs' => $pairs,
            'updated_at' => $article->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Retire une paire de la fiche de preuve éditoriale par son identifiant. N'écrit que
     * 'editorial_proof_pairs' - même garde-fou que destroySourceText() ci-dessus.
     */
    public function destroyProofPair(NewsArticle $article, string $pair): JsonResponse
    {
        $pairs = collect($article->editorial_proof_pairs ?? [])
            ->reject(fn (array $p) => ($p['id'] ?? null) === $pair)
            ->values()
            ->all();

        $article->update(['editorial_proof_pairs' => $pairs]);

        // Même report de 'updated_at' que storeProofPair() ci-dessus (Lot 4a, section 2.6).
        return response()->json([
            'success' => true,
            'pairs' => $pairs,
            'updated_at' => $article->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Ajoute UN outil lié par son slug (Lot 4b, design doc 2026-09-03, section 2.5) - action
     * immédiate, même patron d'interaction que storeProofPair() ci-dessus (pas de troisième
     * patron inventé sur cet écran). Délègue entièrement à
     * Modules\News\Actions\NewsToolSyncAction::attachBySlug() (section 2.4, méthode promue au
     * Lot 1, v1.248.2) - additif PUR, ne touche jamais aux liens déjà existants.
     *
     * Choix (non explicité au mot près par le design, tranché ici pour rester cohérent avec le
     * reste de ce contrôleur) : un slug inconnu renvoie 422, comme toute autre valeur invalide de
     * update() (nature_original/niveau_preuve) - contrairement à destroyRelatedTool() ci-dessous,
     * une TENTATIVE D'AJOUT qui n'attache rien n'atteint pas l'intention de l'admin et doit être
     * visible comme un échec, jamais un succès silencieux.
     */
    public function storeRelatedTool(Request $request, NewsArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'tool_slug' => ['required', 'string', 'max:120'],
        ]);

        $result = $this->toolSync->attachBySlug($article, [$validated['tool_slug']]);

        if ($result['module_disabled']) {
            return response()->json(['error' => 'Le module Directory est désactivé - aucun outil ne peut être lié.'], 422);
        }

        if ($result['unknown'] !== []) {
            return response()->json([
                'error' => "Slug introuvable dans l'annuaire publié : ".implode(', ', $result['unknown']).'.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'related_tools' => $this->relatedToolsPayload($article->fresh()),
            'updated_at' => $article->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Retire UN outil lié par son slug (Lot 4b, section 2.5) - additif/soustractif STRICT,
     * jamais un remplacement : seul le slug demandé est détaché (Modules\News\Actions\
     * NewsToolSyncAction::detachBySlug(), section 2.4). Une DELETE reste idempotente ici, par
     * choix délibéré (même esprit que related_tool_slugs_remove côté CLI, défaut 3 du
     * 2026-08-28, qui AVERTIT sans jamais faire échouer la commande) : un slug déjà détaché ou
     * jamais lié (`not_attached`) renvoie 200, la fiche est bien dans l'état voulu par l'admin
     * (cet outil n'est plus lié) - seul un slug qui n'existe même pas dans l'annuaire (`unknown`)
     * renvoie 422, cas qui ne devrait survenir que si l'outil a disparu de l'annuaire entre le
     * chargement de l'écran et le clic.
     */
    public function destroyRelatedTool(NewsArticle $article, string $slug): JsonResponse
    {
        $result = $this->toolSync->detachBySlug($article, [$slug]);

        if ($result['module_disabled']) {
            return response()->json(['error' => 'Le module Directory est désactivé - aucun outil ne peut être détaché.'], 422);
        }

        if ($result['unknown'] !== []) {
            return response()->json([
                'error' => "Slug introuvable dans l'annuaire : ".implode(', ', $result['unknown']).'.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'related_tools' => $this->relatedToolsPayload($article->fresh()),
            'updated_at' => $article->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Forme {slug, label} des outils DÉJÀ liés à la fiche (Lot 4b, section 2.8) - extrait pour
     * être réutilisé tel quel par show(), storeRelatedTool() et destroyRelatedTool() (DRY : 3e
     * occurrence de la même projection, seuil d'abstraction du projet atteint, CLAUDE.md section
     * "DRY et anti-sur-ingénierie").
     *
     * @return \Illuminate\Support\Collection<int, array{slug: string, label: string}>
     */
    private function relatedToolsPayload(NewsArticle $article): \Illuminate\Support\Collection
    {
        return $article->tools->map(fn ($t) => [
            'slug' => (string) $t->slug,
            'label' => $t->getTranslation('name', app()->getLocale(), false)
                ?: $t->getTranslation('name', 'fr_CA', false)
                ?: $t->getTranslation('name', 'en', false)
                ?: (string) $t->slug,
        ])->values();
    }

    /**
     * Génère le prompt d'IMAGE (Phase D, design doc section 5.3/5.4) - jamais de génération
     * programmée : ce texte est copié manuellement par l'admin puis collé dans Gemini (pilotage
     * navigateur, compte propriétaire). Contrairement à generatePrompt() ci-dessus, ne dépend PAS
     * du texte source (le style est fixe) : utilisable dès qu'un titre de travail existe.
     */
    public function generateImagePrompt(Request $request, NewsArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'angle' => ['nullable', 'string', 'max:500'],
        ]);

        $prompt = $this->promptBuilder->buildImagePrompt(
            $article->seo_title ?: $article->title,
            $validated['angle'] ?? ''
        );

        return response()->json(['success' => true, 'prompt' => $prompt]);
    }

    /**
     * Dépôt manuel du fichier rapporté de Gemini (Phase D, design doc section 5.3/5.4). Valide le
     * type MIME RÉEL du contenu (règle Laravel 'image'/'mimes', basée sur la détection du contenu
     * binaire - pas seulement l'extension déclarée par le navigateur), le poids et les dimensions
     * minimales, PUIS délègue le traitement (recadrage 1200x630, JPEG social + WebP page) à
     * NewsImageService::processFromUploadedFile() - jamais de service concurrent. Ce flux ne
     * bloque JAMAIS la composition : la fiche s'enregistre et se publie sans image (l'image de
     * repli existante fait foi tant qu'aucun dépôt n'a réussi) - cette action est indépendante de
     * save().
     */
    public function uploadImage(Request $request, NewsArticle $article): JsonResponse
    {
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.NewsImageService::MAX_UPLOAD_KB,
            ],
        ]);

        $file = $request->file('image');
        [$width, $height] = array_pad((array) @getimagesize($file->getRealPath()), 2, 0);

        if ($width < NewsImageService::MIN_WIDTH || $height < NewsImageService::MIN_HEIGHT) {
            return response()->json([
                'error' => "Image trop petite (reçue {$width}×{$height}px, minimum ".NewsImageService::MIN_WIDTH.'×'.NewsImageService::MIN_HEIGHT.'px).',
            ], 422);
        }

        try {
            $imageUrl = $this->imageService->processFromUploadedFile($file, $article->id);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Le traitement de l\'image a échoué : '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'image_url' => asset($imageUrl).'?v='.time(),
        ]);
    }

    /**
     * Bouton Publier-et-purger (design doc, section "Récupération automatique Markdown +
     * Publier-et-purger (2026-08-17)", décision propriétaire 2026-08-17) : publier et purger le
     * texte source intégral en UN SEUL geste, dans une seule transaction. Voir le doc-bloc de
     * classe ci-dessus pour l'explication de l'exception (seul endroit du contrôleur qui écrit
     * is_published/published_at).
     *
     * Ordre des vérifications, chacune bloquante et exclusive (jamais deux erreurs mélangées) :
     * 1. déjà publiée → 409 ;
     * 2. prérequis serveur manquants (seo_title, summary, au moins une paire de preuve) → 422
     *    avec la LISTE complète des manquants, pas seulement le premier ;
     * 3. revalidation à 100 % des paires « fait » contre le texte source COURANT (pas celui du
     *    moment où le prompt a été généré - une seule paire dont l'extrait n'est plus une
     *    sous-chaîne exacte fait échouer TOUTE la publication, rien ne part, rien n'est purgé.
     *
     * ACTION : les vérifications 2 et 3 sont DÉLÉGUÉES à NewsArticle::publishReadinessCheck()
     * (note datée 2026-08-17 ci-dessus dans le doc-bloc de classe) - RÉUTILISÉE telle quelle par
     * NewsApplyCommand (--publish, porte bornée de l'agent). DRY explicite : une seule
     * implémentation de « prêt à publier », jamais deux gardes qui pourraient diverger.
     * MCP: SELF (<5 lignes)
     * RAISON: extraction demandée par le mandat, aucune autre logique changée.
     */
    public function publish(NewsArticle $article): JsonResponse
    {
        if ($article->is_published) {
            return response()->json([
                'error' => 'Cette fiche est déjà publiée.',
            ], 409);
        }

        $check = $article->publishReadinessCheck();

        if (! $check['ready']) {
            if ($check['missing'] !== []) {
                return response()->json([
                    'error' => "Cette fiche n'est pas prête à être publiée : ".implode(', ', $check['missing']).' manquant(s).',
                    'missing' => $check['missing'],
                ], 422);
            }

            return response()->json([
                'error' => NewsArticle::publishInvalidPairMessage($check['invalid_pair']),
            ], 422);
        }

        // ACTION : addendum daté 2026-08-17 (fin de journée) - structured_summary (résumé
        // MACHINE de la collecte, prioritaire sur summary côté fiche publique) est effacé JUSTE
        // AVANT la publication, la composition manuelle faisant désormais autorité sur le
        // résumé affiché. logStructuredSummaryOverride() journalise l'ancienne valeur, réutilisée
        // telle quelle par NewsApplyCommand (--payload) - DRY, un seul point de la règle.
        // MCP: SELF (<5 lignes)
        // RAISON: correctif ciblé d'un défaut découvert en production, même point unique que la
        // porte bornée de l'agent.
        $article->logStructuredSummaryOverride();

        // ACTION : mécanique d'écriture DÉLÉGUÉE à NewsArticle::publishAndPurgeSource() (addendum
        // "purge garantie sur tous les chemins de publication", 2026-08-17) - RÉUTILISÉE telle
        // quelle par AdminNewsController::toggleArticle(), DRY strict sur la règle « publier =
        // purger ». Les gardes ci-dessus (prérequis + revalidation des paires) restent
        // SPÉCIFIQUES à cet endpoint, volontairement absentes de la méthode partagée.
        // MCP: SELF (<5 lignes)
        // RAISON: DRY explicite, une seule implémentation de la purge à travers le code.
        // ACTION : Richesse v1.188.0 - garde-fou découvert en implémentant ce mandat : l'effacement
        // inconditionnel de structured_summary ci-dessous était correct pour l'ancien résumé
        // MACHINE, mais aurait aussi détruit un résumé COMPOSÉ (marqueur composed:true, écrit par
        // NewsApplyCommand --payload) au moment même de le publier. hasComposedSummary() est le
        // point UNIQUE de cette distinction (DRY, réutilisé par show.blade.php).
        // MCP: SELF (<5 lignes)
        // RAISON: design doc "Actus - composition manuelle assistée", section "Richesse v1.188.0".
        DB::transaction(function () use ($article): void {
            if (! $article->hasComposedSummary()) {
                $article->update(['structured_summary' => null]);
            }
            $article->publishAndPurgeSource();
        });

        // ACTION : publier DEPUIS CET ÉCRAN est un geste humain - c'est donc ici, et non dans la
        // porte de l'agent, que la signature éditoriale se pose (2026-08-21, voir le docblock de
        // NewsArticle::markReviewedByHuman()). Une personne qui clique « Publier » a vu la fiche.
        // MCP: SELF (<5 lignes)
        // RAISON: rendre vraie la promesse publique de /methodologie (relecture humaine datée).
        $article->markReviewedByHuman();

        Log::channel('composition')->info('publish - publication et purge', [
            'article_id' => $article->id,
            'slug' => $article->slug,
            'reviewed_at' => optional($article->reviewed_at)->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'site_url' => url('/actualites/'.$article->slug),
            // Publier vaut relecture (markReviewedByHuman() ci-dessus) : l'écran doit le savoir,
            // sinon il proposerait « J'ai relu » sur une fiche qui vient justement d'être signée.
            'reviewed_at' => optional($article->reviewed_at)->toIso8601String(),
            'reviewed_by' => $article->reviewerLabel(),
        ]);
    }

    /**
     * ACTION : bouton « J'ai relu » - pose la signature éditoriale sur une fiche DÉJÀ publiée.
     *
     * Complément indispensable de publish() : les fiches composées et publiées par l'agent
     * n'ont, par construction, aucune signature (2026-08-21). Cet endpoint est le geste humain
     * qui l'accorde, une fois la fiche réellement lue. Il ne modifie RIEN d'autre : ni le texte,
     * ni la publication, ni la fraîcheur servant de jeton à la porte d'écriture.
     *
     * MCP: SELF (<5 lignes de logique, le reste est du contrat HTTP)
     * RAISON: sans cet endroit, une fiche publiée par l'agent ne pourrait jamais être signée.
     */
    public function markReviewed(NewsArticle $article): JsonResponse
    {
        if ($article->hasEditorialReview()) {
            return response()->json([
                'error' => 'Cette fiche porte déjà une signature éditoriale.',
                'reviewed_at' => $article->reviewed_at->toIso8601String(),
            ], 409);
        }

        $article->markReviewedByHuman();

        Log::channel('composition')->info('markReviewed - signature éditoriale posée par un humain', [
            'article_id' => $article->id,
            'slug' => $article->slug,
            'reviewed_at' => optional($article->reviewed_at)->toIso8601String(),
        ]);

        // Même nom de clé que publish() ci-dessus (`reviewed_by`) : deux points d'entrée qui
        // décrivent la même chose ne doivent pas la nommer différemment - le front n'a alors
        // qu'une seule convention à connaître.
        return response()->json([
            'success' => true,
            'reviewed_at' => optional($article->reviewed_at)->toIso8601String(),
            'reviewed_by' => $article->reviewerLabel(),
        ]);
    }
}
