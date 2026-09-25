<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Settings\Facades\Settings;
use Modules\Tools\Models\Tool;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Bascule UN outil précis (par slug) en maintenance publique : 503 + Retry-After servis à tout
 * visiteur anonyme, sans toucher au reste du site (jamais `php artisan down`, qui met tout le
 * site en 503 - c'est ce qui a coûté 2 mois de trafic de recherche en juillet 2026). Générique :
 * réutilise le gate is_under_construction/construction_mode déjà en place (Tool::isAccessibleTo,
 * PublicToolController::show) pour n'importe quel outil.
 *
 * Se manoeuvre entièrement en base (aucun déploiement requis) : le ResponseCache est vidé après
 * chaque bascule pour que l'effet soit immédiat, même si une page était déjà en cache.
 */
class ToolMaintenanceCommand extends Command
{
    protected $signature = 'tools:maintenance {slug : Slug de l\'outil (ex. constructeur-prompts)} {--on : Active la maintenance publique} {--off : Désactive la maintenance publique}';

    protected $description = "Bascule un outil en maintenance publique (503 + Retry-After) le temps d'une réécriture, sans affecter le reste du site";

    public function handle(): int
    {
        $slug = trim((string) $this->argument('slug'));
        $tool = Tool::where('slug', $slug)->first();

        if (! $tool) {
            $this->error("Aucun outil avec le slug « {$slug} ».");

            return self::FAILURE;
        }

        $on = (bool) $this->option('on');
        $off = (bool) $this->option('off');

        if ($on && $off) {
            $this->error('--on et --off sont mutuellement exclusifs.');

            return self::FAILURE;
        }

        if (! $on && ! $off) {
            $this->printStatus($tool);

            return self::SUCCESS;
        }

        if ($on) {
            $tool->update(['is_under_construction' => true, 'construction_mode' => 'maintenance']);
            $this->clearResponseCache();

            $this->info("Maintenance ACTIVÉE pour « {$tool->name} » ({$slug}).");
            $this->line('Les visiteurs anonymes reçoivent désormais un 503 + Retry-After sur /outils/'.$slug.' (jamais de noindex : la page reste indexée).');
            $this->line('Toi (superadmin connecté) continues d\'y accéder normalement.');
            $this->printPreviewLink($slug);

            return self::SUCCESS;
        }

        $tool->update(['is_under_construction' => false]);
        $this->clearResponseCache();

        $this->info("Maintenance DÉSACTIVÉE pour « {$tool->name} » ({$slug}). L'outil réel est de nouveau public.");

        return self::SUCCESS;
    }

    private function printStatus(Tool $tool): void
    {
        $state = $tool->is_under_construction
            ? "EN MAINTENANCE ({$tool->construction_mode})"
            : 'PUBLIC';

        $this->line("« {$tool->name} » ({$tool->slug}) : {$state}.");
        $this->line('Pour activer :   php artisan tools:maintenance '.$tool->slug.' --on');
        $this->line('Pour désactiver : php artisan tools:maintenance '.$tool->slug.' --off');
    }

    /**
     * Le jeton d'aperçu vit en table settings (jamais en dur, règle du projet) - généré au
     * premier `--on` s'il n'existe pas encore, puis réutilisé tel quel aux bascules suivantes.
     */
    private function printPreviewLink(string $slug): void
    {
        $token = Settings::get(Tool::MAINTENANCE_PREVIEW_TOKEN_SETTING);

        if (! is_string($token) || $token === '') {
            $token = Str::random(48);
            Settings::set(Tool::MAINTENANCE_PREVIEW_TOKEN_SETTING, $token, 'string', 'secrets');
        }

        $url = url('/outils/'.$slug).'?'.Tool::MAINTENANCE_PREVIEW_QUERY.'='.$token;

        $this->line('');
        $this->line('Lien d\'aperçu personnel (pose un cookie de 30 jours, à ne pas partager) :');
        $this->line($url);
    }

    /**
     * Même geste que Setting::booted() (static::saved -> ResponseCache::clear()) : sans ce
     * vidage, une page déjà en cache (/outils/{slug}, middleware cacheResponse:600) continuerait
     * de servir l'ancien état jusqu'à 10 minutes après la bascule.
     */
    private function clearResponseCache(): void
    {
        ResponseCache::clear();
    }
}
