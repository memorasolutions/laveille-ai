<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\CompositionPromptBuilder;
use Modules\News\Services\EditorialProofNormalizer;
use Modules\News\Services\NewsImageService;

/**
 * SEULE porte d'écriture bornée pour l'agent Claude Code CLI orchestré par l'écran de
 * composition (design doc "Actus - composition manuelle assistée" 2026-08-15, section "Révision
 * 2026-08-17 - prompt d'orchestration Claude Code CLI"). Décision unanime du panel de 5 IA :
 * l'agent n'écrit JAMAIS librement en base (aucun Eloquent, aucun SQL, aucun tinker) - il ne peut
 * appliquer son travail que par CETTE commande, qui impose une liste blanche stricte de clés et
 * une double protection anti-écrasement (empreinte du texte source + updated_at).
 *
 * Deux modes indépendants, chacun reprenable seul :
 * - `--payload=` : applique seo_title / summary / editorial_proof_pairs depuis un fichier JSON.
 * - `--image=`   : applique un fichier image local déjà obtenu (Gemini), via
 *                  NewsImageService::processFromLocalFile().
 *
 * Un échec du mode --image ne remet jamais en cause une application --payload déjà réussie, et
 * inversement - c'est voulu (étape 4 du prompt généré, reprenable indépendamment de l'étape 3).
 *
 * Ne touche JAMAIS is_published ni published_at : la publication reste un geste manuel du
 * propriétaire dans /admin/news/articles, quel que soit ce que la commande applique.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class NewsApplyCommand extends Command
{
    /**
     * Liste blanche stricte des clés de contenu acceptées dans le payload JSON. Toute autre clé
     * (y compris is_published, published_at, slug, id...) fait refuser la commande explicitement
     * - jamais un simple avertissement, jamais une clé ignorée en silence.
     */
    private const ALLOWED_PAYLOAD_KEYS = ['expected_source_hash', 'expected_updated_at', 'seo_title', 'summary', 'editorial_proof_pairs'];

    protected $signature = 'news:apply {article : id de la fiche news_articles} {--payload= : chemin d\'un fichier JSON de charge utile texte} {--image= : chemin d\'un fichier image local à appliquer}';

    protected $description = 'Seule porte d\'écriture bornée pour l\'agent de composition (Actus 2.0) - jamais d\'Eloquent/SQL direct par l\'agent.';

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

        // ACTION : refus systématique sur une fiche déjà publiée - garde-fou du panel de 5 IA,
        // AUCUNE exception. L'agent (ou un rejeu accidentel de la commande) ne doit jamais
        // pouvoir modifier une fiche déjà en ligne par cette porte.
        // MCP: SELF (<5 lignes)
        // RAISON: unique limite non négociable exigée par le mandat, vérifiée avant toute autre
        // logique des deux modes ci-dessous.
        if ($article->is_published) {
            $this->error("La fiche {$article->id} est déjà publiée - news:apply refuse d'écrire sur une fiche publiée.");

            return self::FAILURE;
        }

        $payloadPath = $this->option('payload');
        $imagePath = $this->option('image');

        if (! $payloadPath && ! $imagePath) {
            $this->error('Fournis --payload=<fichier.json> ou --image=<fichier> (ou les deux, en deux appels séparés).');

            return self::FAILURE;
        }

        if ($payloadPath) {
            $result = $this->applyPayload($article, (string) $payloadPath);
            if ($result !== self::SUCCESS) {
                return $result;
            }
        }

        if ($imagePath) {
            $result = $this->applyImage($article, (string) $imagePath);
            if ($result !== self::SUCCESS) {
                return $result;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Mode --payload : texte (titre, résumé, preuve éditoriale). Liste blanche stricte + double
     * protection anti-écrasement (empreinte du texte source ET updated_at) - une fiche modifiée
     * depuis la génération du prompt fait échouer la commande plutôt que d'écraser un travail
     * plus récent en silence.
     */
    private function applyPayload(NewsArticle $article, string $payloadPath): int
    {
        if (! is_file($payloadPath)) {
            $this->error("Fichier payload introuvable : {$payloadPath}.");

            return self::FAILURE;
        }

        $decoded = json_decode((string) file_get_contents($payloadPath), true);
        if (! is_array($decoded)) {
            $this->error('Payload JSON invalide (racine attendue : un objet).');

            return self::FAILURE;
        }

        $unknownKeys = array_diff(array_keys($decoded), self::ALLOWED_PAYLOAD_KEYS);
        if ($unknownKeys !== []) {
            $this->error('Clé(s) non autorisée(s) dans le payload : '.implode(', ', $unknownKeys).'. Clés permises : '.implode(', ', self::ALLOWED_PAYLOAD_KEYS).'.');

            return self::FAILURE;
        }

        if (! array_key_exists('expected_source_hash', $decoded) || ! array_key_exists('expected_updated_at', $decoded)) {
            $this->error('Le payload doit contenir expected_source_hash et expected_updated_at (métadonnées de fraîcheur du prompt).');

            return self::FAILURE;
        }

        if ((string) $decoded['expected_source_hash'] !== (string) $article->source_content_hash) {
            $this->error("Empreinte du texte source périmée pour la fiche {$article->id} : la fiche a changé depuis la génération du prompt. Régénère le prompt et recommence.");

            return self::FAILURE;
        }

        if ((string) $decoded['expected_updated_at'] !== (string) $article->updated_at?->toIso8601String()) {
            $this->error("La fiche {$article->id} a été modifiée depuis la génération du prompt (updated_at différent). Régénère le prompt et recommence.");

            return self::FAILURE;
        }

        $updates = [];

        if (array_key_exists('seo_title', $decoded)) {
            if (! is_string($decoded['seo_title'])) {
                $this->error('seo_title doit être une chaîne de caractères.');

                return self::FAILURE;
            }
            $updates['seo_title'] = $decoded['seo_title'];
        }

        if (array_key_exists('summary', $decoded)) {
            if (! is_string($decoded['summary'])) {
                $this->error('summary doit être une chaîne de caractères.');

                return self::FAILURE;
            }
            $updates['summary'] = $decoded['summary'];
        }

        if (array_key_exists('editorial_proof_pairs', $decoded)) {
            $normalizedPairs = $this->normalizeProofPairs($decoded['editorial_proof_pairs'], (string) $article->internal_source_text);
            if ($normalizedPairs === null) {
                // Message d'erreur déjà émis par normalizeProofPairs().
                return self::FAILURE;
            }
            // ACTION : les nouvelles paires COMPLÈTENT les paires existantes (jamais un
            // remplacement intégral) - une fiche peut déjà porter des paires ajoutées à la main
            // via l'écran (storeProofPair), et cette commande ne doit jamais les faire
            // disparaître en silence.
            // MCP: SELF (<5 lignes)
            // RAISON: même sémantique d'accumulation que storeProofPair() côté contrôleur.
            $updates['editorial_proof_pairs'] = array_merge($article->editorial_proof_pairs ?? [], $normalizedPairs);
        }

        if ($updates === []) {
            $this->error('Payload sans effet : aucune des clés seo_title / summary / editorial_proof_pairs n\'est fournie.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($article, $updates): void {
            $article->update($updates);
        });

        Log::channel('composition')->info('news:apply - payload appliqué', [
            'article_id' => $article->id,
            'source_hash' => $article->source_content_hash,
            'prompt_version' => CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION,
            'keys_applied' => array_keys($updates),
        ]);

        $this->info("Fiche {$article->id} : payload texte appliqué (".implode(', ', array_keys($updates)).').');

        return self::SUCCESS;
    }

    /**
     * Valide et normalise le tableau de paires de preuve éditoriale du payload. Retourne null (et
     * émet le message d'erreur) si une paire est invalide - jamais une validation partielle.
     * Réutilise EditorialProofNormalizer::containsExact(), même règle que
     * NewsCompositionController::storeProofPair() : une paire "fact" doit être une sous-chaîne
     * exacte du texte source.
     *
     * @return array<int, array{id: string, statement: string, excerpt: string, type: string, created_at: string}>|null
     */
    private function normalizeProofPairs(mixed $pairsInput, string $sourceText): ?array
    {
        if (! is_array($pairsInput)) {
            $this->error('editorial_proof_pairs doit être un tableau de paires.');

            return null;
        }

        $normalized = [];

        foreach ($pairsInput as $pair) {
            if (! is_array($pair) || ! isset($pair['statement'], $pair['excerpt'], $pair['type'])
                || ! is_string($pair['statement']) || ! is_string($pair['excerpt']) || ! is_string($pair['type'])) {
                $this->error('Chaque paire de editorial_proof_pairs doit contenir statement, excerpt et type (chaînes).');

                return null;
            }

            if (! in_array($pair['type'], ['fact', 'analysis'], true)) {
                $this->error("Type de paire invalide : « {$pair['type']} » (attendu : fact ou analysis).");

                return null;
            }

            if ($pair['type'] === 'fact' && ! EditorialProofNormalizer::containsExact($sourceText, $pair['excerpt'])) {
                $this->error("Extrait déclaré « fact » absent du texte source (sous-chaîne exacte attendue) : {$pair['excerpt']}");

                return null;
            }

            $normalized[] = [
                'id' => (string) Str::uuid(),
                'statement' => $pair['statement'],
                'excerpt' => $pair['excerpt'],
                'type' => $pair['type'],
                'created_at' => now('America/Toronto')->toIso8601String(),
            ];
        }

        return $normalized;
    }

    /**
     * Mode --image : dépôt d'un fichier image local déjà obtenu (Gemini, pilotage navigateur du
     * propriétaire). Mêmes validations que NewsCompositionController::uploadImage() (type MIME
     * réel du CONTENU - pas seulement l'extension - poids, dimensions minimales), bornes
     * partagées via NewsImageService::MAX_UPLOAD_KB / MIN_WIDTH / MIN_HEIGHT.
     */
    private function applyImage(NewsArticle $article, string $imagePath): int
    {
        if (! is_file($imagePath)) {
            $this->error("Fichier image introuvable : {$imagePath}.");

            return self::FAILURE;
        }

        $sizeKb = filesize($imagePath) / 1024;
        if ($sizeKb > NewsImageService::MAX_UPLOAD_KB) {
            $this->error('Image trop lourde ('.round($sizeKb).' Ko, maximum '.NewsImageService::MAX_UPLOAD_KB.' Ko).');

            return self::FAILURE;
        }

        // Type MIME RÉEL du contenu binaire (finfo, jamais l'extension déclarée) - même garde-fou
        // que la règle Laravel 'mimes' côté contrôleur.
        $mime = @mime_content_type($imagePath) ?: '';
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $this->error("Type d'image non supporté (détecté : ".($mime !== '' ? $mime : 'inconnu').', attendu : jpeg, png ou webp).');

            return self::FAILURE;
        }

        [$width, $height] = array_pad((array) @getimagesize($imagePath), 2, 0);
        if ($width < NewsImageService::MIN_WIDTH || $height < NewsImageService::MIN_HEIGHT) {
            $this->error("Image trop petite (reçue {$width}×{$height}px, minimum ".NewsImageService::MIN_WIDTH.'×'.NewsImageService::MIN_HEIGHT.'px).');

            return self::FAILURE;
        }

        try {
            $imageUrl = $this->imageService->processFromLocalFile($imagePath, $article->id);
        } catch (\Throwable $e) {
            $this->error('Le traitement de l\'image a échoué : '.$e->getMessage());

            return self::FAILURE;
        }

        Log::channel('composition')->info('news:apply - image appliquée', [
            'article_id' => $article->id,
            'prompt_version' => CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION,
        ]);

        $this->info("Fiche {$article->id} : image appliquée ({$imageUrl}).");

        return self::SUCCESS;
    }
}
