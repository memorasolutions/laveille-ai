<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Concerns\HasAdminShareContents;
use Modules\Core\Traits\TracksEditorialModification;
use Modules\Tools\Models\Concerns\Shareable;

class Tool extends Model
{
    use HasAdminShareContents;
    use Shareable;
    use TracksEditorialModification;

    // Mode "maintenance" (2026-09-19) : 3e valeur de construction_mode, distincte de
    // construction/revision (200 + noindex, page qui ne sera jamais indexée) - un outil DÉJÀ
    // public qu'on ferme TEMPORAIREMENT (503 + Retry-After, JAMAIS de noindex, cf. commande
    // tools:maintenance). Le propriétaire garde 2 voies : superadmin connecté, ou ce jeton
    // d'aperçu (réglage settings, jamais en dur) posé en cookie de longue durée via l'URL.
    public const MAINTENANCE_PREVIEW_QUERY = 'apercu';

    public const MAINTENANCE_PREVIEW_COOKIE = 'lv_tools_apercu';

    public const MAINTENANCE_PREVIEW_TOKEN_SETTING = 'tools.maintenance_preview_token';

    public const MAINTENANCE_RETRY_AFTER_SETTING = 'tools.maintenance_retry_after_seconds';

    public const MAINTENANCE_RETRY_AFTER_DEFAULT_SECONDS = 7200;

