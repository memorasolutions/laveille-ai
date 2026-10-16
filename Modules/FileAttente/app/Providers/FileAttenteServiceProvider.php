<?php

declare(strict_types=1);

namespace Modules\FileAttente\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\FileAttente\Console\PurgeCommand;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class FileAttenteServiceProvider extends ServiceProvider
{
    protected string $name = 'FileAttente';

    protected string $nameLower = 'file-attente';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Ce fournisseur n'est chargé que si le module est ACTIVÉ (modules_statuses.json) : tout ce qui
     * est branché ici (carte /outils, tâche planifiée, commande) disparaît donc avec le module,
     * sans ligne résiduelle en base.
     */
    public function boot(): void
    {
        $module = require module_path($this->name, 'config/config.php');
        config(['file-attente' => array_replace_recursive($module, config('file-attente', []))]);
        $this->loadViewsFrom(module_path($this->name, 'resources/views'), 'fileattente');
        $this->loadTranslationsFrom(module_path($this->name, 'resources/lang'), 'fileattente');
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        $this->registerToolCard();
        $this->registerPurge();
    }

    public function provides(): array
    {
        return [];
    }

    /**
     * Carte sur /outils, ajoutée au rendu (jamais seedée : une ligne en base survivrait à la
     * désactivation du module). Aucun couplage fort avec Tools : sans son modèle ou sa vue, rien ne se passe.
     */
    private function registerToolCard(): void
    {
        View::composer('tools::public.index', function ($view): void {
            $toolClass = '\\Modules\\Tools\\Models\\Tool';
            if (! config('file-attente.show_in_tools', true) || ! class_exists($toolClass)) {
                return;
            }
            // En construction : la carte (qui mène à un 503) n'est visible que du super-administrateur.
            if (config('file-attente.under_construction', true) === true) {
                $user = auth()->user();
                if (! ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
                    return;
                }
            }
            $data = $view->getData();
            $tools = $data['tools'] ?? null;
            if (! $tools instanceof \Illuminate\Support\Collection) {
                return;
            }

            $card = new $toolClass([
                'name' => __('fileattente::ui.tool.name'),
                'slug' => 'file-attente',
                'description' => __('fileattente::ui.tool.description'),
                'icon' => '🎟️',
                'category' => 'communication',
                'is_active' => true,
                'is_under_construction' => false,
                'sort_order' => 9000,
                'views_count' => 0,
            ]);
            $card->id = -9001; // carte transitoire, jamais enregistrée
            // Le lien /outils/{slug} de la grille est exactement route('fileattente.create').

            $categories = (array) ($data['categories'] ?? []);
            $categories['communication'] = ($categories['communication'] ?? 0) + 1;

            $view->with('tools', $tools->concat([$card])->values())->with('categories', $categories);
        });
    }

    private function registerPurge(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PurgeCommand::class]);
        }

        $this->app->booted(function (): void {
            $this->app->make(Schedule::class)->command('fileattente:purge')->hourly()->withoutOverlapping();
        });
    }
}
