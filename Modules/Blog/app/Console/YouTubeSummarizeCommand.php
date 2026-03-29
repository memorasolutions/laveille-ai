<?php

declare(strict_types=1);

namespace Modules\Blog\Console;

use Illuminate\Console\Command;

class YouTubeSummarizeCommand extends Command
{
    protected $signature = 'youtube:summarize {url} {--lang=fr}';

    protected $description = 'Extraire et résumer une vidéo YouTube';

    public function handle(): int
    {
        if (! class_exists(\Modules\AI\Services\YouTubeService::class)) {
            $this->error('Le module AI est requis pour cette commande.');

            return 1;
        }

        $service = app(\Modules\AI\Services\YouTubeService::class);
        $url = $this->argument('url');
        $lang = $this->option('lang');

        $this->info("Extraction du transcript : {$url}");

        $result = $service->extractTranscript($url, $lang);

        if (! $result) {
            $this->error('Impossible d\'extraire la transcription.');

            return 1;
        }

        $this->info("Transcript : {$result['video_id']} — ".strlen($result['transcript']).' caractères, '.count($result['segments']).' segments');
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