    // ACTION : contenu réellement éditorial de l'outil (voir Modules\Core\Traits\
    // TracksEditorialModification) - exclut is_active/is_under_construction/construction_mode/
    // sort_order/views_count, qui décrivent un ÉTAT opérationnel, pas le contenu affiché.
    // MCP: SELF (<5 lignes)
    // RAISON: docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md, MESURE B - le sitemap
    // publiait déjà `updated_at` (touché par ViewCounterService::record()) comme lastmod.
    protected array $editorialFields = [
        'name', 'description', 'answer_summary', 'answer_points', 'icon', 'featured_image', 'category',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'answer_summary',
        'answer_points',
        'icon',
        'featured_image',
        'is_active',
        'is_under_construction',
        'construction_mode',
        'sort_order',
        'category',
        'views_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_under_construction' => 'boolean',
        'views_count' => 'integer',
        'answer_points' => 'array',
        'content_updated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Contenus de partage admin (superadmin) pour un outil INTERACTIF (/outils/{slug}) :
     * résumé Gemini Notebook, prompts infographie + diapositives, posts LinkedIn/Facebook/X +
     * légende Instagram. Réutilise le trait DRY HasAdminShareContents (zéro duplication de logique
     * sociale). Robuste si la description est vide. Angle : faire ESSAYER l'outil (gratuit).
     */
    public function adminShareContents(): array
    {
        $name = trim((string) ($this->name ?? '')) !== '' ? (string) $this->name : 'cet outil';
        $url = url('/outils/' . $this->slug);

        $description = trim((string) ($this->description ?? ''));
        $answerSummary = trim((string) ($this->answer_summary ?? ''));

        // Résumé (Gemini Notebook) : markdown propre, sans liens.
        $resume = "# {$name}\n\n";
        if ($description !== '') {
            $resume .= "## Description\n{$description}\n\n";
        }
        if ($answerSummary !== '') {
            $resume .= "## En bref\n{$answerSummary}\n\n";
        }
        if ($description === '' && $answerSummary === '') {
            $resume .= "## Description\nOutil IA gratuit proposé par La veille de Stef.\n\n";
        }
        $resume = $this->stripLinks($resume);

        // Prompts Gemini Notebook.
        $prompt = $this->infographiePrompt($url, 'Présente l\'outil interactif « ' . $name . ' » dans une infographie : à quoi il sert, à qui il s\'adresse, pourquoi l\'essayer. Public : curieux sans connaissances préalables.');
        $slides = $this->slidesPrompt($url, 'Objectif : présenter l\'outil interactif « ' . $name . ' » : à quoi il sert, pour qui, comment l\'essayer. Public : curieux, sans connaissances préalables.');

        // Base sociale (sans lien).
        $plainSource = $description !== '' ? $description : $answerSummary;
        $plainDef = $this->smartTrim($this->stripLinks($plainSource), 200);
        if ($plainDef === '') {
            $plainDef = "Un outil IA gratuit, simple à essayer.";
        }
        $interest = $answerSummary !== '' && $answerSummary !== $plainSource
            ? $this->smartTrim($this->stripLinks($answerSummary), 180)
            : "Le genre d'outil gratuit qui peut vite devenir indispensable.";

        $hook = "Connais-tu {$name} ? Un outil gratuit pour te simplifier la vie avec l'IA. 👀";
        $cta = "Tu l'essaies ? Dis-moi ce que tu en penses en commentaire 👇";
        $hashtags = ['#IA', '#OutilsIA', '#' . $this->normalizeShareHashtag($name), '#Québec'];

        $linkedin = $this->buildLinkedInPost($hook, $plainDef, $interest, $cta, $hashtags);
        $facebook = $this->buildFacebookPost($hook, $plainDef, $interest, $cta, $hashtags);

        // Post X (Twitter) : court (≤ 280 car), punché, 1-2 hashtags, aucun lien.
        $xHook = $this->stripLinks("{$name} : un outil IA gratuit à essayer 👀");
        $xBody = $this->smartTrim($plainDef, 160);
        $xTags = '#IA #OutilsIA';
        $x = $this->smartTrim(trim($xHook . "\n\n" . $xBody . "\n\n" . $xTags), 278);

        // Légende Instagram : accroche + 2-3 lignes + bloc de hashtags plus fourni.
        $igHashtags = array_unique(array_filter([
            '#IA', '#IntelligenceArtificielle', '#OutilsIA', '#Productivité', '#Québec',
            '#' . $this->normalizeShareHashtag($name),
        ]));
        $instagram = trim(implode("\n\n", array_filter([
            "✨ Connais-tu {$name} ?",
            $plainDef,
            "Un outil gratuit à essayer dès maintenant. Le lien est dans la bio 👆",
        ]))) . "\n\n" . implode(' ', $igHashtags);

        return [
            ['label' => 'Résumé (Gemini Notebook)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'Gemini Notebook Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'Gemini Notebook Diapositives', 'icon' => '🖼️', 'text' => $slides],
            ['label' => 'Post LinkedIn', 'icon' => '💼', 'text' => $linkedin],
            ['label' => 'Post Facebook', 'icon' => '📘', 'text' => $facebook],
            ['label' => 'Post X', 'icon' => '✖️', 'text' => $x],
            ['label' => 'Légende Instagram', 'icon' => '📸', 'text' => $instagram],
        ];
    }

    /**
     * Round 12 (2026-07-27) : logique DRY du gate is_under_construction, jusqu'ici dupliquée
     * dans UserSavedController - à réutiliser par tout consommateur hors du middleware
     * EnsureToolNotUnderConstruction (routes satellites qui agrègent des données de l'outil
     * sans passer par ses propres routes, ex. /user/saved, exports RGPD).
     *
     * $tool (round 22, 2026-07-27) : passer le modèle déjà chargé quand l'appelant l'a - évite
     * une requête Tool redondante (trouvé par la passe adversariale sur PublicToolController::show(),
     * qui charge $tool puis relançait une 2e requête identique via cette méthode, sur la page la
     * plus visitée du site). Callers sans modèle en main (middleware, exports, menu) passent null.
     */
    private static ?bool $gateColumnExistsCache = null;

    public static function isAccessibleTo(string $slug, $user, ?self $tool = null): bool
    {
        // Round 33 (2026-07-27) : Schema::hasTable()/hasColumn() relancent 2 requêtes
        // information_schema à CHAQUE appel (jamais mises en cache par Laravel) - trouvé par
        // la passe adversariale sur la page la plus visitée du site, où cette méthode est
        // appelée plusieurs fois par requête (contrôleur + composant menu). Le schéma ne change
        // qu'au déploiement d'une migration, jamais en cours de vie du worker - un cache
        // statique par process est donc sûr et élimine ces requêtes redondantes.
        self::$gateColumnExistsCache ??= \Illuminate\Support\Facades\Schema::hasTable('tools')
            && \Illuminate\Support\Facades\Schema::hasColumn('tools', 'is_under_construction');

        if (! self::$gateColumnExistsCache) {
            return true;
        }

        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        $tool = $tool ?? static::where('slug', $slug)->first();

        if (! $tool || ! $tool->is_under_construction) {
            return true;
        }

        // Round "maintenance" (2026-09-19) : le contournement par jeton d'aperçu ne concerne QUE
        // ce mode - condition testée EN PREMIER pour ne déclencher aucune requête/lecture settings
        // supplémentaire sur les modes historiques construction/revision (round 33, 2026-07-27,
        // avait déjà mesuré et figé le nombre de requêtes de cette méthode par un test dédié).
        if ($tool->construction_mode === 'maintenance' && self::hasValidMaintenancePreviewBypass()) {
            return true;
        }

        return false;
    }

    /**
     * Contournement "aperçu propriétaire" du mode maintenance : un paramètre d'URL valide
     * (?apercu=...) pose un cookie de contournement de 30 jours (pour un accès mobile sans
     * connexion), puis ce même cookie suffit sur les visites suivantes. Le jeton attendu vit en
     * table settings (jamais en dur dans le code, règle du projet) - régénérable sans
     * déploiement via `tools:maintenance {slug} --on`.
     */
    private static function hasValidMaintenancePreviewBypass(): bool
    {
        if (! class_exists(\Modules\Settings\Facades\Settings::class)) {
            return false;
        }

        $token = \Modules\Settings\Facades\Settings::get(self::MAINTENANCE_PREVIEW_TOKEN_SETTING);

        if (! is_string($token) || $token === '') {
            return false;
        }

        $request = request();

        if (! $request) {
            return false;
        }

        $paramToken = (string) $request->query(self::MAINTENANCE_PREVIEW_QUERY, '');

        if ($paramToken !== '' && hash_equals($token, $paramToken)) {
            \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::make(
                self::MAINTENANCE_PREVIEW_COOKIE,
                $token,
                60 * 24 * 30, // 30 jours, comme le cookie quest_email déjà en place (QuestController)
                '/',
                null,
                true,
                true,
                false,
                'lax'
            ));

            return true;
        }

        $cookieToken = (string) $request->cookie(self::MAINTENANCE_PREVIEW_COOKIE, '');

        return $cookieToken !== '' && hash_equals($token, $cookieToken);
    }

    /**
     * Valeur du Retry-After (secondes) servi avec le 503 en mode maintenance - réglage settings,
     * ajustable sans déploiement ; valeur de repli si le réglage n'existe pas encore.
     */
    public static function maintenanceRetryAfterSeconds(): int
    {
        if (! class_exists(\Modules\Settings\Facades\Settings::class)) {
            return self::MAINTENANCE_RETRY_AFTER_DEFAULT_SECONDS;
        }

        return (int) \Modules\Settings\Facades\Settings::get(
            self::MAINTENANCE_RETRY_AFTER_SETTING,
            self::MAINTENANCE_RETRY_AFTER_DEFAULT_SECONDS
        );
    }
}
