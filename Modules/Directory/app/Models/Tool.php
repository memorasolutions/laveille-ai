<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Concerns\HasAdminShareContents;
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\HasFallbackTranslatedSlug;
use Modules\Core\Traits\HasLifecycleStatus;
use Modules\Core\Traits\HasSponsorship;
use Modules\Directory\Traits\HasSuggestions;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Tool extends Model implements Searchable
{
    use HasAdminShareContents;
    use HasFallbackTranslatedSlug;
    use HasLifecycleStatus;
    use HasSponsorship;
    use HasSuggestions;
    use HasTranslations;
    use LogsActivity;
    use \Modules\Voting\Traits\HasCommunityVotes;
    use \Modules\SEO\Traits\NotifiesIndexNow;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'url', 'pricing', 'status', 'short_description', 'description', 'is_featured', 'lifecycle_status', 'lifecycle_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('tool')
            ->setDescriptionForEvent(fn (string $event): string => match ($event) {
                'created' => 'Outil créé',
                'updated' => 'Outil modifié',
                'deleted' => 'Outil supprimé',
                default => "Outil {$event}",
            });
    }

    public function getPublicUrl(): string
    {
        // 2026-07-19 (P0, signalé par l'utilisateur) : app.locale = 'fr_CA' mais 'slug' (champ
        // Spatie Translatable) n'est souvent renseigné que sous la clé 'fr' - sans repli, l'accès
        // à $this->slug pour un outil sans traduction 'fr_CA' explicite renvoyait null, et
        // route('directory.show', null) levait UrlGenerationException (500 sur /admin/directory,
        // et potentiellement sur toute page publique appelant cette méthode). config/translatable.php
        // n'étant pas publié, aucun fallback automatique de spatie/laravel-translatable ne s'applique
        // - repli manuel explicite ici : locale courante -> 'fr' -> première traduction disponible.
        $slug = $this->getTranslation('slug', app()->getLocale(), false)
            ?: $this->getTranslation('slug', 'fr', false)
            ?: collect($this->getTranslations('slug'))->first();

        return route('directory.show', $slug);
    }

    protected array $suggestableFields = [
        'description' => 'Description',
        'short_description' => 'Description courte',
        'pricing' => 'Tarification',
        'url' => 'URL',
        'core_features' => 'Fonctionnalités',
        'how_to_use' => 'Guide',
        'use_cases' => "Cas d'usage",
        'other' => 'Autre',
    ];

    protected $table = 'directory_tools';

    public array $translatable = ['name', 'slug', 'description', 'short_description', 'how_to_use', 'core_features', 'use_cases', 'pros', 'cons', 'education_pricing_details', 'review'];

    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'url', 'affiliate_url', 'logo',
        'pricing', 'status', 'clicks_count', 'is_featured', 'featured_until', 'featured_order', 'sort_order',
        'how_to_use', 'core_features', 'use_cases', 'faq', 'pros', 'cons',
        'screenshot', 'screenshot_locked', 'screenshot_focal_y', 'screenshot_master_stale', 'prices_converted_cad_at', 'website_type', 'launch_year', 'target_audience',
        'submitted_by',
        'last_enriched_at', 'enrichment_version',
        'parent_tool_id', 'ecosystem_tag',
        'has_education_pricing', 'education_pricing_type', 'education_pricing_details', 'education_pricing_url',
        'education_discount_type', 'education_target_audience', 'education_verification_required', 'education_official_url', 'education_last_checked_at',
        'is_academic_discount', 'education_level', 'privacy_compliance', 'learning_curve', 'has_api_access',
        'lifecycle_status', 'lifecycle_date', 'lifecycle_replacement_url', 'lifecycle_replacement_tool_id', 'lifecycle_notes',
        'aliases',
        'review',
        'tutorials_last_scanned_at', // 2026-05-05 #138 : fix critique - sans ca, tools:enrich-tutorials re-scannait toujours les memes 10 outils.
        'underlying_model', 'is_multimodal', 'output_types', 'opt_out_training', 'unique_value',
        'last_change_detected_at', 'last_change_type', 'last_change_note', // S90 #43 freshness signals
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function parentTool(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_tool_id');
    }

    public function lifecycleReplacement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'lifecycle_replacement_tool_id');
    }

    public function matchesName(string $candidate): int
    {
        $candidate = mb_strtolower(trim($candidate));
        if ($candidate === '') {
            return 0;
        }

        $names = [(string) ($this->getTranslation('name', 'fr_CA', false) ?? '')];

        if (is_array($this->aliases)) {
            foreach ($this->aliases as $alias) {
                if (is_string($alias) && trim($alias) !== '') {
                    $names[] = $alias;
                }
            }
        }

        $best = 0;

        foreach ($names as $n) {
            $n = mb_strtolower(trim($n));
            if ($n === '') {
                continue;
            }
            similar_text($candidate, $n, $percent);
            $best = (int) max($best, (int) round($percent));
        }

        return $best;
    }

    public function childTools(): HasMany
    {
        return $this->hasMany(self::class, 'parent_tool_id');
    }

    public function scopeEcosystem($query, string $tag)
    {
        return $query->where('ecosystem_tag', $tag);
    }

    public function getVisitUrl(): string
    {
        return $this->affiliate_url ?: $this->url ?? '#';
    }

    public function isAffiliate(): bool
    {
        return ! empty($this->affiliate_url);
    }

    protected $casts = [
        'is_featured' => 'boolean',
        'screenshot_locked' => 'boolean',
        'screenshot_focal_y' => 'integer',
        'screenshot_master_stale' => 'boolean',
        'prices_converted_cad_at' => 'datetime',
        'has_education_pricing' => 'boolean',
        'education_target_audience' => 'array',
        'education_verification_required' => 'boolean',
        'education_last_checked_at' => 'datetime',
        'is_academic_discount' => 'boolean',
        'education_level' => 'array',
        'learning_curve' => 'integer',
        'has_api_access' => 'boolean',
        'featured_until' => 'datetime',
        'featured_order' => 'integer',
        'faq' => 'array',
        'target_audience' => 'array',
        'last_enriched_at' => 'datetime',
        'enrichment_version' => 'integer',
        'lifecycle_date' => 'date',
        'aliases' => 'array',
        'is_multimodal' => 'boolean',
        'output_types' => 'array',
        'last_change_detected_at' => 'datetime', // S90 #43 freshness signals
    ];

    public function setPricingAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['pricing'] = null;
            return;
        }

        $normalized = mb_strtolower(trim((string) $value));
        $normalized = str_replace(['open-source', 'open source', 'opensource'], 'open_source', $normalized);

        $this->attributes['pricing'] = $normalized;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'directory_category_tool', 'directory_tool_id', 'directory_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'directory_tag_tool', 'directory_tool_id', 'directory_tag_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->where(fn ($q) => $q->whereNull('featured_until')->orWhere('featured_until', '>', now()))
            ->orderBy('featured_order')
            ->orderByDesc('clicks_count');
    }

    public function scopeNotArchived($query)
    {
        return $query->where(function ($q) {
            $q->where('lifecycle_status', '!=', 'archived')
                ->orWhereNull('lifecycle_status');
        });
    }

    public static function pricingDistribution(): array
    {
        return self::published()->notArchived()
            ->selectRaw('pricing, count(*) as cnt')
            ->groupBy('pricing')
            ->pluck('cnt', 'pricing')
            ->toArray();
    }

    public static function driftCount(int $days = 90): int
    {
        $cutoff = now()->subDays($days);

        return self::published()->notArchived()
            ->where(function ($q) use ($cutoff) {
                $q->where('last_enriched_at', '<', $cutoff)
                    ->orWhereNull('last_enriched_at');
            })
            ->count();
    }

    public static function neverCheckedCount(): int
    {
        return self::published()->notArchived()
            ->whereNull('last_enriched_at')
            ->count();
    }

    public static function countByStatus(): array
    {
        return self::query()
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
    }

    public const HEALTH_METRICS_TTL = 300;

    public const HEALTH_METRICS_CACHE_KEY = 'tool.health_metrics';

    public static function healthMetrics(): array
    {
        return Cache::remember(self::HEALTH_METRICS_CACHE_KEY, self::HEALTH_METRICS_TTL, function () {
            return [
                'distribution' => self::pricingDistribution(),
                'status' => self::countByStatus(),
                'drift_90' => self::driftCount(90),
                'drift_180' => self::driftCount(180),
                'never_checked' => self::neverCheckedCount(),
                'pending_reports' => \Modules\Directory\Models\ToolPricingReport::pendingCount(),
            ];
        });
    }

    public static function flushHealthMetricsCache(): void
    {
        Cache::forget(self::HEALTH_METRICS_CACHE_KEY);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ToolReview::class, 'directory_tool_id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(ToolDiscussion::class, 'directory_tool_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ToolResource::class, 'directory_tool_id');
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(ToolScreenshot::class, 'directory_tool_id');
    }

    public function alternatives(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'tool_alternatives', 'tool_id', 'alternative_tool_id')
            ->withPivot('relevance_score', 'source')
            ->withTimestamps();
    }

    public function alternativeOf(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'tool_alternatives', 'alternative_tool_id', 'tool_id')
            ->withPivot('relevance_score', 'source')
            ->withTimestamps();
    }

    /**
     * Actualités liées à cet outil (curation manuelle ou auto).
     */
    public function newsArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\News\Models\NewsArticle::class,
            'news_article_tool',
            'tool_id',
            'news_article_id'
        )->withPivot('source')->withTimestamps();
    }

    public function allAlternatives()
    {
        return $this->alternatives->merge($this->alternativeOf)->unique('id');
    }

    public function averageRating(): float
    {
        return (float) $this->reviews()->approved()->avg('rating') ?: 0;
    }

    public static function searchableFields(): array
    {
        // 2026-06-29 : 'aliases' ajouté — la colonne JSON est stockée en texte,
        // le LIKE %term% match un alias dans le tableau (ex. %GLM% dans ["GLM","GLM-5.1"]).
        return ['name', 'short_description', 'description', 'aliases'];
    }

    public static function searchSectionKey(): string
    {
        return 'annuaire';
    }

    public static function searchSectionLabel(): string
    {
        return __('Annuaire');
    }

    public static function searchSectionIcon(): string
    {
        return '🛠️';
    }

    public static function searchPriority(): int
    {
        return 30;
    }

    public function searchableResultTitle(): string
    {
        return $this->name;
    }

    public function searchableResultExcerpt(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->description ?: $this->short_description ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        return $this->getPublicUrl();
    }

    public function adminShareContents(): array
    {
        $name = $this->name ?? '';
        $resume = "# {$name}\n\n";
        $desc = ($this->short_description ?? '') !== '' ? $this->short_description : ($this->description ?? '');
        if ($desc !== '') { $resume .= "## Description\n{$desc}\n\n"; }
        if (($v = $this->core_features ?? '') !== '' && ! is_array($v)) { $resume .= "## Fonctionnalités\n{$v}\n\n"; }
        elseif (is_array($this->core_features) && $this->core_features !== []) { $resume .= "## Fonctionnalités\n- " . implode("\n- ", array_map('strval', $this->core_features)) . "\n\n"; }
        if (($v = $this->use_cases ?? '') !== '' && ! is_array($v)) { $resume .= "## Cas d'usage\n{$v}\n\n"; }
        elseif (is_array($this->use_cases) && $this->use_cases !== []) { $resume .= "## Cas d'usage\n- " . implode("\n- ", array_map('strval', $this->use_cases)) . "\n\n"; }
        if (is_array($this->pros) && $this->pros !== []) { $resume .= "## Avantages\n- " . implode("\n- ", array_map('strval', $this->pros)) . "\n\n"; }
        if (is_array($this->cons) && $this->cons !== []) { $resume .= "## Inconvénients\n- " . implode("\n- ", array_map('strval', $this->cons)) . "\n\n"; }
        if (($v = $this->pricing ?? '') !== '' && ! is_array($v)) { $resume .= "## Tarification\n{$v}\n\n"; }
        if (($v = $this->review ?? '') !== '') { $resume .= "## Avis\n{$v}\n\n"; }
        $resume = $this->stripLinks($resume);
        $prompt = $this->infographiePrompt('https://laveille.ai/annuaire', 'Présente l\'outil « ' . $name . ' » dans une infographie : à quoi il sert, pour qui, ses forces. Public : curieux sans connaissances préalables.');
        $slides = $this->slidesPrompt('https://laveille.ai/annuaire', 'Objectif : présenter l\'outil « ' . $name . ' » : à quoi il sert, pour qui, ses forces et limites. Public : curieux, sans connaissances préalables.');
        $cf = $this->core_features;
        $points = is_array($cf) ? array_map('strval', $cf) : array_values(array_filter(array_map('trim', explode(',', (string) $cf))));
        if ($points === [] && is_array($this->pros)) { $points = array_map('strval', $this->pros); }
        // Post réseaux sociaux « 2026 » : curiosity-gap + En clair + 👉 + CTA discussion (sans lien ni promo).
        $plainDef = $this->smartTrim($this->stripLinks((string) $desc), 200);
        $firstFeature = '';
        foreach ((is_array($points) ? $points : []) as $point) {
            if ((string) $point !== '') {
                $firstFeature = (string) $point;
                break;
            }
        }
        if ($firstFeature === '' && is_array($this->pros)) {
            foreach ($this->pros as $pro) {
                if ((string) $pro !== '') {
                    $firstFeature = (string) $pro;
                    break;
                }
            }
        }
        $interest = $firstFeature !== ''
            ? $this->smartTrim($this->stripLinks($firstFeature), 180)
            : "Le genre d'outil qui peut vite devenir indispensable dans ton coffre.";
        $hook = "Tu cherches un outil IA pour te simplifier la vie ? Laisse-moi te parler de {$name}. 👀";
        $cta = "Tu l'as déjà essayé ? Raconte en commentaire 👇";
        // Preuve sociale : nombre de tutoriels approuvés de l'outil (sans lien, masqué si 0) — best practice 2026.
        $tutoCount = (int) $this->resources()->where('is_approved', true)->count();
        $tutoBonus = '';
        if ($tutoCount > 0) {
            $mot = $tutoCount === 1 ? 'tutoriel' : 'tutoriels';
            $verbe = $tutoCount === 1 ? "t'attend" : "t'attendent";
            $tutoBonus = "🎓 {$tutoCount} {$mot} pour bien démarrer {$verbe} déjà sur la veille.";
        }
        $hashtags = ['#IA', '#OutilsIA', '#' . $this->normalizeShareHashtag((string) $name), '#Québec'];
        $linkedin = $this->buildLinkedInPost($hook, $plainDef, $interest, $cta, $hashtags, $tutoBonus);
        $facebook = $this->buildFacebookPost($hook, $plainDef, $interest, $cta, $hashtags, $tutoBonus);
        return [
            ['label' => 'Résumé (Gemini Notebook)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'Gemini Notebook Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'Gemini Notebook Diapositives', 'icon' => '🖼️', 'text' => $slides],
            ['label' => 'Post LinkedIn', 'icon' => '💼', 'text' => $linkedin],
            ['label' => 'Post Facebook', 'icon' => '📘', 'text' => $facebook],
        ];
    }
}
