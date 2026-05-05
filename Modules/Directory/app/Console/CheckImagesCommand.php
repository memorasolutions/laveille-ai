<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;

class CheckImagesCommand extends Command
{
    protected $signature = 'tools:check-images
                            {--auto-fix : Tente de regénérer les screenshots manquants via directory:capture-screenshots}
                            {--limit=0 : Limiter aux N premiers outils (0 = tous)}';

    protected $description = 'Audit santé des images screenshot annuaire (HEAD HTTP) + log/régénère les 404';

    public function handle(): int
    {
        $this->info('🔍 Audit images annuaire — démarrage');

        $query = Tool::whereNotNull('screenshot')->where('screenshot', '!=', '');
        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }
        $tools = $query->get(['id', 'slug', 'name', 'screenshot', 'updated_at']);

        $total = $tools->count();
        $broken = [];
        $checked = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($tools as $tool) {
            $checked++;
            $url = str_starts_with($tool->screenshot, 'http')
                ? $tool->screenshot
                : url($tool->screenshot);

            $isLocal = ! str_starts_with($tool->screenshot, 'http');
            $isOk = false;

            try {
                if ($isLocal) {
                    $localPath = public_path(ltrim($tool->screenshot, '/'));
                    $isOk = is_file($localPath) && filesize($localPath) > 100;
                } else {
                    $resp = Http::timeout(8)->withOptions(['allow_redirects' => true])->head($url);
                    $isOk = $resp->successful();
                }
            } catch (\Throwable $e) {
                $isOk = false;
            }

            if (! $isOk) {
                $broken[] = ['slug' => $tool->slug, 'name' => $tool->name, 'url' => $url];
            }

            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $brokenCount = count($broken);
        $this->table(
            ['Total vérifié', 'Cassé', 'OK'],
            [[$checked, $brokenCount, $checked - $brokenCount]],
        );

        if ($brokenCount === 0) {
            $this->info('✅ Toutes les images sont accessibles.');

            return self::SUCCESS;
        }

        Log::channel('daily')->warning('[tools:check-images] '.$brokenCount.'/'.$checked.' images cassées', [
            'broken_slugs' => array_column($broken, 'slug'),
        ]);

        $this->warn('⚠️ '.$brokenCount.' image(s) cassée(s) :');
        foreach (array_slice($broken, 0, 20) as $b) {
            $this->line('  • '.$b['slug'].' ('.$b['name'].') — '.$b['url']);
        }
        if ($brokenCount > 20) {
            $this->line('  … +'.($brokenCount - 20).' autres');
        }

        if ($this->option('auto-fix')) {
            $this->info('🔧 Auto-fix activé : régénération via directory:capture-screenshots');
            foreach ($broken as $b) {
                $this->call('directory:capture-screenshots', ['--slug' => $b['slug'], '--force' => true]);
            }
        } else {
            $this->line('💡 Relancer avec --auto-fix pour régénérer.');
        }

        return self::SUCCESS;
    }
}
