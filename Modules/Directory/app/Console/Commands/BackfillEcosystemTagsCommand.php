<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Directory\Console\Commands;

use Illuminate\Console\Command;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\EcosystemCountService;
use Modules\Directory\Services\EcosystemResolverService;

class BackfillEcosystemTagsCommand extends Command
{
    protected $signature = 'directory:backfill-ecosystem-tags {--dry-run : Simulation, affiche le résultat sans rien écrire}';

    protected $description = "Détecte et écrit ecosystem_tag sur les outils dont il est vide, via l'URL (jamais d'écrasement d'un tag déjà rempli manuellement).";

    public function handle(EcosystemResolverService $resolver): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Ne touche JAMAIS un ecosystem_tag déjà rempli (manuel ou backfill précédent) —
        // piège d'écrasement silencieux explicitement signalé dans la recherche.
        $tools = Tool::query()->whereNull('ecosystem_tag')->get(['id', 'slug', 'url', 'ecosystem_tag']);

        if ($tools->isEmpty()) {
            $this->info('Aucun outil avec ecosystem_tag vide. Rien à faire.');

            return self::SUCCESS;
        }

        $detected = [];
        $notDetected = [];

        foreach ($tools as $tool) {
            $tag = $resolver->resolve((string) $tool->url);
            $slug = (string) ($tool->getTranslation('slug', 'fr_CA', false) ?: $tool->getTranslation('slug', 'fr', false) ?: $tool->id);

            if ($tag !== null) {
                $detected[] = ['id' => $tool->id, 'slug' => $slug, 'url' => $tool->url, 'tag' => $tag];
            } else {
                $notDetected[] = ['id' => $tool->id, 'slug' => $slug, 'url' => $tool->url];
            }
        }

        if ($dryRun) {
            $this->table(
                ['ID', 'Slug', 'URL', 'Tag détecté'],
                collect($detected)
                    ->map(fn (array $d) => [$d['id'], $d['slug'], $d['url'], $d['tag']])
                    ->concat(
                        collect($notDetected)->map(fn (array $d) => [$d['id'], $d['slug'], $d['url'], 'NON DÉTECTÉ'])
                    )
            );
            $this->info(sprintf(
                '[DRY-RUN] %d détecté(s) / %d non détecté(s) sur %d outil(s) sans ecosystem_tag.',
                count($detected),
                count($notDetected),
                $tools->count()
            ));

            return self::SUCCESS;
        }

        foreach ($detected as $d) {
            Tool::whereKey($d['id'])->update(['ecosystem_tag' => $d['tag']]);
        }

        if ($detected !== []) {
            EcosystemCountService::flushCache();
        }

        $this->info(sprintf('%d outil(s) taggé(s), %d non détecté(s).', count($detected), count($notDetected)));

        if ($notDetected !== []) {
            $this->warn('Outils non détectés (à taguer manuellement au besoin) :');
            foreach ($notDetected as $d) {
                $this->line("  - [{$d['id']}] {$d['slug']} ({$d['url']})");
            }
        }

        return self::SUCCESS;
    }
}
