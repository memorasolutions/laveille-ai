<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Mews\Purifier\Facades\Purifier;
use Modules\Blog\Database\Factories\ArticleFactory;
use Modules\Blog\States\ArticleState;
use Modules\Core\Concerns\HasAdminShareContents;
use Modules\Core\Contracts\Searchable as SearchableContract;
use Modules\Blog\States\DraftArticleState;
use Modules\Blog\States\PublishedArticleState;
use Modules\Core\Services\SocialImageResolver;
use Modules\Core\Traits\HasPreviewToken;
use Modules\CustomFields\Traits\HasCustomFields;
use Modules\Tenancy\Traits\BelongsToTenant;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\ModelStates\HasStates;
use Spatie\ResponseCache\Facades\ResponseCache;
use Spatie\Translatable\HasTranslations;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static> published()
 */
class Article extends Model implements SearchableContract
{
    use BelongsToTenant, HasAdminShareContents, HasCustomFields, HasFactory, HasPreviewToken, HasStates, HasTranslations, LogsActivity, Searchable, SoftDeletes;
    use \Modules\SEO\Traits\NotifiesIndexNow;

    public function getPublicUrl(): string
    {
        // Round 74 (2026-07-27, passe adversariale) : même pattern que Tool::getPublicUrl()
        // (Modules/Directory, P0 2026-07-19) - $this->slug pour la locale courante peut être une
        // chaîne vide si l'article n'a de traduction 'slug' que pour une autre locale (ex. 'fr_CA'
        // seulement, visiteur en 'en'). config/translatable.php n'étant pas publié, aucun repli
        // automatique de spatie/laravel-translatable ne s'applique - repli manuel explicite :
        // locale courante -> 'fr_CA' -> 1re traduction disponible.
        $slug = $this->getTranslation('slug', app()->getLocale(), false)
            ?: $this->getTranslation('slug', 'fr_CA', false)
            ?: collect($this->getTranslations('slug'))->first();

        return url('/blog/' . $slug);
    }

    public array $translatable = ['title', 'slug', 'content', 'excerpt'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Article {$eventName}");
    }

