<?php

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Modules\Community\Traits\HasComments;
use Modules\Community\Traits\HasReports;
use Modules\Core\Concerns\HasAdminShareContents;
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\HasPublishedState;
use Modules\Core\Traits\LogsActivityStandard;
use Modules\Voting\Traits\HasCommunityVotes;

class NewsArticle extends Model implements Searchable
{
    use HasAdminShareContents;
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
    protected array $activitylogFields = ['title', 'seo_title', 'summary', 'is_published', 'relevance_score'];
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

    // 2026-05-05 #146 : scopePublished mutualise via HasPublishedState (DRY Core).

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
        return ['title', 'seo_title', 'summary', 'description'];
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

        $keyPoints = is_array($ss['key_points'] ?? null) ? $ss['key_points'] : [];

        return trim(
            ($ss['hook'] ?? '').' '.implode(' ', $keyPoints).' '.($ss['why_important'] ?? '')
        );
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
     * Contenus de partage admin (superadmin) : résumé NotebookLM, prompt NotebookLM, post réseaux sociaux.
     * Retourne un tableau d'items [label, icon, text] pour le composant <x-core::admin-copy-menu>.
     * Logique générée Hermes, hashtag affiné (préserve la casse des acronymes).
     */
    public function adminShareContents(): array
    {
        $title = $this->seo_title ?: $this->title;
        $structured = is_array($this->structured_summary) ? $this->structured_summary : null;

        // 1. Résumé complet pour NotebookLM (titres de section, sans liens).
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
            if ($quote = data_get($structured, 'quote')) {
                $resume .= "## Citation\n« {$quote} »\n\n";
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

        // 2. Prompt NotebookLM (via trait HasAdminShareContents).
        $prompt = $this->infographiePrompt('https://laveille.ai/actualites', 'Vulgarise les points clés de tous les documents dans une infographie engageante. Public : étudiants sans connaissances préalables.');
        $slides = $this->slidesPrompt('https://laveille.ai/actualites', 'Objectif : décrypter cette actualité et ce qu\'elle change concrètement. Public : étudiants, sans connaissances préalables.');

        // 3. Post réseaux sociaux natif (via trait HasAdminShareContents).
        // Post réseaux sociaux « 2026 » : hook structuré + En clair (tldr/résumé) + 👉 (point clé) + CTA (sans lien ni promo).
        $hook = $this->smartTrim((string) (data_get($structured, 'hook') ?: $this->title), 150);
        $plainDef = $this->smartTrim($this->stripLinks((string) (data_get($structured, 'tldr') ?: $this->summary)), 200);
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
            $candidates[] = (string) data_get($structured, 'quote');
            $candidates[] = (string) data_get($structured, 'why_important');
        }
        foreach ($candidates as $cand) {
            $c = trim($this->stripLinks((string) $cand));
            if ($c === '' || $this->textsAreSimilar($plainDef, $c)) {
                continue;
            }
            $interest = $this->smartTrim($c, 180);
            break;
        }
        $tags = ['#IA'];
        if ($this->category_tag) { $tags[] = '#' . $this->normalizeShareHashtag($this->category_tag); }
        $tags[] = '#Québec';
        $tags[] = '#VeilleIA';
        $linkedin = $this->buildLinkedInPost($hook, $plainDef, $interest, "Toi, t'en penses quoi ? 👇", $tags);
        $facebook = $this->buildFacebookPost($hook, $plainDef, $interest, "Toi, t'en penses quoi ? 👇", $tags);

        return [
            ['label' => 'Résumé (NotebookLM)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'NotebookLM Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'NotebookLM Diapositives', 'icon' => '🖼️', 'text' => $slides],
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
