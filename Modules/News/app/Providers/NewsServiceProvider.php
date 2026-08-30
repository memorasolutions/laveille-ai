<?php

declare(strict_types=1);

namespace Modules\News\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class NewsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'News';

    protected string $nameLower = 'news';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        \Modules\News\Models\NewsArticle::observe(\Modules\News\Observers\NewsArticleObserver::class);

        // ACTION: enregistrement du composant Livewire d'édition inline des outils liés.
        // MCP: SELF (<5 lignes)
        // RAISON: composant front-end admin-gaté sur la page publique actualité.
        Livewire::component('news.article-tools-editor', \Modules\News\Livewire\ArticleToolsEditor::class);

        // ACTION: popup rapide « Outils liés » depuis la liste /actualites (icône engrenage).
        // MCP: SELF (<5 lignes)
        // RAISON: réutilise ArticleToolsEditor tel quel, wrapper d'aiguillage seulement.
        Livewire::component('news.tools-quick-edit-modal', \Modules\News\Livewire\NewsToolsQuickEditModal::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->tag([\Modules\News\Models\NewsArticle::class], 'searchable.models');
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\News\Console\FetchNewsCommand::class,
            \Modules\News\Console\RescrapeImagesCommand::class,
            \Modules\News\Console\RegenerateSlugsCommand::class,
            \Modules\News\Console\RescrapeGoogleImagesCommand::class,
            \Modules\News\Console\ReprocessArticlesCommand::class,
            \Modules\News\Console\PruneSeoCommand::class,
            \Modules\News\Console\BackfillAutoToolDetectionCommand::class,
            \Modules\News\Console\NotifyNewsDigestCommand::class,
            // Actus 2.0, révision 2026-08-17 (prompt d'orchestration Claude Code CLI) - SEULE
            // porte d'écriture bornée pour l'agent, voir docblock de la classe.
            \Modules\News\Console\NewsApplyCommand::class,
            // Actus 2.0, addendum 2026-08-17 (« purge garantie sur tous les chemins de
            // publication ») - filet de vérification quotidien, planifié dans routes/console.php.
            \Modules\News\Console\VerifySourcePurgeCommand::class,
            // Implémentation /actu2 - volet serveur (2026-08-17) : point d'entrée LECTURE SEULE
            // du skill Claude Code local (JSON canonique) et récolte de l'ORIGINAL (2e porte
            // d'écriture bornée, aux côtés de NewsApplyCommand) - voir docblocs des classes.
            \Modules\News\Console\NewsBriefCommand::class,
            \Modules\News\Console\NewsSourceCommand::class,
            // Améliorations en attente (2026-08-17), point 1 - création manuelle d'une fiche
            // brouillon à partir d'un lien, voir docblock de la classe et
            // NewsArticle::createManualDraft() (DRY, même implémentation que l'écran web).
            \Modules\News\Console\NewsCreateDraftCommand::class,
            // Chantier AdSense « faible valeur » (2026-08-18) - retrait SEO-sûr et réversible
            // d'un lot de fiches (410 Gone), voir docblock de la classe.
            \Modules\News\Console\RetireArticlesCommand::class,
            // Fenêtre glissante des brouillons bruts (design doc SPEC-PRUNE-DRAFTS, 2026-08-20) -
            // purge sûre et réversible du backlog /admin/news/composition, voir docblock.
            \Modules\News\Console\PruneDraftsCommand::class,
            // Rendement réel des sources (2026-08-23) : lecture seule, jamais planifiée. Elle
            // existe pour qu'on arbitre la liste des sources sur des chiffres plutôt qu'à
            // l'intuition, qui surestime toujours ce qui fait du bruit.
            \Modules\News\Console\SourcesReportCommand::class,
            // Traduction des titres précalculée (2026-08-24) - retire la traduction du chemin
            // synchrone de l'écran de composition, voir docblock de la classe et le plafond
            // retiré dans NewsCompositionController::candidates().
            \Modules\News\Console\TranslateTitlesCommand::class,
            // Opération de masse distincte annoncée hors périmètre par le correctif v1.237.5
            // (image de repli bakant le mauvais titre) - jamais planifiée, voir docblock de la
            // classe pour les gardes (curatée/idempotence/backup).
            \Modules\News\Console\RegenerateFallbackImagesCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            // Élagage SEO QUOTIDIEN des actualités périmées (02:10 heure Québec) - fenêtre de
            // fraîcheur, cf. Modules/News/config/seo_prune.php. Passé de mensuel à quotidien le
            // 2026-08-09 : avec le flux quotidien d'actualités, un passage mensuel laissait des
            // centaines de fiches périmées indexées entre deux passages (refus AdSense).
            // Réversible (flag DB) ; piloté par config('news.seo_prune').
            $schedule->command('news:prune-seo')
                ->dailyAt('02:10')
                ->timezone('America/Toronto')
                ->onOneServer();

            // Purge SÛRE des brouillons bruts QUOTIDIENNE (design doc SPEC-PRUNE-DRAFTS,
            // 2026-08-20) - fenêtre glissante des 200 brouillons les plus récents ; ne touche
            // jamais une fiche publiée/composée/retirée/relue (garde-fou absolu, voir docblock de
            // la classe). Backup horodaté AVANT toute suppression, rotation interne des 14
            // derniers backups. Idempotente : rien à faire si moins de 200 brouillons bruts
            // existent. Décalée de 30 minutes après news:prune-seo pour éviter tout chevauchement.
            // 2026-08-23, demande du fondateur : l'écran de composition ne doit montrer que les
            // actualités du jour, et celles de la veille doivent disparaître. La fenêtre passe
            // donc d'un COMPTE (200 plus récents, soit environ cinq jours au débit actuel) à un
            // JOUR de collecte. À 02h40 heure du Québec, « aujourd'hui » vient de commencer :
            // la purge emporte exactement la veille, ce qui est l'effet voulu.
            // Les quatre garde-fous restent entiers - jamais une fiche publiée, retirée, relue ni
            // composée - et un backup JSON restaurable est écrit AVANT toute suppression.
            $schedule->command('news:prune-drafts --keep-days=1')
                ->dailyAt('02:40')
                ->timezone('America/Toronto')
                ->onOneServer();

            // Traduction des titres PRÉCALCULÉE, HORAIRE (2026-08-24) - retire la traduction du
            // chemin synchrone de l'écran de composition (voir docblock de
            // Modules\News\Console\TranslateTitlesCommand). Minute 25 : la collecte `news:fetch`
            // (cron cPanel, hors scheduler Laravel) tourne à la minute 15, donc la minute 25 la
            // suit et laisse dix minutes à la collecte pour terminer avant que la traduction
            // parte sur les titres fraîchement récoltés.
            $schedule->command('news:translate-titles --limit=200')
                ->hourlyAt(25)
                ->timezone('America/Toronto')
                ->onOneServer()
                ->withoutOverlapping();
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($module_config, $existing)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
