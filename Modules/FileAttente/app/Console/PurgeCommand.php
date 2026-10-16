<?php

declare(strict_types=1);

namespace Modules\FileAttente\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\FileAttente\Models\Board;
use Modules\FileAttente\Models\Participant;

/**
 * Purge des tableaux expirés (spec 6.5, Loi 25) : plafond de vie depuis la création, jamais prolongé
 * par une consultation. Supprime les tableaux dont purge_after est dépassé AVEC leurs participants,
 * leurs entrées de cache et les PDF temporaires éventuels. Idempotente : sans donnée expirée, ne fait rien.
 * Ne touche QUE les données éphémères de cet outil.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class PurgeCommand extends Command
{
    protected $signature = 'fileattente:purge';

    protected $description = 'Supprime les tableaux de file d’attente expirés, leurs participants et les PDF temporaires';

    public function handle(): int
    {
        $boards = 0;
        $people = 0;

        // chunkById : les suppressions en cours de parcours ne décalent pas les lots.
        Board::where('purge_after', '<', now())->chunkById(100, function ($expired) use (&$boards, &$people): void {
            foreach ($expired as $board) {
                $displayHash = $board->display_token_hash;
                $cacheKey = "fa.board.{$board->id}.v";

                DB::transaction(function () use ($board, &$people): void {
                    $people += Participant::where('board_id', $board->id)->delete();
                    $board->delete();
                });

                Cache::forget($cacheKey);
                Cache::forget('fa.disp.'.$displayHash);
                $boards++;
            }
        });

        $files = $this->purgeTemporaryPdfs();

        $this->info("Purge terminée : $boards tableau(x), $people participant(s), $files fichier(s) temporaire(s).");

        return self::SUCCESS;
    }

    /** PDF temporaires éventuels : tout fichier plus vieux que le plafond de vie dans storage/app/fileattente/tmp. */
    private function purgeTemporaryPdfs(): int
    {
        $dir = storage_path('app/fileattente/tmp');
        if (! is_dir($dir)) {
            return 0;
        }
        $limit = time() - ((int) config('file-attente.purge_hours', 24)) * 3600;
        $count = 0;
        foreach (glob($dir.'/*.pdf') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $limit && @unlink($file)) {
                $count++;
            }
        }

        return $count;
    }
}
