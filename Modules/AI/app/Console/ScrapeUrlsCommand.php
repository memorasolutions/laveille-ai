<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Console;

use Illuminate\Console\Command;
use Modules\AI\Models\KnowledgeUrl;
use Modules\AI\Services\WebScraperService;

class ScrapeUrlsCommand extends Command
{
    protected $signature = 'ai:scrape-urls
                            {--url= : Scrape a specific URL ID}
                            {--all : Scrape all active URLs needing scraping}';

    protected $description = 'Scrape les URLs externes pour la base de connaissances IA';

    public function handle(WebScraperService $scraperService): int
    {
        if ($this->option('url')) {
            return $this->scrapeOne($scraperService, (int) $this->option('url'));
        }

        if ($this->option('all')) {
            return $this->scrapeAll($scraperService);
        }

        $this->info('Usage :');
        $this->line('  php artisan ai:scrape-urls --url=ID    Scraper une URL spécifique');
        $this->line('  php artisan ai:scrape-urls --all       Scraper toutes les URLs actives');

        return self::SUCCESS;
    }

    private function scrapeOne(WebScraperService $scraperService, int $urlId): int
    {
        try {
            $knowledgeUrl = KnowledgeUrl::findOrFail($urlId);
        } catch (\Exception) {
            $this->error("URL ID {$urlId} introuvable.");

            return self::FAILURE;
        }

        $this->info("Scraping : {$knowledgeUrl->label} ({$knowledgeUrl->url})");

        $pages = $scraperService->scrapeAndIndex($knowledgeUrl);
        $knowledgeUrl->refresh();

        $this->line("  Statut : {$knowledgeUrl->scrape_status}");
        $this->line("  Pages indexées : {$pages}");

        if ($knowledgeUrl->scrape_error) {
            $this->error("  Erreur : {$knowledgeUrl->scrape_error}");
        }

        return self::SUCCESS;
    }

    private function scrapeAll(WebScraperService $scraperService): int
    {
        $urls = KnowledgeUrl::needsScraping()->get();

        if ($urls->isEmpty()) {
            $this->info('Aucune URL à scraper pour le moment.');

            return self::SUCCESS;
        }

        $this->info("{$urls->count()} URL(s) à scraper.");

        $totalPages = 0;
        $completed = 0;
        $failed = 0;
        $blocked = 0;

        foreach ($urls as $knowledgeUrl) {
            $this->line("  {$knowledgeUrl->label}...");

            $pages = $scraperService->scrapeAndIndex($knowledgeUrl);
            $knowledgeUrl->refresh();

            $totalPages += $pages;

            match ($knowledgeUrl->scrape_status) {
                'completed' => $completed++,
                'robots_blocked' => $blocked++,
                default => $failed++,
            };

            $this->line("    → {$knowledgeUrl->scrape_status} ({$pages} pages)");
        }

        $this->newLine();
        $this->info('Résumé :');
        $this->line("  URLs traitées : {$urls->count()}");
        $this->line("  Pages indexées : {$totalPages}");
        $this->line("  Complétées : {$completed}");
        $this->line("  Échouées : {$failed}");
        $this->line("  Bloquées robots.txt : {$blocked}");

        return self::SUCCESS;
    }
}
