<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\CompositionPromptBuilder;
use Modules\News\Services\EditorialProofNormalizer;
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
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class NewsCompositionController extends Controller
{
    public function __construct(
        private readonly CompositionPromptBuilder $promptBuilder,
        private readonly NewsImageService $imageService,
        private readonly SourceMarkdownFetcher $sourceFetcher,
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
            'generatePromptEndpointTemplate' => route('admin.news.composition.generate-prompt', ['article' => '__SLUG__']),
            'proofPairsStoreEndpointTemplate' => route('admin.news.composition.proof-pairs.store', ['article' => '__SLUG__']),
            'proofPairsDestroyEndpointTemplate' => route('admin.news.composition.proof-pairs.destroy', ['article' => '__SLUG__', 'pair' => '__PAIR_ID__']),
            'generateImagePromptEndpointTemplate' => route('admin.news.composition.generate-image-prompt', ['article' => '__SLUG__']),
            'uploadImageEndpointTemplate' => route('admin.news.composition.upload-image', ['article' => '__SLUG__']),
            'articlesIndexUrl' => route('admin.news.articles.index'),
        ]);
    }

    /**
     * Liste des actualités déjà collectées, pour la colonne "disponibles" du composant partagé
     * news-article-picker.js (voir public/assets/admin/news-article-picker.js). Même forme JSON
     * que ConcentreBuilderController::newsForWeek(), sans le regroupement par acteur (inutile ici
     * : une seule actualité est composée à la fois, pas de tri par cluster à faire).
     */
    public function candidates(): JsonResponse
    {
        $articles = NewsArticle::query()
            ->with('source')
            ->orderByDesc('pub_date')
            ->limit(200)
            ->get();

        return response()->json([
            'items' => $articles->map(fn (NewsArticle $a) => [
                'id' => $a->id,
                'title' => $a->seo_title ?: $a->title,
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
            'is_published' => (bool) $article->is_published,
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
     * Sauvegarde le titre publié (seo_title), le résumé publié (summary) et/ou le texte source
     * interne (internal_source_text) d'une actualité déjà collectée. N'écrit jamais dans
     * 'description' (colonne purgée, ne plus jamais réutiliser - voir la migration).
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
        ]);

        $this->applySourceProvenance($validated, $article);

        $article->update($validated);

        return response()->json([
            'success' => true,
            'updated_at' => $article->fresh()->updated_at?->toIso8601String(),
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

        return response()->json(['success' => true]);
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

        return response()->json([
            'success' => true,
            'markdown' => $result['markdown'],
            'acquisition' => $result['acquisition'],
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
     */
    public function storeProofPair(Request $request, NewsArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'statement' => ['required', 'string', 'max:1000'],
            'excerpt' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'in:fact,analysis,primary_fact'],
            'source_url' => ['required_if:type,primary_fact', 'nullable', 'url:http,https', 'max:2000'],
        ]);

        if ($validated['type'] === 'fact') {
            $source = (string) $article->internal_source_text;

            if (! EditorialProofNormalizer::containsExact($source, $validated['excerpt'])) {
                return response()->json([
                    'error' => 'Cet extrait n\'est pas une sous-chaîne exacte du texte source : reprends-le mot pour mot, ou déclare cette paire comme « analyse ».',
                ], 422);
            }
        }

        $pairs = $article->editorial_proof_pairs ?? [];
        $newPair = [
            'id' => (string) Str::uuid(),
            'statement' => $validated['statement'],
            'excerpt' => $validated['excerpt'],
            'type' => $validated['type'],
            'created_at' => now('America/Toronto')->toIso8601String(),
        ];
        if ($validated['type'] === 'primary_fact') {
            $newPair['source_url'] = $validated['source_url'];
        }
        $pairs[] = $newPair;

        $article->update(['editorial_proof_pairs' => $pairs]);

        return response()->json(['success' => true, 'pairs' => $pairs]);
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

        return response()->json(['success' => true, 'pairs' => $pairs]);
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
        DB::transaction(function () use ($article): void {
            $article->update(['structured_summary' => null]);
            $article->publishAndPurgeSource();
        });

        Log::channel('composition')->info('publish - publication et purge', [
            'article_id' => $article->id,
            'slug' => $article->slug,
        ]);

        return response()->json([
            'success' => true,
            'site_url' => url('/actualites/'.$article->slug),
        ]);
    }
}
