<?php

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Community\Traits\HasComments;
use Modules\Community\Traits\HasReports;
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\HasPublishedState;
use Modules\Core\Traits\LogsActivityStandard;
use Modules\Voting\Traits\HasCommunityVotes;

class NewsArticle extends Model implements Searchable
{
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
        $resume = trim((string) preg_replace('#https?://\S+#', '', $resume));

        // 2. Prompt NotebookLM (texte fixe).
        $prompt = <<<'PROMPT'
Lien à mettre dans l'infographie en bas au centre de façon apparente: https://laveille.ai/actualites

Langue : français québécois, tutoiement, ton conversationnel et accessible. Écris comme une vraie personne, pas comme une IA. Aucune majuscule à l'américaine (mais garder les majuscules des acronymes et en début de phrase). Pas de tiret cadratin.

Vulgarise les points clés de tous les documents dans une infographie engageante. Public : étudiants sans connaissances préalables.

Design : fond clair, style moderne et coloré, icônes simples. Bleu foncé pour les éléments importants, accents jaune ou orange pour les faits marquants. Beaucoup d'espace négatif.

Hiérarchie : message principal en gros, détails en plus petit. Chaque section doit donner envie de lire la suite. Visuel chaleureux, jamais corporatif.
PROMPT;

        // 3. Post réseaux sociaux natif (format A, ton québécois, sans lien externe).
        $hook = (string) (data_get($structured, 'hook') ?: $this->title);
        $kp = data_get($structured, 'key_points');
        $kp = is_array($kp) ? array_slice($kp, 0, 3) : [];
        $points = '';
        foreach ($kp as $point) {
            $points .= '→ ' . mb_substr(trim((string) $point), 0, 140) . "\n";
        }
        $social = trim($hook) . "\n\n" . rtrim($points) . "\n\ntoi, t'en penses quoi ?\n\nPlus d'actualités IA, en français, sur La veille de Stef.\n\n";
        $tags = ['#IA'];
        if ($this->category_tag && ($h = $this->normalizeShareHashtag($this->category_tag)) !== '') {
            $tags[] = '#' . $h;
        }
        $tags[] = '#Québec';
        $tags[] = '#VeilleIA';
        $social .= implode(' ', $tags);

        return [
            ['label' => 'Résumé (NotebookLM)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'Prompt NotebookLM', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'Post réseaux sociaux', 'icon' => '📣', 'text' => $social],
        ];
    }

    private function normalizeShareHashtag(string $tag): string
    {
        $t = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $tag);
        $t = (string) preg_replace('/[^a-zA-Z0-9\s]/', '', $t);
        $words = array_filter(preg_split('/\s+/', trim($t)) ?: []);

        return implode('', array_map('ucfirst', $words));
    }
}
