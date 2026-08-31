<?php

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Community\Traits\HasComments;
use Modules\Community\Traits\HasReports;
use Modules\Core\Concerns\HasAdminShareContents;
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\HasPublishedState;
use Modules\Core\Traits\LogsActivityStandard;
use Modules\News\Services\EditorialProofNormalizer;
use Modules\Voting\Traits\HasCommunityVotes;

class NewsArticle extends Model implements Searchable
{
    // ACTION : le trait expose publicShareTexts(array $matiere, string $url) ; NewsArticle a
    // besoin d'une méthode SANS argument du même nom (appelée par show.blade.php) - aliasée en
    // sharedPublicShareTexts() pour éviter la collision (une méthode de classe masque toujours
    // la méthode de trait de même nom en PHP ; l'alias reste le seul moyen de l'appeler encore).
    // RAISON: mandat explicite (trait générique inchangé pour Blog/Directory/Tools/Dictionary/Acronyms).
    use HasAdminShareContents {
        publicShareTexts as protected sharedPublicShareTexts;
    }
    use HasComments, HasReports, HasCommunityVotes;
    use HasPublishedState;
    use LogsActivityStandard;
    use \Modules\SEO\Traits\NotifiesIndexNow;

    // ACTION : 'description' retiré des champs journalisés (design doc "Actus - zéro copie du
    // texte source", 2026-08-13, section 5 étape 4) - cette colonne va être purgée (texte
    // intégral des articles sources, 32 840 lignes) ; la laisser journalisée aurait recopié
    // chaque valeur dans activity_log au moment même de la purge, recréant le problème dans
    // une table que personne ne surveille. Cette étape doit précéder la purge de la colonne.
    // MCP: SELF (<5 lignes)
    // RAISON: étape 4 de la procédure de purge, bloquante avant les étapes 5 et 6.
    // ACTION : 'internal_source_text' (écran de composition, design doc "Actus - composition
    // manuelle assistée" 2026-08-15, section 5.2) est VOLONTAIREMENT absent de ce tableau, pour
    // la même raison que 'description' ci-dessus : logger ce champ recopierait le texte source
    // intégral dans activity_log à chaque sauvegarde ou suppression, recréant l'incident que la
    // purge de 'description' a corrigé - dans une table que personne ne surveille.
    // MCP: SELF (<5 lignes)
    // RAISON: garde-fou zéro-copie, cohérent avec l'exclusion déjà en place pour 'description'.
    protected array $activitylogFields = ['title', 'seo_title', 'summary', 'is_published', 'published_at', 'relevance_score'];
    protected string $activitylogName = 'news_article';

    public function getPublicUrl(): string
    {
        return url('/actualites/' . $this->slug);
    }
    protected $fillable = [
        'news_source_id', 'title', 'slug', 'guid', 'url', 'resolved_url', 'description',
        'summary', 'image_url', 'author', 'pub_date', 'is_published',
        'relevance_score', 'score_justification', 'structured_summary',
        'category_tag', 'impact_level', 'feed_type', 'seo_title', 'meta_description',
        'short_url_id', 'views_count', 'canonical_url', 'is_potential_duplicate_of', 'dedup_score', 'dedup_reason',
        'seo_status', // index | noindex | gone - élagage SEO réversible des vieilles news peu vues
        'linkedin_shared_at', 'facebook_shared_at', // tracking "déjà publié" (admin, point rouge)
        'is_comparative_digest', // Actus 2.0 : fiche comparative issue de la fusion multi-sources
        // ACTION : écran de composition (design doc "Actus - composition manuelle assistée"
        // 2026-08-15, section 5.2) - texte source collé par l'admin, back-office UNIQUEMENT.
        // Jamais lu par NewsArticle::searchableFields(), jamais par les cascades d'affichage
        // (displayExcerpt/structuredBodyText), jamais par JsonLdService - voir
        // Modules\News\Http\Controllers\Admin\NewsCompositionController pour le seul point
        // d'écriture/lecture admin.
        // MCP: SELF (<5 lignes)
        // RAISON: emplacement distinct explicite exigé (ne PAS réutiliser 'description', purgée).
        'internal_source_text',
        // ACTION : fiche de preuve éditoriale (design doc "Actus - composition manuelle
        // assistée" 2026-08-15, section 7 / Phase B) - paires {phrase publiée, extrait exact du
        // texte source, décision fait/analyse}, JSON interne. Même garde-fou que
        // 'internal_source_text' juste au-dessus : jamais lu par un chemin public, jamais
        // journalisé (absent de $activitylogFields), et volontairement absent de
        // NewsCompositionController::candidates().
        // MCP: SELF (<5 lignes)
        // RAISON: emplacement distinct explicite, cohérent avec internal_source_text.
        'editorial_proof_pairs',
        // ACTION : complément de conservation (design doc "Actus - composition manuelle
        // assistée" 2026-08-15, section 5.2) - date de capture et empreinte SHA-256 du texte
        // source, calculées par NewsCompositionController::update(). SURVIVENT à la suppression
        // de 'internal_source_text' (jamais écrites par destroySourceText()) : avec les extraits
        // des paires de preuve, elles font foi une fois le texte intégral supprimé.
        // MCP: SELF (<5 lignes)
        // RAISON: même garde-fou d'emplacement distinct que les deux champs ci-dessus.
        'source_captured_at',
        'source_content_hash',
        // ACTION : récupération automatique Markdown + Publier-et-purger (design doc "Actus -
        // composition manuelle assistée" 2026-08-15, révision 2026-08-17) - même garde-fou
        // d'emplacement distinct que les trois champs ci-dessus. 'source_acquisition' (trace de
        // Modules\News\Services\SourceMarkdownFetcher) n'est écrite QUE par
        // NewsCompositionController::fetchSource(). 'published_at' n'est écrite QUE par
        // NewsCompositionController::publish() (le seul écrit possible de is_published dans ce
        // contrôleur, exception documentée sur cette méthode).
        // MCP: SELF (<5 lignes)
        // RAISON: même garde-fou d'emplacement distinct que les champs voisins.
        'source_acquisition',
        'published_at',
        // ACTION : bonification panel 2026-08-17 (soir), décision du propriétaire - les fiches
        // doivent CITER l'original et porter une photo créditée. 'primary_sources' (tableau
        // {label, url, note?}) N'EST PAS interne comme les champs voisins ci-dessus : il est
        // affiché tel quel en fin de fiche publique (show.blade.php, section « Sources »).
        // 'image_credit' est affiché sous l'image principale. Seul écrivain : NewsApplyCommand
        // (--payload), même porte bornée que les autres champs de composition.
        // MCP: SELF (<5 lignes)
        // RAISON: design doc, section "Bonification panel 2026-08-17 (soir)".
        'primary_sources',
        'image_credit',
        // ACTION : implémentation /actu2 - volet serveur (design doc "Actus - composition
        // manuelle assistée" 2026-08-15, section "Implémentation /actu2 - volet serveur
        // (2026-08-17)") - trois champs additifs. 'nature_original' est INTERNE (jamais affiché
        // tel quel sur la fiche publique - même garde-fou que internal_source_text). 'niveau_preuve'
        // et 'original_post' sont affichés publiquement (voir show.blade.php), exactement comme
        // primary_sources/image_credit ; 'niveau_preuve' est traduit en français courant, jamais
        // l'étiquette technique brute. Seul écrivain : NewsApplyCommand (--payload), même porte
        // bornée.
        // MCP: SELF (<5 lignes)
        // RAISON: design doc, section "Implémentation /actu2 - volet serveur (2026-08-17)".
        'nature_original',
        'niveau_preuve',
        'original_post',
        // ACTION : chantier AdSense « faible valeur » (2026-08-18) - retrait SEO-sûr et
        // RÉVERSIBLE d'une fiche (colonne ajoutée par la migration
        // 2026_08_18_150000_add_retired_at_to_news_articles). Seuls écrivains : retire()/
        // unretire() ci-dessous (jamais d'écriture directe ailleurs).
        // MCP: SELF (<5 lignes)
        // RAISON: design doc du chantier, section retrait 410.
        'retired_at',
        // ACTION : module « signature éditoriale » (signal humain E-E-A-T vérifiable, design doc
        // SPEC-SIGNAL-HUMAIN, club des sages 93/100, 2026-08-20) - 'reviewed_at'/'reviewed_by'
        // sont fillable pour l'écriture SERVEUR, mais CORRECTIF DU 2026-08-21 : le SEUL point
        // d'écriture est désormais markReviewedByHuman(), appelé par un geste humain de l'écran
        // d'administration (publication manuelle ou bouton « J'ai relu »). NewsApplyCommand ne
        // les écrit PLUS - la signature attestait sinon une relecture qui n'avait pas eu lieu.
        // Ces deux clés restent volontairement ABSENTES de ALLOWED_PAYLOAD_KEYS : l'agent ne
        // peut jamais fabriquer sa propre date de relecture.
        // MCP: SELF (<5 lignes)
        // RAISON: design doc SPEC-SIGNAL-HUMAIN, étape 1.
        'reviewed_at',
        'reviewed_by',
        // Module « vérification » (2026-08-21) : trois champs additifs, nullables, sans effet sur
        // une fiche ordinaire. Vocabulaire et rendu : voir FACT_CHECK_VERDICTS.
        'fact_check_verdict',
        'fact_check_claim',
        'fact_check_source',
        // Statut orthogonal « vérification non concluante » (2026-08-31) - voir
        // hasFactCheckInconclusive(), jamais un sixième verdict.
        'fact_check_inconclusive_at',
        // Traduction des titres précalculée (2026-08-24) - voir la migration
        // 2026_08_24_090000_add_title_fr_to_news_articles. Seul écrivain :
        // Modules\News\Console\TranslateTitlesCommand (`news:translate-titles`), jamais l'écran
        // de composition lui-même (voir NewsCompositionController::titresTraduits()).
        'title_fr',
        'title_fr_at',
    ];

