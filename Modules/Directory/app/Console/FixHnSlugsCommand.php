<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Directory\Models\Tool;

class FixHnSlugsCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:fix-hn-slugs {--dry-run : N\'applique pas les changements} {--limit=50} {--force : Ignore kill switch}';

    protected $description = "Nettoie les outils dont le nom ou slug commence par 'Show HN:' ou 'show-hn-' (préfixes Hacker News non désirés)";

    public function handle(): int
    {
        if (! $this->option('force') && $this->shouldSkipForKillSwitch('cron.fix-hn')) {
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $tools = Tool::query()
            ->where(function ($query) {
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr_CA')) LIKE ?", ['Show HN:%'])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.fr_CA')) LIKE ?", ['show-hn-%']);
            })
            ->limit($limit)
            ->get();

        $processedCount = 0;

        foreach ($tools as $tool) {
            $processedCount++;
            $originalNameFr = $tool->getTranslation('name', 'fr_CA');
            $currentSlugFr = $tool->getTranslation('slug', 'fr_CA');

            $newName = \Modules\Directory\Services\ToolNameCleanerService::clean((string) $originalNameFr);

            if ($newName === $originalNameFr && ! \Modules\Directory\Services\ToolNameCleanerService::isHnTitle((string) $originalNameFr)) {
                $this->warn("  #{$tool->id} skip (rien à nettoyer): '{$originalNameFr}'");
                continue;
            }
            $newSlug = Str::slug($newName);
            $originalSlug = $newSlug;
            $counter = 1;

            while (Tool::where('id', '!=', $tool->id)
                ->where('slug->fr_CA', $newSlug)
                ->exists()) {
                $counter++;
                $newSlug = $originalSlug . '-' . $counter;
            }

            $this->info("  #{$tool->id} '{$originalNameFr}' → '{$newName}' | slug '{$currentSlugFr}' → '{$newSlug}'");

            if ($dryRun) {
                continue;
            }

            foreach (['fr_CA', 'fr', 'en'] as $locale) {
                $tool->setTranslation('name', $locale, $newName);
                $tool->setTranslation('slug', $locale, $newSlug);
            }
            $tool->save();

            $sourcePath = '/annuaire/' . $currentSlugFr;
            $targetPath = '/annuaire/' . $newSlug;

            DB::table('url_redirects')->updateOrInsert(
                ['from_url' => $sourcePath],
                [
                    'to_url' => $targetPath,
                    'status_code' => 301,
                    'is_active' => true,
                    'note' => 'Auto-généré par tools:fix-hn-slugs',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $mode = $dryRun ? 'dry-run' : 'appliqué';
        $this->info("{$processedCount} outils traités ({$mode}).");

        return self::SUCCESS;
    }
}
