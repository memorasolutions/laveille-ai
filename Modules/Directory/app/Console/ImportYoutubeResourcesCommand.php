<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;

class ImportYoutubeResourcesCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:import-youtube-resources
        {--file= : Chemin JSON}
        {--force : Ignore kill switch}
        {--dry-run : Simule l\'import sans écrire en base}';

    protected $description = 'Importe des tutoriels YouTube (type=youtube, language=fr) depuis un JSON file. Idempotent (skip doublons video_id par outil).';

    public function handle(): int
    {
        if (! $this->option('force') && $this->shouldSkipForKillSwitch('cron.import-youtube')) {
            return self::SUCCESS;
        }

        $filePath = (string) $this->option('file');

        if ($filePath === '' || ! File::exists($filePath)) {
            $this->error("Fichier JSON introuvable : {$filePath}");

            return self::FAILURE;
        }

        $json = File::get($filePath);
        $config = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($config)) {
            $this->error('JSON invalide : ' . json_last_error_msg());

            return self::FAILURE;
        }

        $data = $config['data'] ?? [];
        $language = $config['language'] ?? 'fr';
        $isApproved = $config['is_approved'] ?? true;
        $minViews = (int) ($config['min_views'] ?? 1500);
        $minDuration = (int) ($config['min_duration'] ?? 60);
        $isDryRun = (bool) $this->option('dry-run');

        $imported = 0;
        $skippedDup = 0;
        $skippedInvalid = 0;
        $toolsScanned = 0;

        if ($isDryRun) {
            $this->warn('Mode dry-run activé : aucune écriture en base.');
        }

        foreach ($data as $toolId => $videos) {
            $toolId = (int) $toolId;
            $tool = Tool::find($toolId);

            if ($tool === null) {
                $this->warn("Tool #{$toolId} introuvable, ignoré.");
                continue;
            }

            if (! is_array($videos)) {
                $this->warn("Tool #{$toolId} : données vidéos invalides, ignoré.");
                continue;
            }

            foreach ($videos as $video) {
                $vid = (string) ($video['video_id'] ?? '');
                $title = (string) ($video['title'] ?? '');
                $channel = (string) ($video['channel'] ?? '');
                $duration = (int) ($video['duration'] ?? 0);
                $views = (int) ($video['views'] ?? 0);

                if ($vid === '' || $views < $minViews || $duration < $minDuration) {
                    $this->warn("INVALID #{$toolId} {$vid} (views={$views} duration={$duration})");
                    $skippedInvalid++;
                    continue;
                }

                $exists = ToolResource::where('directory_tool_id', $toolId)
                    ->where('video_id', $vid)
                    ->exists();

                if ($exists) {
                    $skippedDup++;
                    continue;
                }

                if (! $isDryRun) {
                    ToolResource::create([
                        'directory_tool_id' => $toolId,
                        'url' => 'https://www.youtube.com/watch?v=' . $vid,
                        'title' => $title,
                        'type' => 'youtube',
                        'language' => $language,
                        'level' => ToolResource::detectLevel($title),
                        'thumbnail' => 'https://i.ytimg.com/vi/' . $vid . '/hqdefault.jpg',
                        'video_id' => $vid,
                        'duration_seconds' => $duration,
                        'channel_name' => $channel,
                        'channel_url' => null,
                        'is_approved' => $isApproved,
                    ]);
                }

                $imported++;
            }

            if (! $isDryRun) {
                $tool->tutorials_last_scanned_at = now();
                $tool->save();
            }

            $toolsScanned++;
        }

        $prefix = $isDryRun ? '[DRY-RUN] ' : '';
        $this->info("{$prefix}=== BILAN : {$imported} imported, {$skippedDup} skipped_dup, {$skippedInvalid} skipped_invalid, {$toolsScanned} tools_scanned ===");

        return self::SUCCESS;
    }
}