    protected $casts = [
        'pub_date' => 'datetime',
        'is_published' => 'boolean',
        'structured_summary' => 'array',
        'relevance_score' => 'integer',
        'is_potential_duplicate_of' => 'integer',
        'dedup_score' => 'float',
        'linkedin_shared_at' => 'datetime',
        'facebook_shared_at' => 'datetime',
        'is_comparative_digest' => 'boolean',
        'editorial_proof_pairs' => 'array',
        'source_captured_at' => 'datetime',
        'source_acquisition' => 'array',
        'published_at' => 'datetime',
        'primary_sources' => 'array',
        'original_post' => 'array',
        'retired_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'title_fr_at' => 'datetime',
        'fact_check_inconclusive_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $article) {
            if (empty($article->slug)) {
                $article->slug = self::generateUniqueSlug($article->seo_title ?? $article->title ?? 'article');
            }
        });

        static::updated(function (self $article) {
            if ($article->wasChanged('is_published') && $article->is_published && $article->category_tag
                && class_exists(\Modules\Community\Events\ContentPublished::class)) {
                \Modules\Community\Events\ContentPublished::dispatch($article->category_tag, 'news', $article);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title) ?: 'article';
        $slug = $baseSlug;
        $counter = 2;

        while (self::where('slug', $slug)->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'news_source_id');
    }

    /**
     * Outils annuaire liés à cette actualité (curation manuelle ou auto).
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Directory\Models\Tool::class,
            'news_article_tool',
            'news_article_id',
            'tool_id'
        )->withPivot('source')->withTimestamps();
    }

    /**
     * Article(s) de blogue lié(s) à cette actualité (curation manuelle ou auto). Jumeau exact de
     * tools() ci-dessus (même forme de relation, même pivot à 3 colonnes) - seul le plafond de 1
     * diffère, et il n'est JAMAIS imposé ici (aucune contrainte SQL, même doctrine que le
     * plafond de 10 sur tools()) : il vit uniquement dans
     * Modules\News\Console\NewsApplyCommand::MAX_RELATED_ARTICLES.
     */
    public function blogArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Blog\Models\Article::class,
            'news_article_article',
            'news_article_id',
            'article_id'
        )->withPivot('source')->withTimestamps();
    }

    public function originalArticle(): BelongsTo
    {
        return $this->belongsTo(self::class, 'is_potential_duplicate_of');
    }

    public function duplicates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'is_potential_duplicate_of');
    }

    /**
     * ACTION : Actus 2.0 - fiche comparative de cet article MEMBRE, ou null. Nommée
     * explicitement plutôt que de réinterpréter silencieusement originalArticle() : un ancien
     * enregistrement DEDUP-SKIP hypothétique (jamais écrit en prod à ce jour) ne doit jamais
     * être confondu avec un vrai groupe Actus 2.0.
     * MCP: SELF (<5 lignes)
     * RAISON: garde de cohérence explicite demandée par le design doc section 4.3.
     */
    public function fusionDigest(): ?self
    {
        $original = $this->originalArticle;

        return ($original && $original->is_comparative_digest) ? $original : null;
    }

    /**
     * ACTION : Actus 2.0 - membres de CETTE fiche comparative (vide si $this n'en est pas une).
     * MCP: SELF (<5 lignes)
     * RAISON: même garde de cohérence que fusionDigest(), design doc section 4.3.
     */
    public function fusionMembers(): \Illuminate\Support\Collection
    {
        return $this->is_comparative_digest ? $this->duplicates : collect();
    }

    public function shortUrl(): ?BelongsTo
    {
        if (! class_exists(\Modules\ShortUrl\Models\ShortUrl::class)) {
            return null;
        }

        return $this->belongsTo(\Modules\ShortUrl\Models\ShortUrl::class, 'short_url_id');
    }

    public function getShortUrlString(): ?string
    {
        if (! $this->short_url_id || ! class_exists(\Modules\ShortUrl\Models\ShortUrl::class)) {
            return null;
        }

        $shortUrl = \Modules\ShortUrl\Models\ShortUrl::find($this->short_url_id);

        return $shortUrl?->getShortUrl();
    }

    /**
     * ACTION : RÈGLE UNIQUE « publier = purger » (design doc "Actus - composition manuelle
     * assistée" 2026-08-15, révision 2026-08-17, addendum "purge garantie sur tous les chemins
     * de publication") - bascule is_published, horodate published_at et purge
     * internal_source_text dans UN SEUL update(), quel que soit le chemin qui publie :
     * Modules\News\Http\Controllers\Admin\NewsCompositionController::publish() (bouton
     * Publier-et-purger, avec ses propres gardes de prérequis AVANT d'appeler cette méthode) ET
     * Modules\News\Http\Controllers\AdminNewsController::toggleArticle() (bascule rapide de
     * /admin/news/articles, sans garde de prérequis - ce n'est pas son rôle). Cette méthode ne
     * fait volontairement AUCUNE validation métier (seo_title/summary/preuve éditoriale) : ces
     * gardes sont spécifiques à l'écran de composition et seraient incohérentes appliquées à la
     * bascule rapide, qui publie des fiches n'ayant jamais transité par cet écran. Seule la
     * MÉCANIQUE d'écriture est partagée, jamais les gardes métier. Provenance
     * (source_captured_at/source_content_hash), source_acquisition et editorial_proof_pairs
     * survivent volontairement - même garde-fou que destroySourceText() (section 5.2).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: DRY explicite demandé par le propriétaire - une seule implémentation de la purge,
     * jamais un texte source original conservé au-delà de la publication, quel que soit le
     * chemin emprunté pour publier.
     */
    public function publishAndPurgeSource(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now('America/Toronto'),
            'internal_source_text' => null,
        ]);
    }

    /**
     * ACTION : point UNIQUE de la règle « prêt à publier » (design doc "Actus - composition
     * manuelle assistée" 2026-08-15, note datée 2026-08-17 "l'agent publie lui-même via
     * news:apply --publish") - extrait de NewsCompositionController::publish() (révision
     * 2026-08-17) pour être réutilisé TEL QUEL par ce même contrôleur (bouton manuel
     * Publier-et-purger) ET par NewsApplyCommand (--publish, porte bornée de l'agent Claude Code
     * CLI). DRY explicite exigé par le mandat : aucune divergence possible entre les deux
     * chemins de publication.
     *
     * Vérifie, dans l'ordre :
     * 1. les champs obligatoires (seo_title, summary, au moins une paire de preuve) - liste
     *    COMPLÈTE des manquants, jamais seulement le premier ;
     * 2. si tous présents, la revalidation à 100 % des paires de type "fact" contre le texte
     *    source COURANT (celui en base au moment de l'appel, pas celui du moment où le prompt a
     *    été généré) - la première paire invalide arrête la vérification.
     *
     * Ne lève jamais d'exception et n'écrit rien : chaque appelant traduit ce résultat dans son
     * propre format (JSON HTTP avec codes 422, sortie console avec code de sortie non nul). La
     * vérification "déjà publiée" (409 côté HTTP, refus explicite côté commande) reste
     * VOLONTAIREMENT hors de cette méthode - elle porte sur is_published, pas sur la
     * préparation du contenu, et chaque appelant l'exprime déjà avec son propre code/message.
     * MCP: SELF (<5 lignes utiles par branche)
     * RAISON: DRY explicite demandé par le propriétaire - une seule implémentation de "prêt à
     * publier" à travers le code, jamais deux gardes qui pourraient diverger avec le temps.
     *
     * ACTION : bonification panel 2026-08-17 (soir) - un 3e type de paire est désormais accepté,
     * « primary_fact » (fait confirmé à la SOURCE PRIMAIRE, distincte du texte source collé pour
     * l'agent). Son excerpt N'EST JAMAIS revalidé en sous-chaîne du texte source courant (contrai-
     * rement à « fact ») - c'est précisément sa raison d'être : citer l'original mot pour mot, pas
     * le texte secondaire éventuellement paraphrasé ou incomplet fourni à l'agent. La seule
     * exigence revalidée ici est la présence d'un 'source_url' non vide (déjà validé comme URL à
     * l'écriture, par NewsCompositionController::storeProofPair() et NewsApplyCommand).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc, section "Bonification panel 2026-08-17 (soir)" - 3e type de paire.
     *
     * @return array{ready: bool, missing: array<int, string>, invalid_pair: array{statement: string, excerpt: string, reason?: string}|null}
     */
    public function publishReadinessCheck(): array
    {
        $missing = [];
        if (blank($this->seo_title)) {
            $missing[] = 'seo_title';
        }
        if (blank($this->summary)) {
            $missing[] = 'summary';
        }
        $pairs = $this->editorial_proof_pairs ?? [];
        if ($pairs === []) {
            $missing[] = 'editorial_proof_pairs';
        }

        if ($missing !== []) {
            return ['ready' => false, 'missing' => $missing, 'invalid_pair' => null];
        }

        $sourceText = (string) $this->internal_source_text;
        foreach ($pairs as $pair) {
            $type = $pair['type'] ?? null;

            // ACTION: troisième appelant du même défaut, corrigé le 2026-08-29 par la MÊME garde
            // que les deux autres (news:apply #1984, puis storeProofPair()) plutôt qu'une
            // quatrième réécriture de la règle.
            // RAISON: destroySourceText() peut vider internal_source_text APRÈS l'ajout des paires.
            // containsExact() contre une chaîne vide ne réussit jamais, et cette vérification-ci
            // BLOQUE LA PUBLICATION : la fiche deviendrait impubliable pour des citations qu'on
            // ne peut plus contrôler, définitivement, puisque la source ne revient jamais.
            // MCP: SELF (1 ligne utile)
            if ($type === 'fact' && ! EditorialProofNormalizer::verifyFactPair($sourceText, (string) ($pair['excerpt'] ?? ''))['accepted']) {
                return [
                    'ready' => false,
                    'missing' => [],
                    'invalid_pair' => [
                        'statement' => (string) ($pair['statement'] ?? ''),
                        'excerpt' => (string) ($pair['excerpt'] ?? ''),
                        'reason' => 'fact_substring',
                    ],
                ];
            }

            if ($type === 'primary_fact' && blank($pair['source_url'] ?? null)) {
                return [
                    'ready' => false,
                    'missing' => [],
                    'invalid_pair' => [
                        'statement' => (string) ($pair['statement'] ?? ''),
                        'excerpt' => (string) ($pair['excerpt'] ?? ''),
                        'reason' => 'primary_fact_missing_source_url',
                    ],
                ];
            }
        }

        return ['ready' => true, 'missing' => [], 'invalid_pair' => null];
    }

    /**
     * ACTION : bonification panel 2026-08-17 (soir) - message d'erreur normalisé pour une paire
     * invalide retournée par publishReadinessCheck(), RÉUTILISÉ par les deux seuls appelants
     * (NewsCompositionController::publish() et NewsApplyCommand --publish) pour qu'un nouveau
     * motif de refus (ex. primary_fact sans source_url) ne puisse jamais diverger entre les deux
     * chemins de publication - même garde-fou DRY que les extractions précédentes de cette classe.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: DRY explicite, un seul endroit à corriger si un nouveau motif de refus apparaît.
     *
     * @param array{statement: string, excerpt: string, reason?: string} $pair
     */
    public static function publishInvalidPairMessage(array $pair): string
    {
        if (($pair['reason'] ?? null) === 'primary_fact_missing_source_url') {
            return 'La paire de preuve « '.($pair['statement'] ?? '').' » est déclarée « fait primaire » mais n\'a pas d\'URL de source primaire valide. Rien n\'a été publié, rien n\'a été purgé.';
        }

        return 'La paire de preuve « '.($pair['statement'] ?? '').' » n\'est plus une sous-chaîne exacte du texte source courant. Rien n\'a été publié, rien n\'a été purgé.';
    }

    /**
     * ACTION : addendum daté 2026-08-17 (fin de journée, découvert en production) - la fiche
     * publique (Modules\News\resources\views\public\show.blade.php, bloc `@if($ss) ...
     * @elseif($article->summary)`) affiche `structured_summary` (résumé MACHINE généré à la
     * collecte) EN PRIORITÉ sur `summary` : tant que `structured_summary` existe, le résumé
     * composé à la main via l'écran de composition restait invisible sur le site, même une fois
     * appliqué. La composition manuelle fait désormais AUTORITÉ sur le résumé publié - cette
     * méthode JOURNALISE l'ancienne valeur de `structured_summary` (canal 'composition',
     * récupérable au besoin, jamais perdu en silence) puis la retourne pour que l'appelant
     * l'inclue dans SON PROPRE update()/transaction ; cette méthode n'écrit rien elle-même, elle
     * ne fait QUE journaliser (aucun effet de bord en base sans l'update() de l'appelant).
     *
     * Réutilisée par DEUX endroits SEULEMENT (DRY explicite, ni plus ni moins) :
     * NewsApplyCommand (mode --payload, dès que la clé 'summary' ou 'composed_summary' est
     * appliquée - correctif 2026-08-28 : RESTREINT depuis « dès qu'un des trois champs de
     * contenu », condition trop large qui effaçait aussi le résumé riche de fiches touchées par
     * un payload partiel sans rapport avec leur résumé) et NewsCompositionController::publish()
     * (juste avant publishAndPurgeSource()).
     * VOLONTAIREMENT PAS dans publishAndPurgeSource() elle-même : cette méthode partagée sert
     * aussi AdminNewsController::toggleArticle() (bascule rapide de fiches jamais passées par
     * l'écran de composition) et `news:verify-source-purge`, où effacer structured_summary
     * serait une régression hors mandat. VOLONTAIREMENT PAS dans update() (édition manuelle de
     * l'écran de composition) : l'admin peut retoucher le texte sans forcer la bascule
     * d'affichage.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: correctif ciblé d'un défaut découvert en production, DRY entre les deux seuls
     * chemins concernés.
     */
    public function logStructuredSummaryOverride(): void
    {
        if ($this->structured_summary === null) {
            return;
        }

        Log::channel('composition')->info('structured_summary effacé au profit de la composition manuelle', [
            'article_id' => $this->id,
            'slug' => $this->slug,
            'structured_summary_avant' => $this->structured_summary,
        ]);
    }

    /**
     * ACTION : Richesse v1.188.0 - structure fixe composée (design doc "Actus - composition
     * manuelle assistée" 2026-08-15, section "Richesse v1.188.0") - vrai uniquement si
     * structured_summary porte le marqueur `composed: true` écrit par NewsApplyCommand
     * (--payload, clé composed_summary). Distingue À JAMAIS une fiche composée par l'agent
     * /actu2 d'une fiche à l'ancien résumé MACHINE (défunt depuis NEWS_MACHINE_SUMMARY_ENABLED
     * = false, mais des fiches historiques en portent encore). Point UNIQUE réutilisé par
     * Modules\News\resources\views\public\show.blade.php (ordre fixe des sections) ET
     * Modules\News\Http\Controllers\Admin\NewsCompositionController::publish() (garde-fou
     * empêchant le bouton manuel Publier-et-purger d'effacer un résumé composé - découvert en
     * implémentant ce mandat : l'effacement inconditionnel de structured_summary avant
     * publication, correct pour l'ancien résumé machine, aurait sinon détruit une composition
     * riche au moment même de la publier).
     * MCP: SELF (<5 lignes)
     * RAISON: DRY explicite - un seul point de vérité pour "cette fiche porte-t-elle un résumé
     * composé", jamais une divergence entre le rendu et la garde d'écriture.
     */
    public function hasComposedSummary(): bool
    {
        return is_array($this->structured_summary) && ($this->structured_summary['composed'] ?? false) === true;
    }

    /**
     * ACTION : provenance du texte source (design doc "Actus - composition manuelle assistée"
     * 2026-08-15, section 5.2) - extrait TEL QUEL de l'ancienne méthode privée
     * NewsCompositionController::applySourceProvenance() (implémentation /actu2, révision
     * 2026-08-17) pour être réutilisé SANS DUPLICATION par Modules\News\Console\
     * NewsSourceCommand (porte serveur du skill /actu2, `php artisan news:source`). Retourne les
     * champs à fusionner dans un update() UNIQUEMENT si le texte fourni change réellement
     * l'empreinte déjà en base (jamais sur un texte vide ou inchangé) - même règle que l'ancien
     * code du contrôleur, aucune divergence de comportement.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: DRY explicite exigé par le mandat /actu2 - une seule implémentation de la
     * provenance, partagée par les deux points d'écriture (écran de composition ET commande
     * news:source).
     *
     * @return array<string, mixed>
     */
    public function sourceProvenanceUpdates(string $sourceText): array
    {
        if (blank($sourceText)) {
            return [];
        }

        $hash = hash('sha256', $sourceText);
        if ($hash === $this->source_content_hash) {
            return [];
        }

        return [
            'source_content_hash' => $hash,
            'source_captured_at' => now('America/Toronto'),
        ];
    }

    /**
     * ACTION : commande `news:create-draft` + écran de composition « Créer une fiche depuis un
     * lien » (design doc "Actus - composition manuelle assistée" 2026-08-15, section
     * "Améliorations en attente", point 1) - le premier cycle /actu2 réel (fiche 33530) a prouvé
     * qu'aucune création manuelle de fiche n'existait : un post X ou une annonce hors collecte
     * RSS n'avait aucune fiche à composer. Point d'entrée UNIQUE (DRY strict) réutilisé TEL QUEL
     * par Modules\News\Console\NewsCreateDraftCommand ET
     * Modules\News\Http\Controllers\Admin\NewsCompositionController::createDraft() - une seule
     * implémentation, deux portes.
     *
     * Idempotente par URL : une fiche déjà créée à cette URL exacte (peu importe sa source) est
     * retournée TELLE QUELLE plutôt que dupliquée - un appel répété (rejeu du skill, double
     * clic) ne crée jamais deux fiches pour le même lien. La source "Soumission manuelle" (URL
     * factice 'manuel://soumission-directe', volontairement INACTIVE - jamais collectée par le
     * flux RSS) n'est créée qu'une seule fois, via firstOrCreate(). Le slug est dérivé
     * automatiquement du titre par le hook static::creating() déjà en place plus haut
     * (generateUniqueSlug(), suffixe unique si collision) - aucune duplication de cette règle.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: DRY explicite exigé par le mandat, une seule implémentation à travers le code.
     *
     * @return array{article: self, created: bool}
     */
    public static function createManualDraft(string $url, ?string $title = null): array
    {
        $url = trim($url);

        $existing = self::where('url', $url)->first();
        if ($existing) {
            return ['article' => $existing, 'created' => false];
        }

        $source = NewsSource::firstOrCreate(
            ['url' => 'manuel://soumission-directe'],
            ['name' => 'Soumission manuelle', 'language' => 'fr', 'active' => false]
        );

        $article = self::create([
            'news_source_id' => $source->id,
            'title' => filled($title) ? trim($title) : 'Fiche créée depuis un lien - à composer',
            'guid' => 'manuel-'.(string) Str::uuid(),
            'url' => $url,
            'description' => '',
            'pub_date' => now('America/Toronto'),
            'is_published' => false,
            'seo_status' => 'index',
        ]);

        return ['article' => $article, 'created' => true];
    }

    // 2026-05-05 #146 : scopePublished mutualise via HasPublishedState (DRY Core).

    /**
     * ACTION : chantier AdSense « faible valeur » (2026-08-18) - OVERRIDE de scopePublished()
     * (HasPublishedState, Modules\Core) : ce override, défini DANS NewsArticle, prime sur celui
     * du trait (PHP résout d'abord les méthodes de la classe elle-même). C'est le POINT DRY
     * UNIQUE qui exclut une fiche retirée (retired_at non nul) de TOUTE surface publique qui
     * appelle published() - liste /actualites, connexes (relatedFor/relatedByEntities), recherche
     * (Modules\Search\Services\SearchService::searchFront(), qui appelle ->published() dès que
     * scopePublished existe). Une fiche retirée continue de résoudre par son slug (route model
     * binding manuel de routes/web.php) : c'est volontaire, PublicNewsController::show() la
     * détecte et sert un 410 explicite plutôt qu'un 404 générique - retrait SEO « volontaire »
     * au sens de Google, jamais une simple disparition.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc du chantier - un seul endroit à corriger si la définition de "publiée"
     * doit évoluer, jamais une divergence entre les surfaces publiques qui l'appellent.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNull('retired_at');
    }

    /**
     * ACTION : vrai si cette fiche a été retirée (retired_at non nul) - chantier AdSense
     * « faible valeur » (2026-08-18). Consommé par PublicNewsController::show() pour servir un
     * 410 explicite avant tout autre traitement.
     * MCP: SELF (<5 lignes)
     * RAISON: design doc du chantier.
     */
    public function isRetired(): bool
    {
        return $this->retired_at !== null;
    }

    /**
     * ACTION : retire cette fiche (retired_at = maintenant) - idempotent, jamais d'écrasement
     * d'un retired_at déjà posé (préserve la date réelle du premier retrait). Chantier AdSense
     * « faible valeur » (2026-08-18). Seul appelant : Modules\News\Console\
     * RetireArticlesCommand (porte bornée, jamais d'appel direct ailleurs).
     * MCP: SELF (<5 lignes)
     * RAISON: réversibilité exigée par le design doc - jamais de suppression de données, seul
     * le statut de service change.
     */
    public function retire(): void
    {
        if ($this->retired_at === null) {
            $this->retired_at = now('America/Toronto');
            $this->save();
        }
    }

    /**
     * ACTION : restaure cette fiche (retired_at = null) - réversibilité du retrait, chantier
     * AdSense « faible valeur » (2026-08-18). Seul appelant : RetireArticlesCommand (--restore).
     * MCP: SELF (<5 lignes)
     * RAISON: garde-fou zéro-suppression - le retrait n'efface jamais rien, il se défait.
     */
    public function unretire(): void
    {
        $this->retired_at = null;
        $this->save();
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('pub_date', 'desc');
    }

    public function scopePotentialDuplicates(Builder $query): Builder
    {
        return $query->whereNotNull('is_potential_duplicate_of');
    }

    public static function searchableFields(): array
    {
        // ACTION : 'description' RETIRE de la recherche publique (2026-08-28).
        // MCP: SELF (<5 lignes)
        // RAISON: ce champ portait le TEXTE SOURCE integral, purge par le chantier « Actus -
        // zero copie » (2026-08-16). Les deux seuls points d'ecriture qui subsistent
        // (NewsArticle et RssFetcherService) y inscrivent volontairement une chaine VIDE, et
        // aucun chemin d'affichage ne le lit. Le laisser dans la recherche coutait un LIKE
        // inutile sur chaque requete, et surtout gardait ouvert un canal de DECOUVERTE : une
        // ligne ancienne ayant echappe a la purge aurait rendu son texte source trouvable par
        // recherche d'une phrase exacte, alors que ce texte ne doit plus exister chez nous.
        return ['title', 'seo_title', 'summary'];
    }

    public static function searchSectionKey(): string
    {
        return 'news';
    }

    public static function searchSectionLabel(): string
    {
        return __('Actualités');
    }

    public static function searchSectionIcon(): string
    {
        return '📰';
    }

    public static function searchPriority(): int
    {
        return 10;
    }

    public function searchableResultTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function searchableResultExcerpt(): string
    {
        return $this->displayExcerpt(200);
    }

    public function searchableResultUrl(): string
    {
        return route('news.show', $this->slug);
    }

    /**
     * 2026-07-04 : aplatit le résumé structuré IA (hook + points clés + pourquoi important) en
     * texte brut. Centralise cette concaténation pour éviter la duplication entre le calcul du
     * temps de lecture (article-card.blade.php, show.blade.php) et la détection automatique
     * d'outils (NewsToolSyncAction::suggest()) - cette dernière ignorait jusqu'ici entièrement
     * structured_summary, qui porte pourtant le gros du contenu réel de l'actualité (description
     * et summary sont souvent vides quand structured_summary est renseigné, cf. show.blade.php
     * ligne 333 : le résumé brut n'est affiché QUE en absence de résumé structuré).
     */
    public function flattenStructuredSummary(): string
    {
        $ss = is_array($this->structured_summary) ? $this->structured_summary : null;
        if (! $ss) {
            return '';
        }

        // ACTION : intégration « Outils liés » (2026-08-17 soir) - l'aplatissement couvre AUSSI
        // les clés du résumé COMPOSÉ v1.188.0 (chiffre-clé, citation, angle QC, action concrète,
        // repères datés), toutes affichées publiquement : sans elles, l'auto-détection d'outils,
        // le temps de lecture et le wordCount JSON-LD ignoraient une partie du corps réel.
        // MCP: multi-ai-mcp→qwen3-max (validé par le superviseur)
        // RAISON: demande fondateur 2026-08-17 - « actu2 doit aussi bien intégrer Outils liés ».
        $parts = [];

        foreach (['hook', 'why_important', 'key_number', 'angle_qc_ca', 'action_concrete'] as $key) {
            if (is_string($ss[$key] ?? null) && trim($ss[$key]) !== '') {
                $parts[] = trim($ss[$key]);
            }
        }

        foreach (is_array($ss['key_points'] ?? null) ? $ss['key_points'] : [] as $point) {
            if (is_string($point) && trim($point) !== '') {
                $parts[] = trim($point);
            }
        }

        if (is_string($ss['quote']['text'] ?? null) && trim($ss['quote']['text']) !== '') {
            $parts[] = trim($ss['quote']['text']);
        }

        foreach (is_array($ss['reperes_dates'] ?? null) ? $ss['reperes_dates'] : [] as $repere) {
            if (is_array($repere) && is_string($repere['texte'] ?? null) && trim($repere['texte']) !== '') {
                $parts[] = trim($repere['texte']);
            }
        }

        return trim(implode(' ', $parts));
    }

    /**
     * ACTION : rendu textuel INTÉGRAL du résumé structuré IA, unique source de vérité pour le
     * corps réellement affiché d'une fiche - utilisé par le JSON-LD (articleBody/wordCount, cf.
     * JsonLdService::newsArticle()), le calcul du temps de lecture (show.blade.php,
     * article-card.blade.php) et le garde-fou anti-corps-vide hasExploitableSummary() ci-dessous.
     * Priorité : résumé structuré aplati (flattenStructuredSummary()), sinon le résumé court.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: design doc "Actus - zéro copie du texte source" (2026-08-13) section 4.3/4.4 - le
     * texte source (description) n'entre plus JAMAIS dans ce calcul, même en repli.
     */
    public function structuredBodyText(): string
    {
        $flattened = trim($this->flattenStructuredSummary());
        if ($flattened !== '') {
            return $flattened;
        }

        return trim((string) ($this->summary ?? ''));
    }

    /**
     * ACTION : garde-fou permanent (design doc section 4.4) - vrai uniquement si cette fiche a
     * un contenu réellement exploitable à afficher publiquement. Consommé par
     * PublicNewsController::show() pour refuser de servir une fiche au corps vide.
     * MCP: SELF (<5 lignes)
     * RAISON: une fiche sans résumé exploitable ne doit jamais être servie avec un corps vide,
     * quelle qu'en soit la cause (échec IA jamais rattrapé, cascade épuisée avant régénération).
     */
    public function hasExploitableSummary(): bool
    {
        return $this->structuredBodyText() !== '';
    }

    /**
     * ACTION : extrait court réutilisable - SOURCE UNIQUE de la cascade « résumé court, sinon
     * accroche du résumé structuré, sinon repli configuré » demandée à six endroits distincts
     * (design doc section 4.5) : meta description et résumé pour agents (show.blade.php),
     * accroche de carte (article-card.blade.php), extrait de recherche
     * (searchableResultExcerpt() ci-dessus), bloc Journal (JournalBlockService) et partage
     * admin (adminShareContents() ci-dessous). Ne JAMAIS recopier cette logique ailleurs.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: DRY explicite - un seul endroit à corriger si la cascade doit changer.
     */
    public function displayExcerpt(int $maxLength = 200): string
    {
        $summary = trim((string) ($this->summary ?? ''));
        if ($summary !== '') {
            return safe_excerpt($summary, $maxLength);
        }

        $flattened = trim($this->flattenStructuredSummary());
        if ($flattened !== '') {
            return safe_excerpt($flattened, $maxLength);
        }

        return $this->fallbackExcerpt();
    }

    /**
     * ACTION : vrai si l'image de la fiche a été posée par CURATION (porte news:apply --image,
     * toujours accompagnée d'un crédit) - le pipeline machine ne doit JAMAIS la régénérer.
     * MCP: SELF (<5 lignes)
     * RAISON: incident 2026-08-18 - news:reprocess a écrasé la photo générée de la fiche 33558
     *         par la vignette de marque, 20 minutes après sa publication.
     */
    public function hasCuratedImage(): bool
    {
        return filled($this->image_credit);
    }

    /**
     * ACTION : URL d'image AVEC cache-bust `?v={updated_at}` - source UNIQUE (DRY) à rappeler
     * dans TOUTE vue qui rend l'image d'une fiche (héros, cartes, widgets d'accueil). Sans ce
     * suffixe, remplacer une image (même chemin) laissait les navigateurs/CDN servir l'ancienne
     * pendant un an (max-age immuable) - incident 2026-08-18 : la photo restaurée de la fiche
     * 33558 restait la vieille vignette dans le widget « Dernières actualités » de l'accueil,
     * seul endroit qui rendait image_url sans version. Retourne null si aucune image ; laisse
     * une URL http externe intacte (jamais de version ajoutée à un domaine tiers).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: demande fondateur 2026-08-18 - « problème d'images encore » ; élimine la
     *         duplication du cache-bust recopié à la main dans article-card et le héros.
     */
    public function versionedImageUrl(): ?string
    {
        if (blank($this->image_url)) {
            return null;
        }
        if (str_contains($this->image_url, 'http')) {
            return $this->image_url;
        }

        return $this->image_url.'?v='.($this->updated_at?->timestamp ?? time());
    }

    /**
     * ACTION : entités nommées de la fiche (index des connexes par entités partagées).
     * MCP: hermes→deepseek-v4-flash (validé par le superviseur)
     * RAISON: arbitrage panel 2026-08-17 - curation par la porte bornée (clé entities).
     */
    public function entities(): HasMany
    {
        return $this->hasMany(NewsArticleEntity::class);
    }

    /**
     * ACTION : REMPLACE les entités de la fiche par la liste fournie (normalisation slug,
     * dédoublonnage) - la curation du cycle /actu2 est la source de vérité.
     * MCP: hermes→deepseek-v4-flash (validé par le superviseur)
     */
    public function syncEntities(array $labels): void
    {
        $normalized = [];

        foreach ($labels as $label) {
            $trimmed = trim((string) $label);
            $slug = Str::slug($trimmed);

            if ($trimmed === '' || $slug === '') {
                continue;
            }

            $normalized[$slug] = [
                'entity_slug' => $slug,
                'entity_label' => $trimmed,
            ];
        }

        if ($normalized === []) {
            return;
        }

        DB::transaction(function () use ($normalized): void {
            $this->entities()->delete();
            $this->entities()->createMany(array_values($normalized));
        });
    }

    /**
     * ACTION : connexes par ENTITÉS partagées - fiches publiées classées par nombre d'entités
     * communes puis fraîcheur. Sous-requête agrégée joinSub (jamais de groupBy sur
     * news_articles.*, incompatible ONLY_FULL_GROUP_BY).
     * MCP: hermes→deepseek-v4-flash (2e passe, squelette imposé par le superviseur)
     * RAISON: arbitrage panel 2026-08-17 (idée neuve claude.ai retenue).
     */
    public function relatedByEntities(int $limit = 3): EloquentCollection
    {
        $slugs = $this->entities()->pluck('entity_slug');

        if ($slugs->isEmpty()) {
            return new EloquentCollection();
        }

        $counts = NewsArticleEntity::query()
            ->select('news_article_id', DB::raw('COUNT(*) as shared_entities'))
            ->whereIn('entity_slug', $slugs)
            ->where('news_article_id', '!=', $this->id)
            ->groupBy('news_article_id');

        return static::published()
            ->joinSub($counts, 'se', function ($join) {
                $join->on('news_articles.id', '=', 'se.news_article_id');
            })
            ->select('news_articles.*', 'se.shared_entities')
            ->orderByDesc('se.shared_entities')
            ->orderByDesc('news_articles.pub_date')
            ->limit($limit)
            ->with('source')
            ->get();
    }

    /**
     * ACTION : point d'entrée UNIQUE des articles connexes (DRY) - entités partagées d'abord,
     * repli sur la catégorie pour compléter jusqu'à la limite.
     * MCP: hermes→deepseek-v4-flash (validé par le superviseur)
     */
    public static function relatedFor(self $article, int $limit = 3): EloquentCollection
    {
        $related = $article->relatedByEntities($limit);

        if ($related->count() >= $limit) {
            return $related;
        }

        $excluded = array_merge($related->pluck('id')->all(), [$article->id]);

        $complement = static::published()
            ->where('category_tag', $article->category_tag)
            ->whereNotIn('id', $excluded)
            ->orderByDesc('pub_date')
            ->limit($limit - $related->count())
            ->with('source')
            ->get();

        return new EloquentCollection($related->concat($complement)->all());
    }

    /**
     * ACTION : vrai si la fiche provient de la source technique « Soumission manuelle »
     * (createManualDraft(), URL factice 'manuel://soumission-directe').
     * MCP: multi-ai-mcp→qwen3-max (validé + corrigé par le superviseur)
     * RAISON: demande fondateur 2026-08-17 - ce libellé technique ne doit jamais paraître
     *         publiquement ; les vues affichent la provenance réelle de l'original à la place.
     */
    public function isManualSubmission(): bool
    {
        return $this->source?->url === 'manuel://soumission-directe';
    }

    /**
     * ACTION : nom de source affiché publiquement (pastilles, meta, cartes). Fiche RSS :
     * comportement inchangé (nom du média). Fiche manuelle : hôte de la première source
     * primaire (d'où vient l'ORIGINAL), sinon « X (@handle) » du post, sinon « Source directe ».
     * MCP: multi-ai-mcp→qwen3-max (validé + corrigé par le superviseur : preg_replace au lieu
     *      du piège ltrim($host, 'www.') qui rognerait « web.dev » en « eb.dev »)
     * RAISON: demande fondateur 2026-08-17 - « ne pas écrire Soumission manuelle mais plutôt
     *         d'où vient l'original ».
     */
    public function displaySourceName(): string
    {
        if (! $this->isManualSubmission()) {
            return $this->source->name ?? __('Source');
        }

        $sources = is_array($this->primary_sources) ? $this->primary_sources : [];
        $url = $sources[0]['url'] ?? null;
        if (is_string($url) && $url !== '') {
            $host = parse_url($url, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return (string) preg_replace('/^www\./', '', $host);
            }
        }

        $post = is_array($this->original_post) ? $this->original_post : [];
        if (filled($post['handle'] ?? null)) {
            return 'X ('.$post['handle'].')';
        }

        return __('Source directe');
    }

    /**
     * ACTION : nom du RELAIS (« relayé par », « Relais média »). Fiche RSS : le média,
     * inchangé. Fiche manuelle : « X (@handle) » si l'entrée est un post, sinon null - la vue
     * masque alors la mention de relais, jamais le libellé technique.
     * MCP: multi-ai-mcp→qwen3-max (validé par le superviseur)
     * RAISON: même demande fondateur 2026-08-17 (journal, entrées 113 et 115).
     */
    public function displayRelayName(): ?string
    {
        if (! $this->isManualSubmission()) {
            return $this->source?->name;
        }

        $post = is_array($this->original_post) ? $this->original_post : [];
        if (filled($post['handle'] ?? null)) {
            return 'X ('.$post['handle'].')';
        }

        return null;
    }

    /**
     * ACTION : module « signature éditoriale » (design doc SPEC-SIGNAL-HUMAIN, 2026-08-20) - vrai
     * si la fiche a reçu une relecture éditoriale RÉELLE et datée (jamais dérivée). Seul écrivain
     * de 'reviewed_at' : Modules\News\Console\NewsApplyCommand (porte bornée), jamais une saisie
     * libre. Gate unique du composant x-news::editorial-signature et du reviewedBy JSON-LD.
     * MCP: SELF (<5 lignes)
     * RAISON: design doc, section « Décision de conception » - une date de relecture sans vraie
     * relecture est trompeuse et se retourne contre le site.
     */
    public function hasEditorialReview(): bool
    {
        return ! is_null($this->reviewed_at);
    }

    /**
     * Ticket #1915 (2026-08-30) - SOURCE UNIQUE du vocabulaire de 'nature_original'. Seul
     * écrivain : Modules\News\Console\NewsApplyCommand (--payload), qui DÉRIVE sa liste blanche
     * d'ici plutôt que de la dupliquer (un doublon avait déjà produit le défaut que ce ticket
     * corrige : une liste blanche affirmée par un skill mais absente du code réel). Champ INTERNE
     * - jamais affiché tel quel sur la fiche publique, même garde-fou que internal_source_text ;
     * 'label' sert l'admin et le débogage, pas une vue publique.
     *
     * Mesure du 2026-08-30 (ticket #1915) : sur les 213 fiches publiées passées par /actu2
     * (niveau_preuve non nul), 129 (60 %) avaient 'nature_original' vide faute de valeur
     * applicable - l'énumération fermée d'origine (4 valeurs, 2026-08-17) ne couvrait pas ce que
     * le site publie réellement. Trois valeurs ajoutées, chacune tirée d'un cas RÉEL observé en
     * production, jamais anticipée :
     *
     * - 'contenu_educatif' : l'original est un cours, une conférence ou un atelier pédagogique
     *   enregistré (ex. fiches 33558 et 36089 - conférences de Stanford et de Geoffrey Hinton -
     *   laissées vides ; fiches 35314 et 35315 - ateliers Anthropic, cours Stanford CS336 -
     *   forcées à tort en 'message_personnel').
     * - 'projet_communautaire' : l'original est un projet logiciel ou un dépôt de code à but non
     *   commercial (communautaire, open source), documentation du dépôt comprise (ex. fiche 39524
     *   FreeCORE - laissée vide plutôt que forcée en 'annonce_commerciale', un projet explicitement
     *   non commercial ; fiche 39528 « Claude Code gratuit » - forcée à tort en
     *   'message_personnel', une approximation relevée par l'agent lui-même).
     * - 'entrevue_publiee' : l'original est une entrevue publiée par un média ou un blogue,
     *   distincte d'un message spontané (ex. fiche 37573 - entrevue de Sam Altman - laissée vide ;
     *   fiche 35337 - entrevue de Boris Cherny chez Y Combinator - forcée à tort en
     *   'annonce_commerciale').
     */
    public const NATURE_ORIGINAL_VALUES = [
        'annonce_commerciale' => 'Annonce commerciale',
        'etude_evaluee' => 'Étude évaluée',
        'preimpression' => 'Préimpression',
        'message_personnel' => 'Message personnel',
        'contenu_educatif' => 'Contenu éducatif',
        'projet_communautaire' => 'Projet communautaire',
        'entrevue_publiee' => 'Entrevue publiée',
    ];

    /**
     * Libellé français de 'nature_original', ou null si absente/inconnue - une valeur retirée du
     * vocabulaire après coup se comporte comme une absence, même garde-fou que factCheckVerdict().
     */
    public function natureOriginalLabel(): ?string
    {
        return self::NATURE_ORIGINAL_VALUES[$this->nature_original] ?? null;
    }

    /**
     * Module « vérification » (2026-08-21) - SOURCE UNIQUE du vocabulaire des verdicts.
     *
     * Tout ce qui touche à une vérification en découle et n'est JAMAIS réécrit ailleurs : le
     * libellé affiché sur le badge, la teinte du badge, la phrase explicative, et la note portée
     * par le balisage Schema.org ClaimReview. Ajouter un verdict, c'est ajouter une entrée ici,
     * et rien d'autre.
     *
     * Le choix des étiquettes suit deux contraintes. D'abord la justesse : les cas rencontrés ne
     * sont presque jamais des « fausses nouvelles » au sens strict, mais des citations mal
     * attribuées, des propos recomposés ou des œuvres de fiction sorties de leur contexte - le
     * mot juste vaut mieux que le mot fort. Ensuite la lisibilité : cinq étiquettes, pas douze,
     * pour qu'un lecteur pressé comprenne du premier coup d'oeil.
     *
     * 'contenu_synthetique' a été ajouté le 2026-08-21 après une passe adversariale qui a nommé
     * l'angle mort : sur un site consacré à l'IA, le cas le plus probable n'est pas la citation
     * mal recopiée, c'est l'image ou la vidéo fabriquée par un générateur puis présentée comme
     * un document authentique. Le ranger sous « présentation trompeuse » aurait tout dit sauf
     * l'essentiel.
     *
     * 'rating' : note ClaimReview sur 5 (1 = entièrement faux, 5 = entièrement exact), attendue
     * par Google en complément de l'étiquette textuelle, qui reste la valeur affichée.
     *
     * 'contexte_manquant' : poser ce verdict exige de trancher, au cas par cas, si l'élément
     * manquant est grave (il fait dire à l'affirmation quelque chose de faux) ou accessoire
     * (compression éditoriale normale). Ce jugement suit un test de discrimination à question
     * unique - jamais réécrit ici - documenté dans `docs/specs/
     * 2026-08-31-test-discrimination-contexte-manquant.md` et rappelé par le skill `/actu2`.
     *
     * 'attribution_erronee' : élargi le 2026-08-31 pour couvrir aussi les DONNÉES (un chiffre
     * vrai attribué au mauvais document), pas seulement les propos d'une personne - cas
     * déclencheur : un chiffre exact, mais rattaché à une source qui ne l'a pas produit.
     */
    public const FACT_CHECK_VERDICTS = [
        'contenu_synthetique' => [
            'label' => 'Contenu généré par une IA',
            'tone' => 'danger',
            'summary' => "Le document présenté comme authentique a été fabriqué par un outil de génération.",
            'rating' => 1,
        ],
        'citation_inexacte' => [
            'label' => 'Citation inexacte',
            'tone' => 'danger',
            'summary' => "La citation attribuée ne se trouve pas dans la source à laquelle on la rattache.",
            'rating' => 1,
        ],
        'attribution_erronee' => [
            'label' => 'Attribution erronée',
            'tone' => 'danger',
            'summary' => "Les propos ou les données existent, mais pas sous cette forme, pas de cette personne, pas de ce document, ou pas à cette occasion.",
            'rating' => 2,
        ],
        'presentation_trompeuse' => [
            'label' => 'Présentation trompeuse',
            'tone' => 'warning',
            'summary' => "Le contenu est authentique, mais présenté d'une manière qui lui fait dire autre chose.",
            'rating' => 2,
        ],
        'contexte_manquant' => [
            'label' => 'Contexte manquant',
            'tone' => 'warning',
            'summary' => "L'information est exacte, mais il lui manque un élément sans lequel on la comprend mal.",
            'rating' => 3,
        ],
    ];

    /**
     * Le module de vérification est-il actif sur cette fiche ? Un verdict inconnu (étiquette
     * retirée du vocabulaire après coup) se comporte comme une absence : rien ne s'affiche,
     * plutôt qu'un badge vide ou une erreur.
     */
    public function hasFactCheck(): bool
    {
        return $this->fact_check_verdict !== null
            && array_key_exists($this->fact_check_verdict, self::FACT_CHECK_VERDICTS);
    }

    /**
     * Statut orthogonal « vérification non concluante » (tranché le 2026-08-27, voir docs/specs/
     * 2026-08-27-exposition-verifications-panel.md) : la fiche a CHERCHÉ à vérifier une
     * affirmation sans pouvoir trancher vers un des cinq verdicts. Un verdict réel prime toujours
     * sur ce statut - hasFactCheck() est vérifié en premier, jamais les deux affichés ensemble.
     */
    public function hasFactCheckInconclusive(): bool
    {
        return ! $this->hasFactCheck() && $this->fact_check_inconclusive_at !== null;
    }

    /**
     * Définition complète du verdict de cette fiche (libellé, teinte, phrase, note), ou null.
     * Point d'accès unique : ni la vue, ni le service JSON-LD ne lisent la constante directement.
     *
     * @return array{label:string, tone:string, summary:string, rating:int}|null
     */
    public function factCheckVerdict(): ?array
    {
        return $this->hasFactCheck() ? self::FACT_CHECK_VERDICTS[$this->fact_check_verdict] : null;
    }

    /**
     * Fiches portant une vérification, les plus récentes d'abord - réutilisée par la page
     * publique d'index des vérifications ET par tout comptage (DRY, une seule définition de
     * « ce qu'est une vérification » côté requête).
     */
    public function scopeFactChecked($query)
    {
        return $query->whereNotNull('fact_check_verdict');
    }

    /**
     * ACTION : pose la signature éditoriale - SEUL point d'écriture de reviewed_at (DRY strict).
     *
     * 2026-08-21, correctif de fond. Jusqu'ici, `news:apply --payload` posait reviewed_at
     * AUTOMATIQUEMENT dès qu'un contenu composé arrivait avec ses preuves. La fiche affichait donc
     * « Vérifié par la rédaction le [date] » sans qu'aucun être humain ne l'ait lue, alors que la
     * page publique /methodologie promet l'inverse mot pour mot : « relue par la rédaction [...]
     * jamais une date fabriquée ou dérivée automatiquement ». 110 fiches publiées portaient cette
     * signature non méritée.
     *
     * Désormais, cette méthode est le SEUL endroit qui écrit reviewed_at, et elle n'est appelée
     * que depuis un GESTE HUMAIN de l'écran d'administration (publication manuelle, ou bouton
     * « J'ai relu »). La porte de l'agent ne la touche jamais : une fiche composée et publiée par
     * l'agent reste sans signature tant qu'un humain ne l'a pas relue. C'est ce qui rend la
     * mention vraie, donc utile.
     *
     * saveQuietly : la signature ne doit pas déclencher les observateurs de contenu ni bousculer
     * updated_at, qui sert de jeton de fraîcheur à la porte d'écriture (leçon projet 2026-08-20).
     */
    public function markReviewedByHuman(?string $reviewerLabel = null): void
    {
        $this->reviewed_at = now('America/Toronto');

        if ($reviewerLabel !== null && trim($reviewerLabel) !== '') {
            $this->reviewed_by = trim($reviewerLabel);
        }

        $this->saveQuietly();
    }

    /**
     * ACTION : module « signature éditoriale » (même design doc) - libellé de qui a relu.
     * 'reviewed_by' vide (cas normal, la porte ne pose que 'reviewed_at') retombe sur le libellé
     * applicatif de la rédaction - jamais un nom de journaliste tiers, jamais l'auteur de la
     * source externe (retiré volontairement de la byline, arbitrage du panel éditorial
     * 2026-08-17). Chaîne traduisible, cohérente avec le reste des mentions « laveille.ai » déjà
     * écrites en toutes lettres ailleurs sur le site public (ex. public/gone.blade.php).
     * MCP: SELF (<5 lignes)
     * RAISON: design doc, section 3 - « la signature affiche "la rédaction de laveille.ai" ».
     */
    public function reviewerLabel(): string
    {
        return $this->reviewed_by ?: __('la rédaction de laveille.ai');
    }

    /**
     * ACTION : dernier repli de la cascade displayExcerpt() - jamais une chaîne vide. Gabarit et
     * mention générique en configuration (news.display_fallback), jamais en dur.
     * MCP: SELF (<5 lignes)
     * RAISON: design doc section 4.5 - « catégorie plus date » quand disponible, sinon une
     * mention non vide.
     */
    private function fallbackExcerpt(): string
    {
        if ($this->category_tag) {
            $template = (string) config('news.display_fallback.with_category', ':category - :date');

            return str_replace(
                [':category', ':date'],
                [(string) $this->category_tag, $this->pub_date ? format_date($this->pub_date) : ''],
                $template
            );
        }

        return (string) config('news.display_fallback.generic', 'Actualité en cours de traitement.');
    }

    /**
     * Textes de partage PUBLICS (barre de partage flottante, visiteurs) - délègue à
     * Modules\Core\Concerns\HasAdminShareContents::publicShareTexts() (aliasé sharedPublicShareTexts
     * plus haut, seul moyen d'éviter la collision de nom avec cette méthode SANS argument).
     * Même matière que adminShareContents() ci-dessous (title/hook/why_important/key_number/
     * action_concrete/category_tag) - DRY strict, aucune nouvelle lecture de structured_summary.
     * Consommée par Modules/News/resources/views/public/show.blade.php.
     */
    public function publicShareTexts(): array
    {
        $structured = is_array($this->structured_summary) ? $this->structured_summary : null;

        return $this->sharedPublicShareTexts([
            'title' => $this->seo_title ?: $this->title,
            'hook' => data_get($structured, 'hook'),
            'why_important' => data_get($structured, 'why_important'),
            'key_number' => data_get($structured, 'key_number'),
            'action_concrete' => data_get($structured, 'action_concrete'),
            'hashtag_categorie' => $this->category_tag,
        ], request()->url());
    }

    /**
     * Contenus de partage admin (superadmin) : résumé Gemini Notebook, prompt Gemini Notebook, post réseaux sociaux.
     * Retourne un tableau d'items [label, icon, text] pour le composant <x-core::admin-copy-menu>.
     * Logique générée Hermes, hashtag affiné (préserve la casse des acronymes).
     */
    public function adminShareContents(): array
    {
        $title = $this->seo_title ?: $this->title;
        $structured = is_array($this->structured_summary) ? $this->structured_summary : null;

        // 1. Résumé complet pour Gemini Notebook (titres de section, sans liens).
        if ($structured) {
            $resume = "# {$title}\n\n";
            if ($hook = data_get($structured, 'hook')) {
                $resume .= "## Accroche\n{$hook}\n\n";
            }
            $keyPoints = data_get($structured, 'key_points');
            if (is_array($keyPoints) && $keyPoints !== []) {
                $resume .= "## Points clés\n- " . implode("\n- ", $keyPoints) . "\n\n";
            }
            if ($why = data_get($structured, 'why_important')) {
                $resume .= "## Pourquoi c'est important\n{$why}\n\n";
            }
            if ($tldr = data_get($structured, 'tldr')) {
                $resume .= "## En bref\n{$tldr}\n\n";
            }
            // ACTION : Richesse v1.188.0 - quote est désormais soit une chaîne (ancien résumé
            // machine), soit un objet {text, author} (résumé composé, design doc section
            // "Richesse v1.188.0") - garde-fou évitant une conversion tableau-vers-chaîne.
            // MCP: SELF (<5 lignes)
            // RAISON: robustesse zéro-casse découverte en implémentant ce mandat.
            $quote = data_get($structured, 'quote');
            $quoteText = is_array($quote) ? (string) ($quote['text'] ?? '') : (string) ($quote ?? '');
            if ($quoteText !== '') {
                $resume .= "## Citation\n« {$quoteText} »\n\n";
            }
            if ($faqQ = data_get($structured, 'faq_question')) {
                $resume .= "## Question\n{$faqQ}\n\n" . data_get($structured, 'faq_answer', '') . "\n\n";
            }
        } else {
            // ACTION : plus de résumé structuré à plat texter - repli sur le résumé court
            // uniquement (design doc "Actus - zéro copie du texte source", 2026-08-13, section
            // 4.1) : $this->description ne véhicule plus jamais le texte source.
            // MCP: SELF (<5 lignes)
            $resume = "# {$title}\n\n" . strip_tags((string) $this->summary);
        }
        $resume = $this->stripLinks($resume);

        // 2. Prompt Gemini Notebook (via trait HasAdminShareContents).
        $prompt = $this->infographiePrompt('https://laveille.ai/actualites', 'Vulgarise les points clés de tous les documents dans une infographie engageante. Public : étudiants sans connaissances préalables.');
        $slides = $this->slidesPrompt('https://laveille.ai/actualites', 'Objectif : décrypter cette actualité et ce qu\'elle change concrètement. Public : étudiants, sans connaissances préalables.');

        // 3. Post réseaux sociaux natif (via trait HasAdminShareContents).
        // Post réseaux sociaux « 2026 » : hook structuré + En clair (tldr/résumé) + 👉 (point clé) + CTA (sans lien ni promo).
        // 2026-08-21 : on ne PRÉ-tronque plus ici (smartTrim coupait au milieu d'une phrase et
        // ajoutait « … », d'où des posts sociaux illisibles). buildLinkedInPost/buildFacebookPost
        // découpent désormais eux-mêmes à des frontières de phrase réelles.
        $hook = trim((string) (data_get($structured, 'hook') ?: $this->title));
        $plainDef = trim($this->stripLinks((string) (data_get($structured, 'tldr') ?: $this->summary)));
        // 👉 : un point clé DISTINCT du résumé (évite la redondance hook/En clair/👉).
        $interest = '';
        $candidates = [];
        if (is_array($structured)) {
            $keyPoints = data_get($structured, 'key_points');
            if (is_array($keyPoints)) {
                foreach ($keyPoints as $point) {
                    $candidates[] = (string) $point;
                }
            }
            // ACTION : Richesse v1.188.0 - même garde-fou que plus haut (quote peut être un
            // objet {text, author} pour un résumé composé).
            // MCP: SELF (<5 lignes)
            $candidates[] = $quoteText ?? '';
            $candidates[] = (string) data_get($structured, 'why_important');
        }
        foreach ($candidates as $cand) {
            $c = trim($this->stripLinks((string) $cand));
            if ($c === '' || $this->textsAreSimilar($plainDef, $c)) {
                continue;
            }
            $interest = $c; // découpe par phrase complète déléguée à buildLinkedInPost (2026-08-21)
            break;
        }
        $tags = ['#IA'];
        if ($this->category_tag) { $tags[] = '#' . $this->normalizeShareHashtag($this->category_tag); }
        $tags[] = '#Québec';
        $tags[] = '#VeilleIA';
        $linkedin = $this->buildLinkedInPost($hook, $plainDef, $interest, "Toi, t'en penses quoi ? 👇", $tags);
        $facebook = $this->buildFacebookPost($hook, $plainDef, $interest, "Toi, t'en penses quoi ? 👇", $tags);

        return [
            ['label' => 'Résumé (Gemini Notebook)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'Gemini Notebook Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'Gemini Notebook Diapositives', 'icon' => '🖼️', 'text' => $slides],
            [
                'label' => 'Post LinkedIn', 'icon' => '💼', 'text' => $linkedin,
                'track_url' => route('admin.news.articles.mark-shared', ['article' => $this, 'platform' => 'linkedin']),
            ],
            [
                'label' => 'Post Facebook', 'icon' => '📘', 'text' => $facebook,
                'track_url' => route('admin.news.articles.mark-shared', ['article' => $this, 'platform' => 'facebook']),
            ],
        ];
    }

}
