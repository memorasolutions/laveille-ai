<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\Directory\Services\PrivacyNoticeService;
use Modules\Settings\Facades\Settings;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Interrupteur public du module « précaution données personnelles » : on | off | status.
 * Écrit le réglage runtime (aucun redéploiement) puis purge le cache de pages publiques.
 */
class PrivacyNoticeToggleCommand extends Command
{
    protected $signature = 'directory:privacy-notice {action=status : on|off|status}';

    protected $description = 'Active, désactive ou affiche l\'état de la note de précaution données personnelles (annuaire)';

    public function handle(PrivacyNoticeService $service): int
    {
        $action = (string) $this->argument('action');

        if (! in_array($action, ['on', 'off', 'status'], true)) {
            $this->error('Action inconnue : utiliser on, off ou status.');

            return self::INVALID;
        }

        if ($action !== 'status') {
            Settings::set(PrivacyNoticeService::SETTING_KEY, $action === 'on' ? '1' : '0', 'boolean', 'directory');
            $this->purgePageCache();
        }

        $this->info('Note de précaution données personnelles : '.($service->enabled() ? 'ACTIVÉE' : 'désactivée'));

        return self::SUCCESS;
    }

    private function purgePageCache(): void
    {
        // Le cache de réponses (spatie/laravel-responsecache) sert l'ancien rendu jusqu'à 7 jours.
        if (class_exists(\Spatie\ResponseCache\Facades\ResponseCache::class)) {
            \Spatie\ResponseCache\Facades\ResponseCache::clear();
        }
        Cache::forget('setting.'.PrivacyNoticeService::SETTING_KEY);
    }
}
