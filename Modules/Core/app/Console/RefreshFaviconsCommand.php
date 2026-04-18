<?php

declare(strict_types=1);

namespace Modules\Core\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\FaviconResolverService;

final class RefreshFaviconsCommand extends Command
{
    use HasKillSwitch;

    /** @var string */
    protected $signature = 'favicons:refresh
        {--expired-only : Rafraîchir seulement les entrées expirées}
        {--limit=50 : Nombre maximal d\'entrées à traiter}
        {--force : Ignorer le kill switch}';

    /** @var string */
    protected $description = 'Rafraîchit le cache des favicons (HEAD vers providers, MAJ resolved_url)';

    public function handle(): int
    {
        if (! $this->option('force') && $this->shouldSkipForKillSwitch('cron.favicons-refresh')) {
            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $expiredOnly = (bool) $this->option('expired-only');

        $query = DB::table('favicon_cache');

        if ($expiredOnly) {
            $query->where(function ($q): void {
                $q->where(function ($sub): void {
                    $sub->whereNotNull('resolved_url')
                        ->where('checked_at', '<', now()->subDays(30));
                })->orWhere(function ($sub): void {
                    $sub->whereNull('resolved_url')
                        ->where('checked_at', '<', now()->subDays(7));
                })->orWhereNull('checked_at');
            });
        }

        $entries = $query->orderBy('checked_at')->limit($limit)->get();

        if ($entries->isEmpty()) {
            $this->info('Aucune entrée à rafraîchir.');

            return self::SUCCESS;
        }

        $refreshed = 0;
        $unchanged = 0;
        $failed = 0;

        foreach ($entries as $entry) {
            $oldUrl = $entry->resolved_url;

            FaviconResolverService::forgetDomain($entry->domain);
            $newUrl = FaviconResolverService::resolve($entry->domain);

            if ($newUrl === null) {
                $failed++;
            } elseif ($newUrl !== $oldUrl) {
                $refreshed++;
            } else {
                $unchanged++;
            }
        }

        $this->info(sprintf(
            'Bilan : %d rafraîchi(s), %d inchangé(s), %d en échec.',
            $refreshed,
            $unchanged,
            $failed,
        ));

        return self::SUCCESS;
    }
}
