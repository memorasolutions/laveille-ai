<?php

declare(strict_types=1);

namespace Modules\SEO\Console;

use Illuminate\Console\Command;
use Modules\SEO\Http\Controllers\SitemapController;
use Modules\SEO\Services\IndexNowService;
use SimpleXMLElement;

class IndexNowSubmitCommand extends Command
{
    protected $signature = 'seo:indexnow-submit {--limit=0 : Limiter le nombre d\'URLs}';

    protected $description = 'Soumet toutes les URLs du sitemap a IndexNow (batch de 100)';

    public function handle(): int
    {
        if (! IndexNowService::isEnabled()) {
            $this->warn('IndexNow desactive (INDEXNOW_ENABLED=false).');

            return self::FAILURE;
        }

        $this->info('Generation du sitemap...');
        $xml = (new SitemapController())->index()->getContent();

        $sitemap = new SimpleXMLElement($xml);
        $urls = [];
        foreach ($sitemap->url as $url) {
            $urls[] = (string) $url->loc;
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $urls = array_slice($urls, 0, $limit);
        }

        $total = count($urls);
        $this->info("{$total} URLs trouvees.");

        $success = 0;
        $failed = 0;

        foreach (array_chunk($urls, 100) as $i => $batch) {
            $result = IndexNowService::submitBatch($batch);
            if ($result) {
                $success += count($batch);
                $this->line("<fg=green>Batch " . ($i + 1) . " : " . count($batch) . " URLs soumises</>");
            } else {
                $failed += count($batch);
                $this->line("<fg=red>Batch " . ($i + 1) . " : echec (" . count($batch) . " URLs)</>");
            }
        }

        $this->info("Termine : {$success} soumises, {$failed} echouees sur {$total}.");

        return self::SUCCESS;
    }
}
