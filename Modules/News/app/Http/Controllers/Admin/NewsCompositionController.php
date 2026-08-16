<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\CompositionPromptBuilder;
use Modules\News\Services\EditorialProofNormalizer;
use Modules\News\Services\NewsImageService;

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
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class NewsCompositionController extends Controller
{
    // Bornes de validation du dépôt manuel d'image (design doc section 5.4). Poids maximal
    // raisonnable pour une image source (avant recadrage/compression) : 8 Mo. Dimensions
    // minimales : la moitié de la cible 1200x630, pour éviter un agrandissement excessif
    // (au-delà, l'image serait visiblement floue une fois recadrée en 1200x630).
    private const IMAGE_MAX_KB = 8192;

    private const IMAGE_MIN_WIDTH = 600;

    private const IMAGE_MIN_HEIGHT = 315;

    public function __construct(
        private readonly CompositionPromptBuilder $promptBuilder,
        private readonly NewsImageService $imageService,
    ) {
    }

    public function index(): View
    {
        return view('news::admin.composition-builder', [
            'candidatesEndpoint' => route('admin.news.composition.candidates'),
            'showEndpointTemplate' => route('admin.news.composition.show', ['article' => '__SLUG__']),
            'updateEndpointTemplate' => route('admin.news.composition.update', ['article' => '__SLUG__']),
            'deleteSourceTextEndpointTemplate' => route('admin.news.composition.destroy-source-text', ['article' => '__SLUG__']),
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
            'is_published' => (bool) $article->is_published,
            'site_url' => url('/actualites/'.$article->slug),
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

        // ACTION : complément de conservation (design doc section 5.2) - date de capture et
        // empreinte SHA-256 recalculées UNIQUEMENT quand un texte source non vide est
        // effectivement collé ou modifié (hash différent de celui déjà en base), jamais sur un
        // texte vide ou inchangé. Un vidage manuel du champ (texte source envoyé vide, puis
        // "Enregistrer") laisse donc ces deux valeurs INTACTES, exactement comme la suppression
        // dédiée destroySourceText() - même garde-fou "survit à la suppression" (5.2), quel que
        // soit le chemin emprunté pour vider le texte.
        // MCP: SELF (<5 lignes)
        // RAISON: preuve durable qui rend le texte intégral supprimable sans perte.
        if (array_key_exists('internal_source_text', $validated) && filled($validated['internal_source_text'])) {
            $hash = hash('sha256', $validated['internal_source_text']);
            if ($hash !== $article->source_content_hash) {
                $validated['source_content_hash'] = $hash;
                $validated['source_captured_at'] = now('America/Toronto');
            }
        }

        $article->update($validated);

        return response()->json([
            'success' => true,
            'updated_at' => $article->fresh()->updated_at?->toIso8601String(),
        ]);
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
     * Génère le prompt de rédaction (Phase B, design doc section 5.1 et 7) à partir du texte
     * source déjà collé, du titre de travail et d'un angle éditorial optionnel. Calqué sur
     * ConcentreBuilderController::generate() : aucune écriture en base ici, uniquement du texte
     * à copier - l'écran reste un assistant de composition, jamais un générateur (section 5.3 du
     * design doc).
     */
    public function generatePrompt(Request $request, NewsArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'angle' => ['nullable', 'string', 'max:500'],
        ]);

        if (blank($article->internal_source_text)) {
            return response()->json([
                'error' => 'Colle d\'abord le texte source de cette actualité avant de générer le prompt.',
            ], 422);
        }

        $prompt = $this->promptBuilder->build(
            $article->internal_source_text,
            $article->seo_title ?: $article->title,
            $validated['angle'] ?? ''
        );

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
     */
    public function storeProofPair(Request $request, NewsArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'statement' => ['required', 'string', 'max:1000'],
            'excerpt' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'in:fact,analysis'],
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
        $pairs[] = [
            'id' => (string) Str::uuid(),
            'statement' => $validated['statement'],
            'excerpt' => $validated['excerpt'],
            'type' => $validated['type'],
            'created_at' => now('America/Toronto')->toIso8601String(),
        ];

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
                'max:'.self::IMAGE_MAX_KB,
            ],
        ]);

        $file = $request->file('image');
        [$width, $height] = array_pad((array) @getimagesize($file->getRealPath()), 2, 0);

        if ($width < self::IMAGE_MIN_WIDTH || $height < self::IMAGE_MIN_HEIGHT) {
            return response()->json([
                'error' => "Image trop petite (reçue {$width}×{$height}px, minimum ".self::IMAGE_MIN_WIDTH.'×'.self::IMAGE_MIN_HEIGHT.'px).',
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
}
