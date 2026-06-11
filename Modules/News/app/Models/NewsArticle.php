<?php

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    protected array $activitylogFields = ['title', 'seo_title', 'summary', 'description', 'is_published', 'relevance_score'];
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
        'seo_status', // index | noindex | gone — élagage SEO réversible des vieilles news peu vues
    ];

    protected $casts = [
        'pub_date' => 'datetime',
        'is_published' => 'boolean',
        'structured_summary' => 'array',
        'relevance_score' => 'integer',
        'is_potential_duplicate_of' => 'integer',
        'dedup_score' => 'float',
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

    public function originalArticle(): BelongsTo
    {
        return $this->belongsTo(self::class, 'is_potential_duplicate_of');
    }

    public function duplicates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'is_potential_duplicate_of');
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
        return \Illuminate\Support\Str::limit(strip_tags($this->summary ?: $this->description ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        return route('news.show', $this->slug);
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
            $resume = "# {$title}\n\n" . strip_tags((string) $this->summary) . "\n\n" . strip_tags((string) $this->description);
        }
        $resume = $this->stripLinks($resume);

        // 2. Prompt NotebookLM (via trait HasAdminShareContents).
        $prompt = $this->infographiePrompt('https://laveille.ai/actualites', 'Vulgarise les points clés de tous les documents dans une infographie engageante. Public : étudiants sans connaissances préalables.');

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
        $social = $this->buildEngagingSocialPost($hook, $plainDef, $interest, "Toi, t'en penses quoi ? 👇", $tags);

        return [
            ['label' => 'Résumé (NotebookLM)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'NotebookLM Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'Post réseaux sociaux', 'icon' => '📣', 'text' => $social],
        ];
    }

}
