<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\News\Actions\NewsToolSyncAction;
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
 *                  image_credit / composed_summary depuis un fichier JSON. La clé `summary`
 *                  efface AUSSI structured_summary (résumé MACHINE de la collecte - addendum daté
 *                  2026-08-17, fin de journée, RESTREINT le 2026-08-28 à cette seule clé - voir
 *                  NewsArticle::logStructuredSummaryOverride() ; auparavant N'IMPORTE QUELLE clé
 *                  de contenu déclenchait l'effacement, ce qui détruisait en silence le résumé
 *                  riche de fiches touchées par un payload partiel n'ayant jamais eu l'intention
 *                  de toucher au résumé). primary_sources/image_credit ajoutés par la
 *                  bonification panel 2026-08-17 (soir, design doc) -
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
 * NOTE DATÉE 2026-08-19 (chantier enrichissement AdSense) - `--enrich` est la SEULE exception
 * au refus catégorique d'écrire sur une fiche déjà publiée (voir handle() plus bas) : elle
 * permet de recomposer le contenu (mode --payload, typiquement composed_summary) et/ou de
 * remplacer l'image (mode --image) d'une fiche DÉJÀ PUBLIÉE dont le référencement est déjà
 * bon, SANS jamais toucher à son slug/URL ni à son statut de publication - ni l'un ni l'autre
 * n'est jamais écrit par ce chemin. --enrich ne s'applique JAMAIS à --publish (sans objet sur
 * une fiche déjà publiée - la combinaison reste refusée comme avant).
 *
 * NOTE DATÉE 2026-08-27 (corriger un titre faux en ligne SANS changer l'adresse) - la clé
 * 'title' du payload, longtemps catégoriquement REFUSÉE en mode --enrich, est désormais
 * acceptée : le slug est une colonne STOCKÉE, régénérée par le seul appel explicite de
 * generateUniqueSlug() (applyPayload() ci-dessous), jamais recalculée automatiquement à chaque
 * écriture (NewsArticle::booted() ne la pose qu'à la CRÉATION). En mode --enrich, applyPayload()
 * saute cet appel : title s'écrit, le slug déjà référencé ne bouge jamais - exactement le même
 * garde-fou que seo_title (qui, lui, n'a jamais touché le slug et n'a donc jamais eu besoin
 * d'être refusé). La clé 'slug' elle-même reste hors de ALLOWED_PAYLOAD_KEYS, donc refusée dans
 * tous les modes, sans exception.
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
    private const ALLOWED_PAYLOAD_KEYS = ['expected_source_hash', 'expected_updated_at', 'title', 'seo_title', 'summary', 'editorial_proof_pairs', 'primary_sources', 'image_credit', 'nature_original', 'niveau_preuve', 'original_post', 'composed_summary', 'related_tool_slugs', 'entities', 'fact_check'];

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

    protected $signature = 'news:apply {article : id de la fiche news_articles} {--payload= : chemin d\'un fichier JSON de charge utile texte - efface aussi structured_summary (résumé machine), qui prime sinon sur ta composition côté fiche publique} {--image= : chemin d\'un fichier image local à appliquer} {--credit= : crédit photo appliqué avec --image (le payload exige la fraîcheur, qui change après la 1re écriture - le crédit voyage donc avec l\'image)} {--publish : publie la fiche - mêmes prérequis que le bouton manuel Publier-et-purger, refuse si déjà publiée} {--enrich : recompose une fiche DÉJÀ PUBLIÉE sans jamais changer son slug ni la dépublier (chantier enrichissement AdSense) - la clé title corrige le titre affiché, slug toujours intact}';

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

        $payloadPath = $this->option('payload');
        $imagePath = $this->option('image');
        $publish = (bool) $this->option('publish');
        $enrich = (bool) $this->option('enrich');

        // ACTION : refus systématique sur une fiche déjà publiée - garde-fou du panel de 5 IA.
        // SEULE exception (chantier enrichissement AdSense, 2026-08-19) : --enrich contourne CE
        // refus, et UNIQUEMENT quand --payload et/ou --image sont demandés (jamais --publish,
        // sans objet sur une fiche déjà publiée) - --enrich est le SEUL moyen d'écrire sur une
        // fiche publiée par cette porte, réservé à la recomposition de CONTENU. Le titre AFFICHÉ
        // peut désormais s'y corriger (clé title, applyPayload()) ; le slug/URL, lui, reste
        // TOUJOURS hors de portée (colonne jamais réécrite en mode --enrich, ni via title ni par
        // aucune autre clé - 'slug' n'a jamais fait partie de ALLOWED_PAYLOAD_KEYS).
        // MCP: SELF (<5 lignes)
        // RAISON: unique limite non négociable exigée par le mandat, vérifiée avant toute autre
        // logique des trois modes ci-dessous ; --enrich l'assouplit strictement dans le
        // périmètre documenté ci-dessus, jamais au-delà.
        if ($article->is_published && ! ($enrich && ! $publish && ($payloadPath || $imagePath))) {
            $this->error("La fiche {$article->id} est déjà publiée - news:apply refuse d'écrire sur une fiche publiée (sauf --enrich combiné à --payload et/ou --image, jamais --publish).");

            return self::FAILURE;
        }

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

        // ACTION : purger le cache de reponse de la page publique apres une ecriture sur une fiche
        // DEJA PUBLIEE. Sans cela, --enrich - dont c'est justement la raison d'etre, corriger une
        // fiche publiee - laisse la correction invisible jusqu'a l'expiration du cache Spatie
        // (7 jours). Mesure le 2026-08-26 : une correction typographique appliquee avec succes ne
        // paraissait pas, la page etant servie depuis le cache.
        // MCP: SELF (<5 lignes)
        // RAISON: reutilise NewsToolSyncAction::invalidatePublicCache(), purge CIBLEE deja en place
        //         (DRY) - jamais un ResponseCache::clear() global qui viderait tout le site.
        if ($article->fresh()?->is_published && ($payloadPath || $imagePath)) {
            NewsToolSyncAction::invalidatePublicCache($article);
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

        // ACTION : clé title (correctif systémique 2026-08-17 soir) - la fiche 33558 a été publiée
        // avec le titre/slug provisoires du brouillon car le slug n'est généré qu'à la CRÉATION
        // (NewsArticle::booted). Le cycle /actu2 décide du titre APRÈS la recherche : cette clé
        // lui permet de le poser par la porte, slug régénéré par la méthode canonique du modèle
        // (fiche brouillon garantie par le préflight : aucun churn d'URL publique).
        // MCP: SELF (bloc court calqué sur seo_title)
        // RAISON: défaut réel observé en prod (journal, entrée 115) ; jamais deux implémentations
        //         de la règle de slug.
        //
        // ACTION : --enrich (correctif 2026-08-27, chantier « corriger un titre faux en ligne ») -
        // le slug est une COLONNE STOCKÉE, régénérée UNIQUEMENT par l'appel explicite ci-dessous
        // (jamais recalculée automatiquement à chaque écriture, cf. NewsArticle::booted() qui ne
        // la pose qu'à la création). En mode --enrich, cette même clé title est donc désormais
        // acceptée sur une fiche déjà publiée, mais l'appel à generateUniqueSlug() est SAUTÉ : le
        // titre affiché se corrige, l'adresse déjà référencée ne bouge jamais. Hors --enrich
        // (fiche encore brouillon), le comportement historique est inchangé : le slug continue de
        // suivre le titre jusqu'à publication.
        // MCP: SELF (<5 lignes)
        // RAISON: seule seo_title corrigeait un titre publié sans toucher au slug ; title lui-même
        //         restait catégoriquement refusé en --enrich, alors que le slug ne dépend du titre
        //         qu'à cet unique point d'appel - jamais casser un lien entrant, jamais laisser un
        //         titre faux en ligne faute d'un garde-fou devenu trop large.
        if (array_key_exists('title', $decoded)) {
            if (! is_string($decoded['title']) || trim($decoded['title']) === '' || mb_strlen($decoded['title']) > 200) {
                $this->error('title doit être une chaîne non vide de 200 caractères maximum.');

                return self::FAILURE;
            }
            $updates['title'] = trim($decoded['title']);
            if (! $this->option('enrich')) {
                $updates['slug'] = NewsArticle::generateUniqueSlug($updates['title'], $article->id);
            }
        }

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
            // ACTION : retrait explicite d'une paire de preuve (mandat daté 2026-08-28, traité
            // conjointement avec l'override de structured_summary plus bas - deux faces du même
            // contrat de payload). Jusqu'ici, cette clé n'acceptait QUE l'ajout : impossible de
            // retirer une paire déjà publiée qui se révèle problématique (ex. une donnée de
            // santé sur une personne nommée) - `null` était refusé (échec is_array) et un
            // tableau vide ne faisait rien (fusion avec rien). Le précédent existait déjà sur ce
            // même payload : `fact_check` (plus bas) accepte `null` comme signal EXPLICITE de
            // retrait, jamais une absence de clé qui n'y touche pas. Cette clé reprend
            // EXACTEMENT la même convention plutôt que d'en inventer une seconde - `null` retire
            // TOUTES les paires existantes (même grain que fact_check : un retrait, pas une
            // édition champ par champ). Pour ne garder que les paires légitimes, un second
            // payload les réapplique ensuite par l'accumulation existante ci-dessous - aucune
            // mécanique nouvelle à apprendre.
            // MCP: SELF (<5 lignes utiles)
            // RAISON: mandat 2026-08-28 - « on ne peut RIEN RETIRER, et une donnée sensible est
            //         publiée à cause de ça » ; convention réutilisée depuis fact_check, jamais
            //         inventée.
            if ($decoded['editorial_proof_pairs'] === null) {
                $updates['editorial_proof_pairs'] = [];
            } else {
                // ACTION : validation PAR PAIRE INDÉPENDANTE (correctif todo #1984, 2026-08-28) -
                // avant ce correctif, normalizeProofPairs() interrompait sa boucle et rejetait le
                // TABLEAU ENTIER dès la première paire invalide, y compris les paires valides du
                // même lot (mesuré : 2 paires invalides sur 15 soumises faisaient échouer les 15).
                // Chaque paire est désormais jugée indépendamment ('accepted'/'rejected'), avec le
                // motif précis de chaque refus - jamais un rejet muet, jamais un lot qui meurt
                // entier pour un seul élément. normalizeProofPairs() ne retourne plus null que pour
                // un payload structurellement invalide (editorial_proof_pairs n'est même pas un
                // tableau) - un cas qui, lui, reste all-or-nothing (rien à évaluer paire par paire).
                // MCP: SELF (<5 lignes utiles)
                // RAISON: todo #1984 - « une paire de preuve ne peut plus être ajoutée ni
                //         réappliquée sur une fiche déjà publiée » ; défaut réel mesuré sur un lot
                //         de 15 paires (2 invalides ont fait échouer les 15).
                $normalizedPairs = $this->normalizeProofPairs($decoded['editorial_proof_pairs'], (string) $article->internal_source_text);
                if ($normalizedPairs === null) {
                    // Message d'erreur déjà émis par normalizeProofPairs().
                    return self::FAILURE;
                }

                foreach ($normalizedPairs['rejected'] as $rejet) {
                    $this->error("editorial_proof_pairs - paire #{$rejet['position']} refusée (« {$rejet['statement']} ») : {$rejet['reason']}");
                }

                // ACTION : texte source purgé (fiche déjà publiée via --enrich, chantier « zéro
                // copie » - NewsArticle::publishAndPurgeSource() met internal_source_text à null).
                // Le contrôle « citation retrouvée dans la source » ne PEUT alors plus s'exécuter :
                // ce n'est pas un échec de validation, c'est un contrôle qui ne s'applique pas -
                // mais il est signalé ici explicitement, jamais ignoré en silence. Le marqueur
                // 'source_verified' => false, posé par normalizeProofPairs(), rend aussi la paire
                // visible comme telle dans les données persistées, pas seulement dans cette sortie
                // console éphémère.
                // MCP: SELF (<5 lignes utiles)
                // RAISON: todo #1984 - hypothèse confirmée par lecture du code (EditorialProofNormalizer::
                //         containsExact() contre une chaîne vide ne peut jamais réussir).
                foreach ($normalizedPairs['accepted'] as $paire) {
                    if (($paire['source_verified'] ?? true) === false) {
                        $this->warn("editorial_proof_pairs - paire « {$paire['statement']} » acceptée SANS vérification possible : le texte source de cette fiche est purgé (fiche déjà publiée). Le contrôle de sous-chaîne exacte ne s'applique pas ici.");
                    }
                }

                // ACTION : zéro régression sur le chemin normal - si AUCUNE paire du lot n'est
                // valide, il n'y a rien à appliquer pour cette clé, exactement comme avant ce
                // correctif (même issue : échec de la commande, rien n'est écrit). Seule la
                // présence d'AU MOINS une paire valide dans le même lot change désormais l'issue
                // globale (elle est appliquée, les invalides sont rapportées mais n'empêchent plus
                // rien).
                // MCP: SELF (<5 lignes utiles)
                // RAISON: préserve exactement les tests existants (paire seule invalide → échec).
                if ($normalizedPairs['accepted'] === [] && $normalizedPairs['rejected'] !== []) {
                    $this->error('editorial_proof_pairs : aucune paire valide dans ce lot - rien n\'a été appliqué pour cette clé.');

                    return self::FAILURE;
                }

                // ACTION : les nouvelles paires COMPLÈTENT les paires existantes (jamais un
                // remplacement intégral) - une fiche peut déjà porter des paires ajoutées à la main
                // via l'écran (storeProofPair), et cette commande ne doit jamais les faire
                // disparaître en silence.
                // MCP: SELF (<5 lignes)
                // RAISON: même sémantique d'accumulation que storeProofPair() côté contrôleur.
                $updates['editorial_proof_pairs'] = array_merge($article->editorial_proof_pairs ?? [], $normalizedPairs['accepted']);
            }
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

        // Module « vérification » (2026-08-21) : les trois colonnes du verdict voyagent dans leur
        // PROPRE panier, jamais dans $updates. Raison précise, et c'est un piège déjà rencontré
        // avec related_tool_slugs : tout payload de CONTENU efface structured_summary (le résumé
        // composé) plus bas. Un verdict est une méta-donnée posée souvent APRÈS coup, sur une
        // fiche déjà composée - le ranger avec le contenu détruirait silencieusement son résumé.
        $factCheckUpdates = [];

        // Module « vérification » (2026-08-21) : clé UNIQUE 'fact_check' portant l'objet complet
        // {verdict, claim, source}. Le vocabulaire des verdicts vit dans le modèle
        // (NewsArticle::FACT_CHECK_VERDICTS), jamais recopié ici : la porte valide contre lui.
        // Passer fact_check à null EFFACE la vérification - c'est le seul moyen de retirer un
        // verdict posé par erreur, et il reste borné à cette porte.
        if (array_key_exists('fact_check', $decoded)) {
            $factCheck = $decoded['fact_check'];

            if ($factCheck === null) {
                $factCheckUpdates['fact_check_verdict'] = null;
                $factCheckUpdates['fact_check_claim'] = null;
                $factCheckUpdates['fact_check_source'] = null;
            } else {
                $allowedVerdicts = array_keys(NewsArticle::FACT_CHECK_VERDICTS);

                // Sous-clés en liste blanche, même doctrine que composed_summary : une clé mal
                // orthographiée (« souce ») serait sinon ignorée EN SILENCE, et l'agent croirait
                // avoir posé une source qui n'existe pas. Refus explicite, jamais un oubli muet.
                if (is_array($factCheck)) {
                    $inconnues = array_diff(array_keys($factCheck), ['verdict', 'claim', 'source']);

                    if ($inconnues !== []) {
                        $this->error('fact_check : sous-clé(s) inconnue(s) refusée(s) : '.implode(', ', $inconnues).' (attendu : verdict, claim, source).');

                        return self::FAILURE;
                    }
                }

                if (! is_array($factCheck)
                    || ! isset($factCheck['verdict'], $factCheck['claim'])
                    || ! is_string($factCheck['verdict'])
                    || ! in_array($factCheck['verdict'], $allowedVerdicts, true)) {
                    $this->error('fact_check invalide : verdict attendu parmi '.implode(', ', $allowedVerdicts).', avec une clé claim.');

                    return self::FAILURE;
                }

                if (! is_string($factCheck['claim']) || trim($factCheck['claim']) === '' || mb_strlen($factCheck['claim']) > 300) {
                    $this->error('fact_check.claim doit être une chaîne non vide de 300 caractères maximum (affirmation examinée, en une phrase).');

                    return self::FAILURE;
                }

                // La source est facultative. Deux garde-fous, pour deux risques distincts.
                //
                // 1. SCHÉMA : filter_var(FILTER_VALIDATE_URL) accepte `javascript://...`, qui
                //    deviendrait exécutable dans le href du badge public au premier clic. Seuls
                //    http et https passent - refus explicite pour tout le reste. (Relevé par une
                //    relecture adversariale du diff avant déploiement, 2026-08-21.)
                // 2. ABSENCE ≠ EFFACEMENT : une clé `source` simplement absente laisse la source
                //    déjà enregistrée INTACTE ; seul un `source: null` explicite l'efface. Sans
                //    cette distinction, recomposer un verdict sans repréciser la source aurait
                //    silencieusement perdu le lien vers la publication d'origine.
                $sourceFournie = array_key_exists('source', $factCheck);
                $claimSource = $sourceFournie ? $factCheck['source'] : null;

                if ($sourceFournie && $claimSource !== null) {
                    $schema = is_string($claimSource) ? mb_strtolower((string) parse_url($claimSource, PHP_URL_SCHEME)) : '';

                    if (! is_string($claimSource) || ! filter_var($claimSource, FILTER_VALIDATE_URL) || ! in_array($schema, ['http', 'https'], true)) {
                        $this->error('fact_check.source doit être une URL http(s) valide (la publication où circule l\'affirmation), ou être absente.');

                        return self::FAILURE;
                    }

                    // La colonne accepte 2048 caractères : au-delà, l'écriture échouerait APRÈS
                    // que le contenu du même payload a déjà été commité (deux paniers, deux
                    // transactions). On refuse donc AVANT d'écrire quoi que ce soit.
                    if (mb_strlen($claimSource) > 2048) {
                        $this->error('fact_check.source dépasse 2048 caractères (limite de la colonne).');

                        return self::FAILURE;
                    }
                }

                $factCheckUpdates['fact_check_verdict'] = $factCheck['verdict'];
                $factCheckUpdates['fact_check_claim'] = trim($factCheck['claim']);

                if ($sourceFournie) {
                    $factCheckUpdates['fact_check_source'] = $claimSource;
                }
            }
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

        // ACTION : intégration « Outils liés » (demande fondateur 2026-08-17 soir) - la clé
        // related_tool_slugs permet au cycle /actu2 de CURATER les outils de l'annuaire liés à la
        // fiche. C'est un pivot (jamais une colonne de $updates) : résolution par slug contre les
        // outils PUBLIÉS seulement, ajout PUR via NewsToolSyncAction::attachAuto() (n'écrase
        // JAMAIS une sélection admin), slugs inconnus signalés explicitement - jamais en silence.
        // MCP: multi-ai-mcp→qwen3-max (validé + corrigé par le superviseur)
        // RAISON: l'auto-détection par mots-clés ne voit que les noms exacts ; l'agent, lui,
        //         sait quels outils sont réellement au coeur de l'actu.
        $relatedToolSlugs = null;
        if (array_key_exists('related_tool_slugs', $decoded)) {
            $value = $decoded['related_tool_slugs'];
            if (! is_array($value)) {
                $this->error('related_tool_slugs doit être un tableau de slugs.');

                return self::FAILURE;
            }
            if (count($value) > 10) {
                $this->error('related_tool_slugs dépasse la limite de 10 slugs.');

                return self::FAILURE;
            }
            foreach ($value as $slug) {
                if (! is_string($slug) || trim($slug) === '' || mb_strlen($slug) > 120) {
                    $this->error('Chaque slug de related_tool_slugs doit être une chaîne non vide de 120 caractères maximum.');

                    return self::FAILURE;
                }
            }
            $relatedToolSlugs = array_values($value);
        }

        // ACTION : clé entities (connexes par entités partagées, arbitrage panel 2026-08-17) -
        // curation des entités nommées CENTRALES de la fiche par le cycle /actu2. Pivot, jamais
        // une colonne de $updates ; remplacement complet via syncEntities() (normalisation slug).
        // MCP: hermes→deepseek-v4-flash (validé + adapté par le superviseur)
        // RAISON: connexes réellement pertinents sans modération, sans NER machine.
        $entities = null;
        if (array_key_exists('entities', $decoded)) {
            $value = $decoded['entities'];
            if (! is_array($value)) {
                $this->error('entities doit être un tableau de libellés.');

                return self::FAILURE;
            }
            if (count($value) > 10) {
                $this->error('entities dépasse la limite de 10 entités.');

                return self::FAILURE;
            }
            foreach ($value as $item) {
                if (! is_string($item) || trim($item) === '' || mb_strlen($item) > 120) {
                    $this->error('Chaque entité doit être une chaîne non vide de 120 caractères maximum.');

                    return self::FAILURE;
                }
            }
            $entities = array_values($value);
        }

        // ACTION : module « signature éditoriale » (signal humain E-E-A-T vérifiable, design doc
        // SPEC-SIGNAL-HUMAIN, club des sages 93/100, 2026-08-20). 'reviewed_at'/'reviewed_by' ne
        // sont TOUJOURS PAS exposés dans ALLOWED_PAYLOAD_KEYS : l'agent ne peut jamais fabriquer
        // sa propre date de relecture. Ce garde-fou reste entier ; ce qui change ci-dessous, c'est
        // que la porte ne la pose plus non plus à sa place.
        //
        // 2026-08-21 : la porte NE POSE PLUS reviewed_at. Le bloc précédent le posait dès qu'un
        // contenu composé arrivait avec ses preuves, si bien que la fiche affichait « Vérifié par
        // la rédaction le [date] » sans qu'aucun humain ne l'ait lue - alors que la page publique
        // /methodologie promet « relue par la rédaction [...] jamais une date dérivée
        // automatiquement ». La signature est désormais posée UNIQUEMENT par un geste humain de
        // l'écran d'administration, via l'unique point d'écriture NewsArticle::markReviewedByHuman()
        // (publication manuelle, ou bouton « J'ai relu »). L'agent compose et publie ; il ne signe
        // pas à la place d'un humain. Une fiche sans signature n'est pas un défaut : c'est
        // simplement une fiche qui n'a pas encore été relue, et le composant editorial-signature
        // ne rend alors rien du tout.

        if ($updates === [] && $relatedToolSlugs === null && $entities === null && $factCheckUpdates === []) {
            $this->error('Payload sans effet : aucune des clés seo_title / summary / editorial_proof_pairs / primary_sources / image_credit / nature_original / niveau_preuve / original_post / composed_summary / related_tool_slugs / entities / fact_check n\'est fournie.');

            return self::FAILURE;
        }

        // ACTION : addendum daté 2026-08-17 (fin de journée) - structured_summary (résumé
        // MACHINE de la collecte, prioritaire sur summary côté fiche publique, cf. show.blade.php
        // bloc @if($ss) ... @elseif($article->summary)) est effacé quand la fiche reçoit un
        // NOUVEAU résumé public qui doit désormais primer : la composition manuelle fait
        // autorité sur le résumé affiché. logStructuredSummaryOverride() journalise l'ancienne
        // valeur AVANT l'effacement, même méthode réutilisée telle quelle par
        // NewsCompositionController::publish() (DRY).
        // MCP: SELF (<5 lignes)
        // RAISON: correctif ciblé, réutilise le point unique déjà extrait sur le modèle.
        //
        // ACTION : correctif daté 2026-08-28 (mandat conjoint avec le retrait de paire de preuve
        // ci-dessus) - RESTREINT le déclencheur : seule la clé 'summary' (le texte de repli qui
        // rivalise réellement avec structured_summary à l'affichage) ou 'composed_summary' (qui
        // écrit structured_summary DIRECTEMENT ci-dessus, donc déjà présent dans $updates) fait
        // désormais basculer l'affichage. AVANT ce correctif, la condition était `$updates !==
        // []` : N'IMPORTE QUELLE clé de contenu (image_credit, nature_original, niveau_preuve,
        // un titre corrigé, un RETRAIT de paire de preuve...) effaçait structured_summary dès que
        // hasComposedSummary() était faux - ce qui a détruit en silence le résumé riche
        // d'environ 4400 fiches d'avant /actu2 lors d'un enrichissement partiel n'ayant jamais eu
        // l'intention de toucher au résumé. Règle absolue du projet : un champ ABSENT du payload
        // signifie « je n'y touche pas », jamais « efface-le » - seule une clé qui remplace
        // vraiment le résumé public affiché doit faire céder l'ancien résumé machine. Les trois
        // tests dédiés (ComposedSummaryApplyTest, FactCheckModuleTest) qui vérifient qu'un
        // payload portant `summary` efface toujours le résumé machine restent verts : cette
        // clé précise n'a jamais cessé de déclencher l'effacement.
        // MCP: SELF (<5 lignes utiles)
        // RAISON: mandat 2026-08-28 - « on ne supprime jamais de données utilisateurs » ; défaut
        //         mesuré sur ~4400 fiches vivantes.
        //
        // ACTION : Richesse v1.188.0 - si composed_summary vient d'écrire structured_summary
        // ci-dessus, ne PAS l'écraser à null ici (seule la journalisation de l'ANCIENNE valeur
        // reste inconditionnelle - jamais perdue en silence, même quand elle est remplacée par
        // une composition plutôt qu'effacée).
        // MCP: SELF (<5 lignes)
        // RAISON: design doc, section "Richesse v1.188.0" - "il le REMPLACE par la version composée".
        // ACTION : intégration « Outils liés » - un payload ne portant QUE related_tool_slugs ne
        // doit ni journaliser un override, ni effacer structured_summary (l'addendum ci-dessus ne
        // vaut que pour un payload de CONTENU) : tout le bloc écriture est donc gardé.
        // MCP: SELF (<5 lignes de garde)
        // RAISON: sans cette garde, une curation d'outils après coup détruirait le résumé composé.
        if ($updates !== []) {
            // ACTION : voir le bloc de commentaires ci-dessus (correctif 2026-08-28) - le
            // déclencheur precis du basculement d'affichage, jamais `$updates !== []` seul.
            // MCP: SELF (<5 lignes)
            // RAISON: DRY - un seul point de calcul de cette condition, réutilisé par le log et
            //         par l'effacement plutôt que dupliqué entre les deux.
            $remplaceLeResumeAffiche = array_key_exists('summary', $decoded) || array_key_exists('structured_summary', $updates);

            if ($remplaceLeResumeAffiche) {
                $article->logStructuredSummaryOverride();
                // ACTION : garde-fou symetrique de NewsCompositionController::publish() - l'effacement
                // ci-dessous vise le resume MACHINE de la collecte, jamais un resume COMPOSE. Sans
                // cette condition, un SECOND payload partiel (un titre corrige, une curation) detruit
                // silencieusement la composition riche ecrite par le payload precedent.
                // MCP: SELF (<5 lignes)
                // RAISON: hasComposedSummary() est le point UNIQUE de cette distinction (DRY) - il
                // gardait deja le bouton manuel Publier-et-purger, il manquait a la porte de l'agent.
                if (! array_key_exists('structured_summary', $updates) && ! $article->hasComposedSummary()) {
                    $updates['structured_summary'] = null;
                }
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
        }

        // Verdict de vérification appliqué à part (voir la note du panier plus haut) : jamais
        // d'effacement de structured_summary, jamais de journal d'override - poser ou retirer un
        // verdict ne touche à rien d'autre sur la fiche.
        if ($factCheckUpdates !== []) {
            DB::transaction(function () use ($article, $factCheckUpdates): void {
                $article->update($factCheckUpdates);
            });

            Log::channel('composition')->info('news:apply - verdict de vérification appliqué', [
                'article_id' => $article->id,
                'verdict' => $factCheckUpdates['fact_check_verdict'],
            ]);

            $this->info("Fiche {$article->id} : verdict de vérification ".($factCheckUpdates['fact_check_verdict'] ?? 'retiré').'.');
        }

        if ($relatedToolSlugs !== null) {
            $this->attachRelatedTools($article, $relatedToolSlugs);
        }

        if ($entities !== null) {
            $article->syncEntities($entities);
            $this->info("Fiche {$article->id} : ".$article->entities()->count().' entité(s) enregistrée(s).');
        }

        return self::SUCCESS;
    }

    /**
     * ACTION : intégration « Outils liés » - résout les slugs (traduisibles Spatie) contre les
     * outils PUBLIÉS de l'annuaire et les attache en ajout PUR (source=auto, attachAuto() ne
     * touche jamais une liaison existante, manuelle ou automatique). Slugs introuvables signalés
     * explicitement en sortie - jamais un échec (l'auto-détection à la publication complète), et
     * jamais un silence.
     * MCP: multi-ai-mcp→qwen3-max (validé + corrigé par le superviseur)
     * RAISON: demande fondateur 2026-08-17 - « actu2 doit aussi bien intégrer Outils liés ».
     */
    private function attachRelatedTools(NewsArticle $article, array $slugs): void
    {
        if (! class_exists(\Modules\Directory\Models\Tool::class)) {
            $this->warn('related_tool_slugs ignoré : le module Directory est désactivé.');

            return;
        }

        $tools = \Modules\Directory\Models\Tool::published()->get(['id', 'slug', 'name']);
        $resolvedIds = [];
        $resolvedNames = [];
        $unknownSlugs = [];

        foreach ($slugs as $slug) {
            $match = $tools->first(fn ($tool) => in_array($slug, $tool->getTranslations('slug'), true));
            if ($match === null) {
                $unknownSlugs[] = $slug;

                continue;
            }
            $resolvedIds[] = (int) $match->id;
            $resolvedNames[] = $match->getTranslation('name', 'fr_CA', false)
                ?: $match->getTranslation('name', 'en', false)
                ?: $slug;
        }

        if ($unknownSlugs !== []) {
            $this->warn('related_tool_slugs ignoré(s) - slug(s) introuvable(s) dans l\'annuaire publié : '.implode(', ', $unknownSlugs).'.');
        }

        if ($resolvedIds !== []) {
            $attached = app(\Modules\News\Actions\NewsToolSyncAction::class)
                ->attachAuto($article, collect($resolvedIds));
            \Modules\News\Actions\NewsToolSyncAction::invalidatePublicCache($article);
            $this->info("Fiche {$article->id} : {$attached} outil(s) lié(s) (".implode(', ', $resolvedNames).').');
        }
    }

    /**
     * Valide et normalise le tableau de paires de preuve éditoriale du payload. Retourne null (et
     * émet le message d'erreur) uniquement si $pairsInput n'est même pas un tableau - un défaut
     * structurel du payload entier, rien à évaluer paire par paire. Sinon, retourne TOUJOURS un
     * tableau avec deux clés 'accepted' et 'rejected' : chaque paire est jugée INDÉPENDAMMENT.
     *
     * ACTION : correctif todo #1984 (2026-08-28) - AVANT ce correctif, cette méthode retournait
     * null (et arrêtait la boucle) dès la PREMIÈRE paire invalide, rejetant même les paires
     * valides du même lot qui n'avaient pas encore été atteintes par le foreach. Défaut mesuré à
     * l'usage : sur un lot de 15 paires soumises, 2 paires invalides faisaient échouer les 15,
     * aucune des 13 valides n'était jamais appliquée. Chaque paire est désormais acceptée ou
     * refusée pour elle-même, avec le motif précis du refus - jamais un rejet muet, jamais un lot
     * qui meurt entier pour un seul élément invalide. L'appelant (applyPayload()) décide ensuite :
     * les paires 'accepted' sont appliquées, les 'rejected' sont rapportées mais n'empêchent plus
     * rien - SAUF si le lot entier est rejeté (zéro paire valide), auquel cas l'issue reste
     * exactement celle d'avant ce correctif (voir applyPayload()).
     *
     * ACTION : correctif todo #1984 (même mandat) - une paire "fact" exige normalement que son
     * excerpt soit une sous-chaîne exacte de $sourceText (règle inchangée, toujours appliquée dès
     * que ce texte est présent - AUCUNE régression sur ce chemin). MAIS sur une fiche DÉJÀ
     * PUBLIÉE, NewsArticle::publishAndPurgeSource() met internal_source_text à null (chantier
     * « zéro copie ») : $sourceText arrive alors vide, et EditorialProofNormalizer::containsExact()
     * contre une chaîne vide ne peut JAMAIS réussir (needle non vide contre haystack vide) - quelle
     * que soit la légitimité de la citation. Ce n'est pas un échec de validation, c'est un
     * contrôle qui ne PEUT plus s'exécuter : quand $sourceText est vide, la paire "fact" est
     * ACCEPTÉE sans revalidation, mais marquée 'source_verified' => false - jamais acceptée en
     * silence comme si elle avait été vérifiée. applyPayload() relaie ce marqueur dans sa sortie
     * console ; il survit aussi dans les données persistées (visible pour toute relecture future).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: todo #1984 - hypothèse confirmée par lecture du code ; deux causes distinctes,
     *         corrigées ensemble car elles cohabitent dans la même méthode.
     *
     * Réutilise EditorialProofNormalizer::containsExact(), même règle que
     * NewsCompositionController::storeProofPair() : une paire "fact" doit être une sous-chaîne
     * exacte du texte source (quand ce texte existe encore).
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
     * @return array{
     *     accepted: array<int, array{id: string, statement: string, excerpt: string, type: string, created_at: string, source_url?: string, source_verified?: bool}>,
     *     rejected: array<int, array{position: int, statement: string, reason: string}>
     * }|null
     */
    private function normalizeProofPairs(mixed $pairsInput, string $sourceText): ?array
    {
        if (! is_array($pairsInput)) {
            $this->error('editorial_proof_pairs doit être un tableau de paires.');

            return null;
        }

        // Texte source purgé (fiche déjà publiée) : trim() évite qu'une chaîne blanche
        // ("   ") soit traitée comme une source exploitable - même exigence que blank().
        $sourceTextDisponible = trim($sourceText) !== '';

        $accepted = [];
        $rejected = [];

        foreach (array_values($pairsInput) as $index => $pair) {
            $position = $index + 1;
            $libelle = (is_array($pair) && is_string($pair['statement'] ?? null)) ? $pair['statement'] : '(paire malformée)';

            if (! is_array($pair) || ! isset($pair['statement'], $pair['excerpt'], $pair['type'])
                || ! is_string($pair['statement']) || ! is_string($pair['excerpt']) || ! is_string($pair['type'])) {
                $rejected[] = ['position' => $position, 'statement' => $libelle, 'reason' => 'doit contenir statement, excerpt et type (chaînes).'];

                continue;
            }

            if (! in_array($pair['type'], ['fact', 'analysis', 'primary_fact'], true)) {
                $rejected[] = ['position' => $position, 'statement' => $libelle, 'reason' => "type invalide : « {$pair['type']} » (attendu : fact, analysis ou primary_fact)."];

                continue;
            }

            // null = type autre que "fact" (vérification sans objet) ; true/false sinon.
            $sourceVerifiee = null;

            if ($pair['type'] === 'fact') {
                if (! $sourceTextDisponible) {
                    $sourceVerifiee = false;
                } elseif (! EditorialProofNormalizer::containsExact($sourceText, $pair['excerpt'])) {
                    $rejected[] = ['position' => $position, 'statement' => $libelle, 'reason' => "extrait déclaré « fait » absent du texte source (sous-chaîne exacte attendue) : {$pair['excerpt']}"];

                    continue;
                } else {
                    $sourceVerifiee = true;
                }
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
                    $rejected[] = ['position' => $position, 'statement' => $libelle, 'reason' => 'paire « primary_fact » sans URL de source primaire valide (http/https).'];

                    continue;
                }
                $entry['source_url'] = $sourceUrl;
            }

            if ($sourceVerifiee === false) {
                $entry['source_verified'] = false;
            }

            $accepted[] = $entry;
        }

        return ['accepted' => $accepted, 'rejected' => $rejected];
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
