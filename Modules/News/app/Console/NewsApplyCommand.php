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
 * Trois modes indépendants, chacun reprenable seul :
 * - `--payload=` : applique seo_title / summary / editorial_proof_pairs / primary_sources /
 *                  image_credit / composed_summary depuis un fichier JSON, et efface AUSSI
 *                  structured_summary (résumé MACHINE de la collecte - addendum daté 2026-08-17,
 *                  fin de journée, voir NewsArticle::logStructuredSummaryOverride()). primary_sources/
 *                  image_credit ajoutés par la bonification panel 2026-08-17 (soir, design doc) -
 *                  contrairement aux autres champs de ce mode, ils NE SONT PAS internes : affichés
 *                  tels quels sur la fiche publique (Modules\News\resources\views\public\show.blade.php).
 *                  composed_summary (Richesse v1.188.0, design doc section "Richesse v1.188.0")
 *                  est un CAS SPÉCIAL : au lieu d'effacer structured_summary à null comme les
 *                  autres champs de ce mode, il le REMPLACE par la version composée (marqueur
 *                  `composed: true`, distinguant à jamais le résumé composé du défunt résumé
 *                  machine) - voir NewsArticle::hasComposedSummary().
 * - `--image=`   : applique un fichier image local déjà obtenu (Gemini), via
 *                  NewsImageService::processFromLocalFile().
 * - `--publish`  : publie la fiche (voir la note ci-dessous - ajouté 2026-08-17, fin de journée).
 *
 * NOTE DATÉE 2026-08-17 (fin de journée, addendum découvert en production) - la fiche publique
 * affiche structured_summary EN PRIORITÉ sur summary (Modules\News\resources\views\public\
 * show.blade.php) : tant qu'il subsiste, le résumé composé via --payload restait invisible sur
 * le site. Le mode --payload l'efface donc désormais systématiquement dès qu'il applique du
 * contenu - la composition manuelle fait autorité sur le résumé publié. L'ancienne valeur est
 * JOURNALISÉE avant l'effacement (canal 'composition'), jamais perdue en silence. Même règle
 * appliquée par NewsCompositionController::publish() juste avant publishAndPurgeSource() - DRY
 * via NewsArticle::logStructuredSummaryOverride(), réutilisée par les deux SEULS chemins
 * concernés (jamais update() de l'écran de composition, jamais la bascule rapide
 * AdminNewsController::toggleArticle()).
 *
 * Un échec du mode --image ne remet jamais en cause une application --payload déjà réussie, et
 * inversement - c'est voulu (étape 4 du prompt généré, reprenable indépendamment de l'étape 3).
 *
 * NOTE DATÉE 2026-08-17 (fin de journée) - RENVERSE un arbitrage antérieur du panel de 5 IA du
 * MÊME jour : décision du propriétaire, l'agent Claude Code CLI publie désormais lui-même la
 * fiche, en toute fin de son prompt d'orchestration (étape 6, après texte, image ET révision
 * adversariale obligatoire - étape 5, addendum reçu pendant cette même révision), via le mode
 * `--publish` ci-dessous, puis donne au propriétaire le lien public direct de la fiche pour une
 * inspection APRÈS publication (et non plus avant). L'ancien principe « l'agent ne publie
 * jamais » tombe : CETTE commande reste la SEULE porte, et `--publish` applique EXACTEMENT les
 * mêmes prérequis et la même revalidation que le bouton manuel Publier-et-purger de l'écran de
 * composition (Modules\News\Http\Controllers\Admin\NewsCompositionController::publish()) - les
 * deux chemins délèguent à NewsArticle::publishReadinessCheck() puis
 * NewsArticle::publishAndPurgeSource() (DRY strict, aucune divergence possible). Cette commande
 * et publish() ci-dessus sont désormais les DEUX SEULS endroits du code entier qui écrivent
 * is_published/published_at - jamais un Eloquent/SQL/tinker direct par l'agent, jamais un autre
 * moyen.
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
    // ACTION : bonification panel 2026-08-17 (soir) - 'primary_sources' et 'image_credit'
    // rejoignent la liste blanche, mêmes garde-fous que les clés existantes (aucune autre clé
    // n'est jamais acceptée en silence).
    // MCP: SELF (<5 lignes)
    // RAISON: design doc, section "Bonification panel 2026-08-17 (soir)".
    // ACTION : implémentation /actu2 - volet serveur (2026-08-17) - 'nature_original',
    // 'niveau_preuve' et 'original_post' rejoignent la liste blanche, mêmes garde-fous.
    // MCP: SELF (<5 lignes)
    // RAISON: design doc, section "Implémentation /actu2 - volet serveur (2026-08-17)".
    // ACTION : Richesse v1.188.0 - 'composed_summary' rejoint la liste blanche, même garde-fou.
    // MCP: SELF (<5 lignes)
    // RAISON: design doc, section "Richesse v1.188.0 - structure fixe composée (2026-08-17 soir)".
    private const ALLOWED_PAYLOAD_KEYS = ['expected_source_hash', 'expected_updated_at', 'seo_title', 'summary', 'editorial_proof_pairs', 'primary_sources', 'image_credit', 'nature_original', 'niveau_preuve', 'original_post', 'composed_summary'];

    /**
     * Richesse v1.188.0 - sous-clés autorisées de composed_summary (design doc, section
     * "Richesse v1.188.0"). Toute autre sous-clé fait refuser tout le payload, même règle que
     * ALLOWED_PAYLOAD_KEYS ci-dessus.
     */
    private const ALLOWED_COMPOSED_SUMMARY_KEYS = ['hook', 'key_points', 'why_important', 'key_number', 'quote', 'angle_qc_ca', 'action_concrete', 'reperes_dates'];

    /**
     * Richesse v1.188.0 - borne par défaut d'une chaîne simple de composed_summary (hook,
     * why_important, key_number, angle_qc_ca, action_concrete) - "~600 max chacune sauf indiqué"
     * (design doc). quote.text/quote.author, les éléments de key_points et les champs de
     * reperes_dates ont leurs propres bornes, plus courtes, validées séparément.
     */
    private const COMPOSED_SUMMARY_STRING_MAX = 600;

    /**
     * Valeurs acceptées pour 'nature_original' (design doc, section "Implémentation /actu2 -
     * volet serveur (2026-08-17)") - classification INTERNE de la nature de l'original retrouvé
     * par le skill.
     */
    private const ALLOWED_NATURE_ORIGINAL = ['annonce_commerciale', 'etude_evaluee', 'preimpression', 'message_personnel'];

    /**
     * Valeurs acceptées pour 'niveau_preuve' (même section) - degré auquel la fiche s'appuie sur
     * l'original plutôt que sur un texte secondaire. PUBLIC, traduit côté fiche (jamais
     * l'étiquette technique brute) par Modules\News\resources\views\public\show.blade.php.
     */
    private const ALLOWED_NIVEAU_PREUVE = ['primaire', 'mixte', 'relais'];

    protected $signature = 'news:apply {article : id de la fiche news_articles} {--payload= : chemin d\'un fichier JSON de charge utile texte - efface aussi structured_summary (résumé machine), qui prime sinon sur ta composition côté fiche publique} {--image= : chemin d\'un fichier image local à appliquer} {--credit= : crédit photo appliqué avec --image (le payload exige la fraîcheur, qui change après la 1re écriture - le crédit voyage donc avec l\'image)} {--publish : publie la fiche - mêmes prérequis que le bouton manuel Publier-et-purger, refuse si déjà publiée}';

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
        // logique des trois modes ci-dessous.
        if ($article->is_published) {
            $this->error("La fiche {$article->id} est déjà publiée - news:apply refuse d'écrire sur une fiche publiée.");

            return self::FAILURE;
        }

        $payloadPath = $this->option('payload');
        $imagePath = $this->option('image');
        $publish = (bool) $this->option('publish');

        if (! $payloadPath && ! $imagePath && ! $publish) {
            $this->error('Fournis --payload=<fichier.json>, --image=<fichier> et/ou --publish (au moins une des trois, seule ou combinée).');

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

        if ($publish) {
            $result = $this->applyPublish($article);
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

        // ACTION : bonification panel 2026-08-17 (soir) - primary_sources REMPLACE la valeur
        // existante (contrairement à editorial_proof_pairs, accumulé pair par pair) : la liste de
        // sources primaires est fournie par l'agent comme un tout cohérent à chaque application,
        // jamais construite depuis l'écran (lecture seule côté composition-builder.blade.php).
        // MCP: SELF (<5 lignes)
        // RAISON: design doc, section "Bonification panel 2026-08-17 (soir)".
        if (array_key_exists('primary_sources', $decoded)) {
            $normalizedSources = $this->normalizePrimarySources($decoded['primary_sources']);
            if ($normalizedSources === null) {
                // Message d'erreur déjà émis par normalizePrimarySources().
                return self::FAILURE;
            }
            $updates['primary_sources'] = $normalizedSources;
        }

        if (array_key_exists('image_credit', $decoded)) {
            if (! is_string($decoded['image_credit'])) {
                $this->error('image_credit doit être une chaîne de caractères.');

                return self::FAILURE;
            }
            if (mb_strlen($decoded['image_credit']) > 255) {
                $this->error('image_credit dépasse 255 caractères.');

                return self::FAILURE;
            }
            $updates['image_credit'] = $decoded['image_credit'];
        }

        // ACTION : implémentation /actu2 - volet serveur (2026-08-17) - trois clés
        // supplémentaires, mêmes garde-fous de validation stricte que les clés existantes
        // ci-dessus (refus explicite, jamais un enregistrement partiel).
        // MCP: SELF (<5 lignes)
        // RAISON: design doc, section "Implémentation /actu2 - volet serveur (2026-08-17)".
        if (array_key_exists('nature_original', $decoded)) {
            if (! is_string($decoded['nature_original']) || ! in_array($decoded['nature_original'], self::ALLOWED_NATURE_ORIGINAL, true)) {
                $this->error('nature_original invalide (attendu : '.implode(', ', self::ALLOWED_NATURE_ORIGINAL).').');

                return self::FAILURE;
            }
            $updates['nature_original'] = $decoded['nature_original'];
        }

        if (array_key_exists('niveau_preuve', $decoded)) {
            if (! is_string($decoded['niveau_preuve']) || ! in_array($decoded['niveau_preuve'], self::ALLOWED_NIVEAU_PREUVE, true)) {
                $this->error('niveau_preuve invalide (attendu : '.implode(', ', self::ALLOWED_NIVEAU_PREUVE).').');

                return self::FAILURE;
            }
            $updates['niveau_preuve'] = $decoded['niveau_preuve'];
        }

        if (array_key_exists('original_post', $decoded)) {
            $normalizedPost = $this->normalizeOriginalPost($decoded['original_post']);
            if ($normalizedPost === null) {
                // Message d'erreur déjà émis par normalizeOriginalPost().
                return self::FAILURE;
            }
            $updates['original_post'] = $normalizedPost;
        }

        // ACTION : Richesse v1.188.0 - composed_summary est un CAS SPÉCIAL parmi les clés de ce
        // mode : il écrit DIRECTEMENT structured_summary (marqueur composed:true ajouté ici),
        // au lieu de laisser le bloc générique ci-dessous l'effacer à null comme pour les autres
        // clés de contenu. Voir NewsArticle::hasComposedSummary(), point unique de cette
        // distinction, réutilisé par show.blade.php (ordre fixe) et NewsCompositionController::
        // publish() (garde-fou anti-effacement au bouton manuel).
        // MCP: SELF (<5 lignes)
        // RAISON: design doc, section "Richesse v1.188.0 - structure fixe composée (2026-08-17 soir)".
        if (array_key_exists('composed_summary', $decoded)) {
            $normalizedComposed = $this->normalizeComposedSummary($decoded['composed_summary']);
            if ($normalizedComposed === null) {
                // Message d'erreur déjà émis par normalizeComposedSummary().
                return self::FAILURE;
            }
            $updates['structured_summary'] = array_merge(['composed' => true], $normalizedComposed);
        }

        if ($updates === []) {
            $this->error('Payload sans effet : aucune des clés seo_title / summary / editorial_proof_pairs / primary_sources / image_credit / nature_original / niveau_preuve / original_post / composed_summary n\'est fournie.');

            return self::FAILURE;
        }

        // ACTION : addendum daté 2026-08-17 (fin de journée) - dès qu'un payload de contenu est
        // appliqué, structured_summary (résumé MACHINE de la collecte, prioritaire sur summary
        // côté fiche publique) est effacé : la composition manuelle fait désormais autorité.
        // logStructuredSummaryOverride() journalise l'ancienne valeur AVANT l'effacement, cette
        // même méthode réutilisée telle quelle par NewsCompositionController::publish() (DRY).
        // MCP: SELF (<5 lignes)
        // RAISON: correctif ciblé, réutilise le point unique déjà extrait sur le modèle.
        //
        // ACTION : Richesse v1.188.0 - si composed_summary vient d'écrire structured_summary
        // ci-dessus, ne PAS l'écraser à null ici (seule la journalisation de l'ANCIENNE valeur
        // reste inconditionnelle - jamais perdue en silence, même quand elle est remplacée par
        // une composition plutôt qu'effacée).
        // MCP: SELF (<5 lignes)
        // RAISON: design doc, section "Richesse v1.188.0" - "il le REMPLACE par la version composée".
        $article->logStructuredSummaryOverride();
        if (! array_key_exists('structured_summary', $updates)) {
            $updates['structured_summary'] = null;
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
     * ACTION : bonification panel 2026-08-17 (soir) - 3e type accepté, « primary_fact » (fait
     * confirmé à la SOURCE PRIMAIRE) : exige un 'source_url' (URL http/https valide) ; son excerpt
     * N'EST JAMAIS revalidé en sous-chaîne du texte source ($sourceText, le texte collé pour
     * l'agent) - c'est la citation exacte de l'ORIGINAL, potentiellement absente d'un texte
     * secondaire paraphrasé ou incomplet. Même règle que
     * NewsCompositionController::storeProofPair() - aucune divergence entre les deux portes.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc, section "Bonification panel 2026-08-17 (soir)".
     *
     * @return array<int, array{id: string, statement: string, excerpt: string, type: string, created_at: string, source_url?: string}>|null
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

            if (! in_array($pair['type'], ['fact', 'analysis', 'primary_fact'], true)) {
                $this->error("Type de paire invalide : « {$pair['type']} » (attendu : fact, analysis ou primary_fact).");

                return null;
            }

            if ($pair['type'] === 'fact' && ! EditorialProofNormalizer::containsExact($sourceText, $pair['excerpt'])) {
                $this->error("Extrait déclaré « fact » absent du texte source (sous-chaîne exacte attendue) : {$pair['excerpt']}");

                return null;
            }

            $entry = [
                'id' => (string) Str::uuid(),
                'statement' => $pair['statement'],
                'excerpt' => $pair['excerpt'],
                'type' => $pair['type'],
                'created_at' => now('America/Toronto')->toIso8601String(),
            ];

            if ($pair['type'] === 'primary_fact') {
                $sourceUrl = is_string($pair['source_url'] ?? null) ? trim($pair['source_url']) : '';
                if ($sourceUrl === '' || ! filter_var($sourceUrl, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $sourceUrl)) {
                    $this->error("Paire « primary_fact » sans URL de source primaire valide (http/https) : « {$pair['statement']} ».");

                    return null;
                }
                $entry['source_url'] = $sourceUrl;
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }

    /**
     * ACTION : bonification panel 2026-08-17 (soir) - valide et normalise le tableau de sources
     * primaires du payload. REMPLACE intégralement la valeur existante (contrairement aux paires
     * de preuve, accumulées) : voir le commentaire d'appel dans applyPayload(). Borne à 10
     * sources : une fiche cite ses sources primaires, elle n'en dresse pas un annuaire.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc, section "Bonification panel 2026-08-17 (soir)".
     *
     * @return array<int, array{label: string, url: string, note: string|null}>|null
     */
    private function normalizePrimarySources(mixed $sourcesInput): ?array
    {
        if (! is_array($sourcesInput)) {
            $this->error('primary_sources doit être un tableau de sources.');

            return null;
        }

        if (count($sourcesInput) > 10) {
            $this->error('primary_sources dépasse la limite de 10 sources.');

            return null;
        }

        $normalized = [];

        foreach ($sourcesInput as $source) {
            if (! is_array($source) || ! isset($source['label'], $source['url'])
                || ! is_string($source['label']) || ! is_string($source['url'])) {
                $this->error('Chaque source de primary_sources doit contenir label et url (chaînes).');

                return null;
            }

            $url = trim($source['url']);
            if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
                $this->error("URL de source primaire invalide (http/https attendu) : « {$url} ».");

                return null;
            }

            $note = $source['note'] ?? null;
            if ($note !== null && ! is_string($note)) {
                $this->error('note de primary_sources doit être une chaîne de caractères si fournie.');

                return null;
            }

            $normalized[] = [
                'label' => $source['label'],
                'url' => $url,
                'note' => $note,
            ];
        }

        return $normalized;
    }

    /**
     * ACTION : implémentation /actu2 - volet serveur (2026-08-17) - valide et normalise la
     * citation statique d'un post X du payload : {text, author, handle, date, url}, tous des
     * chaînes. Utilisée quand l'ORIGINAL retrouvé par le skill est lui-même un post - jamais le
     * widget platform.x.com (script tiers interdit), une citation statique affichée par
     * show.blade.php. 'text' est la seule clé obligatoire (sans elle, rien à citer) ; 'url', si
     * fournie, doit être une URL http/https valide (lien « Voir sur X »).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc, section "Implémentation /actu2 - volet serveur (2026-08-17)".
     *
     * @return array{text: string, author?: string, handle?: string, date?: string, url?: string}|null
     */
    private function normalizeOriginalPost(mixed $postInput): ?array
    {
        if (! is_array($postInput)) {
            $this->error('original_post doit être un objet (text, author, handle, date, url).');

            return null;
        }

        $allowedKeys = ['text', 'author', 'handle', 'date', 'url'];
        $unknownKeys = array_diff(array_keys($postInput), $allowedKeys);
        if ($unknownKeys !== []) {
            $this->error('Clé(s) non autorisée(s) dans original_post : '.implode(', ', $unknownKeys).'. Clés permises : '.implode(', ', $allowedKeys).'.');

            return null;
        }

        $text = $postInput['text'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            $this->error('original_post.text est obligatoire (citation statique du post original).');

            return null;
        }
        if (mb_strlen($text) > 1000) {
            $this->error('original_post.text dépasse 1000 caractères.');

            return null;
        }

        $normalized = ['text' => $text];

        foreach (['author', 'handle', 'date'] as $key) {
            if (! array_key_exists($key, $postInput)) {
                continue;
            }
            if (! is_string($postInput[$key])) {
                $this->error("original_post.{$key} doit être une chaîne de caractères.");

                return null;
            }
            $normalized[$key] = $postInput[$key];
        }

        if (array_key_exists('url', $postInput)) {
            $url = is_string($postInput['url']) ? trim($postInput['url']) : '';
            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
                $this->error("original_post.url invalide (http/https attendu) : « {$url} ».");

                return null;
            }
            $normalized['url'] = $url;
        }

        return $normalized;
    }

    /**
     * Richesse v1.188.0 (design doc "Actus - composition manuelle assistée" 2026-08-15, section
     * "Richesse v1.188.0 - structure fixe composée") - valide et normalise composed_summary :
     * huit sous-clés nullables (hook, key_points, why_important, key_number, quote, angle_qc_ca,
     * action_concrete, reperes_dates), toute sous-clé inconnue fait refuser tout le payload,
     * chaînes bornées (~600 caractères sauf indication contraire par sous-structure). Le
     * marqueur `composed: true` N'EST PAS ajouté ici - il l'est par l'appelant (applyPayload()),
     * pour que cette méthode reste une simple validation/normalisation sans connaître le
     * contexte de stockage.
     * MCP: SELF (<5 lignes utiles par branche)
     * RAISON: design doc, section "Richesse v1.188.0" - liste blanche stricte, même doctrine que
     * les autres normalizeXxx() de cette commande.
     *
     * @return array<string, mixed>|null
     */
    private function normalizeComposedSummary(mixed $input): ?array
    {
        if (! is_array($input)) {
            $this->error('composed_summary doit être un objet.');

            return null;
        }

        $unknownKeys = array_diff(array_keys($input), self::ALLOWED_COMPOSED_SUMMARY_KEYS);
        if ($unknownKeys !== []) {
            $this->error('Clé(s) non autorisée(s) dans composed_summary : '.implode(', ', $unknownKeys).'. Clés permises : '.implode(', ', self::ALLOWED_COMPOSED_SUMMARY_KEYS).'.');

            return null;
        }

        $normalized = [];

        foreach (['hook', 'why_important', 'key_number', 'angle_qc_ca', 'action_concrete'] as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            if (! is_string($input[$key])) {
                $this->error("composed_summary.{$key} doit être une chaîne de caractères.");

                return null;
            }
            if (mb_strlen($input[$key]) > self::COMPOSED_SUMMARY_STRING_MAX) {
                $this->error("composed_summary.{$key} dépasse ".self::COMPOSED_SUMMARY_STRING_MAX.' caractères.');

                return null;
            }
            $normalized[$key] = $input[$key];
        }

        if (array_key_exists('key_points', $input)) {
            $points = $this->normalizeComposedKeyPoints($input['key_points']);
            if ($points === null) {
                // Message d'erreur déjà émis par normalizeComposedKeyPoints().
                return null;
            }
            $normalized['key_points'] = $points;
        }

        if (array_key_exists('quote', $input)) {
            $quote = $this->normalizeComposedQuote($input['quote']);
            if ($quote === null) {
                // Message d'erreur déjà émis par normalizeComposedQuote().
                return null;
            }
            $normalized['quote'] = $quote;
        }

        if (array_key_exists('reperes_dates', $input)) {
            $reperes = $this->normalizeComposedReperesDates($input['reperes_dates']);
            if ($reperes === null) {
                // Message d'erreur déjà émis par normalizeComposedReperesDates().
                return null;
            }
            $normalized['reperes_dates'] = $reperes;
        }

        return $normalized;
    }

    /**
     * Richesse v1.188.0 - composed_summary.key_points : au plus 5 puces, chacune au plus 300
     * caractères (design doc : "3-5 puces factuelles attribuées, 20-35 mots chacune").
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc, section "Richesse v1.188.0".
     *
     * @return array<int, string>|null
     */
    private function normalizeComposedKeyPoints(mixed $input): ?array
    {
        if (! is_array($input)) {
            $this->error('composed_summary.key_points doit être un tableau de chaînes.');

            return null;
        }

        if (count($input) > 5) {
            $this->error('composed_summary.key_points dépasse la limite de 5 puces.');

            return null;
        }

        $normalized = [];
        foreach ($input as $point) {
            if (! is_string($point)) {
                $this->error('Chaque élément de composed_summary.key_points doit être une chaîne de caractères.');

                return null;
            }
            if (mb_strlen($point) > 300) {
                $this->error('Un élément de composed_summary.key_points dépasse 300 caractères.');

                return null;
            }
            $normalized[] = $point;
        }

        return $normalized;
    }

    /**
     * Richesse v1.188.0 - composed_summary.quote : objet {text, author}, text obligatoire (une
     * citation sans texte n'a pas de sens), author facultatif. Bornes propres à cette
     * sous-structure (design doc : "une seule citation, locuteur et fonction identifiés").
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc, section "Richesse v1.188.0".
     *
     * @return array{text: string, author?: string}|null
     */
    private function normalizeComposedQuote(mixed $input): ?array
    {
        if (! is_array($input)) {
            $this->error('composed_summary.quote doit être un objet {text, author}.');

            return null;
        }

        $allowedKeys = ['text', 'author'];
        $unknownKeys = array_diff(array_keys($input), $allowedKeys);
        if ($unknownKeys !== []) {
            $this->error('Clé(s) non autorisée(s) dans composed_summary.quote : '.implode(', ', $unknownKeys).'. Clés permises : '.implode(', ', $allowedKeys).'.');

            return null;
        }

        $text = $input['text'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            $this->error('composed_summary.quote.text est obligatoire (citation).');

            return null;
        }
        if (mb_strlen($text) > 400) {
            $this->error('composed_summary.quote.text dépasse 400 caractères.');

            return null;
        }

        $normalized = ['text' => $text];

        if (array_key_exists('author', $input)) {
            if (! is_string($input['author'])) {
                $this->error('composed_summary.quote.author doit être une chaîne de caractères.');

                return null;
            }
            if (mb_strlen($input['author']) > 120) {
                $this->error('composed_summary.quote.author dépasse 120 caractères.');

                return null;
            }
            $normalized['author'] = $input['author'];
        }

        return $normalized;
    }

    /**
     * Richesse v1.188.0 - composed_summary.reperes_dates : au plus 4 jalons {date, texte, url?},
     * juxtaposés jamais causaux (design doc). date/texte obligatoires par jalon, url facultative
     * mais doit être http/https valide si fournie - même règle que primary_sources.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc, section "Richesse v1.188.0".
     *
     * @return array<int, array{date: string, texte: string, url?: string}>|null
     */
    private function normalizeComposedReperesDates(mixed $input): ?array
    {
        if (! is_array($input)) {
            $this->error('composed_summary.reperes_dates doit être un tableau.');

            return null;
        }

        if (count($input) > 4) {
            $this->error('composed_summary.reperes_dates dépasse la limite de 4 repères.');

            return null;
        }

        $normalized = [];
        foreach ($input as $repere) {
            if (! is_array($repere) || ! isset($repere['date'], $repere['texte'])
                || ! is_string($repere['date']) || ! is_string($repere['texte'])) {
                $this->error('Chaque repère de composed_summary.reperes_dates doit contenir date et texte (chaînes).');

                return null;
            }
            if (mb_strlen($repere['date']) > 40) {
                $this->error('composed_summary.reperes_dates : une date dépasse 40 caractères.');

                return null;
            }
            if (mb_strlen($repere['texte']) > 200) {
                $this->error('composed_summary.reperes_dates : un texte dépasse 200 caractères.');

                return null;
            }

            $entry = ['date' => $repere['date'], 'texte' => $repere['texte']];

            if (array_key_exists('url', $repere)) {
                $url = is_string($repere['url']) ? trim($repere['url']) : '';
                if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
                    $this->error("composed_summary.reperes_dates : url invalide (http/https attendu) : « {$url} ».");

                    return null;
                }
                $entry['url'] = $url;
            }

            $normalized[] = $entry;
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

        // Le héros de la fiche publique se rend depuis image_url : une fiche créée MANUELLEMENT
        // (flux /actu2, hors collecte RSS) ne l'a jamais reçu - sans ce renseignement, l'image
        // traitée existe sur disque mais reste invisible (trou trouvé au premier test réel,
        // fiche 33530, 2026-08-17). Jamais écrasé s'il est déjà rempli.
        if (blank($article->image_url)) {
            $article->update(['image_url' => $imageUrl]);
        }

        // Crédit photo appliqué AVEC l'image (option --credit) : le mode payload exige la
        // fraîcheur (updated_at), qui change dès la première écriture - le crédit voyage donc
        // avec l'image, jamais dans un second payload voué au refus.
        $credit = trim((string) $this->option('credit'));
        if ($credit !== '') {
            if (mb_strlen($credit) > 255) {
                $this->error('Le crédit photo dépasse 255 caractères.');

                return self::FAILURE;
            }
            $article->update(['image_credit' => $credit]);
        }

        Log::channel('composition')->info('news:apply - image appliquée', [
            'article_id' => $article->id,
            'image_credit' => $credit !== '' ? $credit : null,
            'prompt_version' => CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION,
        ]);

        $this->info("Fiche {$article->id} : image appliquée ({$imageUrl})".($credit !== '' ? " avec crédit « {$credit} »" : '').'.');

        return self::SUCCESS;
    }

    /**
     * Mode --publish (ajouté 2026-08-17, fin de journée - voir la note datée du doc-bloc de
     * classe) : publie la fiche par la porte bornée, seule alternative au bouton manuel
     * Publier-et-purger de l'écran de composition. Le refus « déjà publiée » est déjà couvert
     * plus haut dans handle() (avant même l'appel à cette méthode, quel que soit le mode
     * demandé) - cette méthode ne revérifie donc que les prérequis de CONTENU, exactement comme
     * NewsCompositionController::publish() après son propre refus 409.
     *
     * DÉLÈGUE la règle « prêt à publier » à NewsArticle::publishReadinessCheck() (DRY strict,
     * même méthode que le bouton manuel - AUCUNE divergence possible entre les deux chemins) et
     * la mécanique d'écriture à NewsArticle::publishAndPurgeSource() (même règle unique
     * « publier = purger » que tous les autres chemins de publication du projet).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: réutilise deux méthodes déjà extraites pour ce mandat, aucune logique nouvelle
     * inventée ici.
     */
    private function applyPublish(NewsArticle $article): int
    {
        $check = $article->publishReadinessCheck();

        if (! $check['ready']) {
            if ($check['missing'] !== []) {
                $this->error("Cette fiche n'est pas prête à être publiée : ".implode(', ', $check['missing']).' manquant(s).');
            } else {
                $this->error(NewsArticle::publishInvalidPairMessage($check['invalid_pair']));
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($article): void {
            $article->publishAndPurgeSource();
        });

        $siteUrl = url('/actualites/'.$article->slug);

        Log::channel('composition')->info('news:apply - publication par la porte bornée', [
            'article_id' => $article->id,
            'slug' => $article->slug,
            'prompt_version' => CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION,
        ]);

        $this->info("Fiche {$article->id} publiée : {$siteUrl}");

        return self::SUCCESS;
    }
}