    protected static function booted(): void
    {
        static::saved(fn () => ResponseCache::clear());
        static::deleted(fn () => ResponseCache::clear());
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'video_url',
        'video_summary',
        'answer_summary',
        'answer_points',
        'status',
        'published_at',
        'category',
        'category_id',
        'tags',
        'meta',
        'expired_at',
        'user_id',
        'tenant_id',
        'is_featured',
        'content_password',
        'format',
        'preview_token',
        'submitted_by',
        'submission_status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
        'status' => ArticleState::class,
        'tags' => 'array',
        'meta' => 'array',
        'answer_points' => 'array',
        'is_featured' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                // ACTION : dériver le slug de la 1re traduction NON VIDE, jamais de $model->title
                // (dépendant de la locale courante) - sinon un article sans traduction dans la
                // locale d'app produit un slug vide, qui casse toute génération de lien route()
                // vers lui (UrlGenerationException, page recherche admin en 500).
                // MCP: SELF (<5 lignes)
                // RAISON: triage tests hérités 2026-08-18 (Phase176) - vrai bug de robustesse.
                $title = collect($model->getTranslations('title'))->first(fn ($v) => filled($v))
                    ?? $model->title;
                $model->slug = Str::slug((string) $title);
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->whereState('status', PublishedArticleState::class)
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->whereState('status', DraftArticleState::class);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    /**
     * Recherche blog réutilisable et durcie (source de vérité unique).
     *
     * Échappe les wildcards LIKE (%, _, \) pour qu'ils soient traités
     * littéralement (un terme « 100% » ou « a_b » ne devient plus un joker)
     * et cible les 3 colonnes traduisibles (title/content/excerpt) sur la
     * locale courante. Terme vide => no-op (query inchangé).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $term
     * @param  string|null  $locale
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchText($query, ?string $term, ?string $locale = null)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $locale ??= app()->getLocale();
        // MySQL : le caractère d'échappement LIKE par défaut est le backslash.
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function ($q) use ($like, $locale) {
            $q->orWhere("title->{$locale}", 'like', $like)
                ->orWhere("content->{$locale}", 'like', $like)
                ->orWhere("excerpt->{$locale}", 'like', $like);
        });
    }

    public function isPasswordProtected(): bool
    {
        return ! empty($this->content_password);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->getTranslation('title', app()->getLocale()),
            'content' => $this->getTranslation('content', app()->getLocale()),
            'excerpt' => $this->getTranslation('excerpt', app()->getLocale()),
            'category' => $this->category,
            'tags' => $this->tags,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status instanceof PublishedArticleState;
    }

    protected function safeContent(): Attribute
    {
        // Profil 'article' (config/purifier.php) : meme profil que celui applique au contenu
        // publie (voir ArticleSubmissionController) - la revue admin voit ainsi la structure
        // reelle (h2-h6, tableaux...) plutot qu'une version sur-purifiee par le profil 'default'
        // (audit 2026-07-22, incoherence trouvee entre l'apercu admin et le contenu publie).
        return Attribute::get(fn () => Purifier::clean($this->content ?? '', 'article'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $field = $field ?? $this->getRouteKeyName();

        if (in_array($field, $this->translatable)) {
            return $this->where("{$field}->{$this->getLocale()}", $value)->first();
        }

        return $this->where($field, $value)->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function isGuestPost(): bool
    {
        return $this->submitted_by !== null;
    }

    public function getAuthorName(): string
    {
        if ($this->isGuestPost() && $this->submittedByUser) {
            return $this->submittedByUser->name;
        }

        return $this->user->name ?? 'Admin';
    }

    /**
     * Récupère le titre pour Google (référencement) depuis les métadonnées.
     *
     * Lit UNIQUEMENT meta['title'] - ne JAMAIS ajouter de repli sur meta['seo_title']. Cette
     * seconde clé existe encore sur au moins 5 articles mais porte une valeur PÉRIMÉE (« Concentré
     * IA semaine du 12 au 19 avril 2026 »), écrite par des tentatives précédentes qui visaient la
     * mauvaise clé - AUCUNE d'elles n'a donc jamais été lue ni affichée. Brancher meta['seo_title']
     * ici en repli ressusciterait ces valeurs mortes sur les articles concernés. Le formulaire
     * d'édition (Modules/Blog/resources/views/themes/backend/admin/articles/edit.blade.php, et son
     * équivalent non thémé) ainsi que ArticleController@update écrivent exclusivement dans
     * meta['title'].
     */
    public function getSeoTitleAttribute(): ?string
    {
        return $this->meta['title'] ?? null;
    }

    /**
     * URL affichable de l'image mise en avant, quelle que soit la convention de stockage
     * (upload admin: "articles/x.jpg" sans préfixe ; import WordPress: "storage/blog/x.jpg" déjà préfixé).
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (empty($this->featured_image)) {
            return null;
        }

        if (str_starts_with($this->featured_image, 'http')) {
            return asset($this->featured_image);
        }

        // 2026-07-25 #1298 : vérifier l'existence physique avant de générer l'URL - une valeur
        // DB pointant vers un fichier jamais réellement téléversé (ex. 12 articles Concentré
        // hebdo avec un chemin `images/blog/...` fantôme, jamais uploadé par le script de
        // publication) rendait silencieusement une <img> cassée sur la page publiée. Repli sur
        // l'image par défaut du site (déjà utilisée comme fallback og:image ailleurs) plutôt que
        // de propager une URL 404.
        if (str_starts_with($this->featured_image, 'storage/')) {
            return file_exists(public_path($this->featured_image))
                ? asset($this->featured_image)
                : asset('images/og-image.png');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->exists($this->featured_image)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->featured_image)
            : asset('images/og-image.png');
    }

    /**
     * Variante « partageable » de featured_image_url, réservée à og:image (aperçu Facebook /
     * LinkedIn / etc) - JAMAIS en WebP ni en AVIF (audit 2026-08-22 : ces formats ne sont
     * fiables sur aucun grand réseau social, l'aperçu reste silencieusement vide sans cette
     * protection). L'affichage normal du site continue d'utiliser featured_image_url tel quel :
     * le WebP y reste légitime et plus léger, on ne dégrade donc pas cet accesseur-là.
     *
     * Délègue la décision « WebP/AVIF -> repli » à Modules\Core\Services\SocialImageResolver
     * (même service que Glossaire/Actualités/Outils), en normalisant d'abord le chemin stocké
     * vers sa forme relative à public_path() - un featured_image d'upload admin ("articles/x.jpg")
     * vit sur le disque Storage "public", accessible publiquement via le lien symbolique
     * public/storage/, donc "storage/articles/x.jpg" du point de vue de public_path().
     */
    public function getFeaturedImageShareableUrlAttribute(): ?string
    {
        if (empty($this->featured_image)) {
            return null;
        }

        $path = $this->featured_image;

        if (!str_starts_with($path, 'http') && !str_starts_with($path, 'storage/')) {
            $path = 'storage/' . $path;
        }

        $shareablePath = SocialImageResolver::shareable($path);

        return empty($shareablePath) ? null : asset($shareablePath);
    }

    public function scopePendingSubmissions($query)
    {
        return $query->where('submission_status', 'pending');
    }

    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function tagsRelation(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->orderByDesc('revision_number');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('position');
    }

    /**
     * Module « vérification » étendu au blogue (2026-08-31) - liste des vérifications
     * attachées à l'article, jamais un verdict global (décision de structure du 2026-08-31).
     * Vocabulaire consommé depuis NewsArticle::FACT_CHECK_VERDICTS, jamais copié ici.
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(ArticleVerification::class)->ordered();
    }

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }

    public static function searchableFields(): array
    {
        return ['title', 'content', 'excerpt'];
    }

    public static function searchSectionKey(): string
    {
        return 'blog';
    }

    public static function searchSectionLabel(): string
    {
        return __('Blog');
    }

    public static function searchSectionIcon(): string
    {
        return '📝';
    }

    public static function searchPriority(): int
    {
        return 20;
    }

    public function searchableResultTitle(): string
    {
        return $this->title;
    }

    public function searchableResultExcerpt(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->content ?: $this->excerpt ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        // 2026-08-31 (#2092) : route('blog.show', $this->slug) était encore un accès brut, alors
        // que getPublicUrl() (ci-dessus) protège déjà ce même besoin depuis le 27 juillet 2026 -
        // ne pas réinventer une seconde résolution, réutiliser celle qui existe déjà.
        return $this->getPublicUrl();
    }

    public function adminShareContents(): array
    {
        $title = $this->title ?? '';
        $excerpt = (string) ($this->excerpt ?? '');
        $resume = $this->stripLinks("# {$title}\n\n" . $excerpt . "\n\n" . strip_tags((string) ($this->content ?? '')));
        $prompt = $this->infographiePrompt('https://laveille.ai/blog', 'Vulgarise les idées clés de cet article dans une infographie engageante. Public : étudiants sans connaissances préalables.');
        $slides = $this->slidesPrompt('https://laveille.ai/blog', 'Objectif : vulgariser les idées clés de cet article et ce qu\'il faut en retenir. Public : étudiants, sans connaissances préalables.');
        // Post réseaux sociaux « 2026 » : titre en accroche + En clair (1re phrase) + 👉 (2e phrase) + CTA (sans lien ni promo).
        $sent = preg_split('/(?<=[.!?])\s+/', $excerpt, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $plainDef = isset($sent[0]) ? $this->smartTrim($this->stripLinks((string) $sent[0]), 200) : $this->smartTrim($this->stripLinks((string) $excerpt), 200);
        $interest = isset($sent[1]) ? $this->smartTrim($this->stripLinks((string) $sent[1]), 180) : '';
        $hook = $this->smartTrim(trim($title !== '' ? $title : $excerpt), 150);
        $cta = "Ça résonne avec ton expérience ? Dis-moi ce que t'en penses 👇";
        $tags = is_array($this->tags) ? $this->tags : [];
        $hashtags = array_merge(['#IA'], array_map(fn ($t) => '#' . $this->normalizeShareHashtag((string) $t), array_slice($tags, 0, 2)), ['#Québec', '#VeilleIA']);
        $linkedin = $this->buildLinkedInPost($hook, $plainDef, $interest, $cta, $hashtags);
        $facebook = $this->buildFacebookPost($hook, $plainDef, $interest, $cta, $hashtags);
        return [
            ['label' => 'Résumé (Gemini Notebook)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'Gemini Notebook Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'Gemini Notebook Diapositives', 'icon' => '🖼️', 'text' => $slides],
            ['label' => 'Post LinkedIn', 'icon' => '💼', 'text' => $linkedin],
            ['label' => 'Post Facebook', 'icon' => '📘', 'text' => $facebook],
        ];
    }
}
