<?php

declare(strict_types=1);

namespace Modules\AI\Console;

use Illuminate\Console\Command;
use Modules\AI\Services\YouTubeService;

class YouTubeSummarizeCommand extends Command
{
    protected $signature = 'youtube:summarize {url} {--lang=fr}';

    protected $description = 'Extraire et résumer une vidéo YouTube';

    public function handle(YouTubeService $service): int
    {
        $url = $this->argument('url');
        $lang = $this->option('lang');

        $this->info("Extraction du transcript pour : {$url}");

        $result = $service->extractTranscript($url, $lang);

        if (! $result) {
            $this->error('Impossible d\'extraire la transcription.');

            return 1;
        }

        $this->info("Transcript extrait : {$result['video_id']} — ".strlen($result['transcript']).' caractères, '.count($result['segments']).' segments');

        $this->info('Génération du résumé via DeepSeek...');
        $summary = $service->summarize($result['transcript'], $result['video_id']);

        if (! $summary) {
            $this->error('Impossible de générer le résumé.');

            return 1;
        }

        $this->newLine();
        $this->line($summary);

        return 0;
    }
}
