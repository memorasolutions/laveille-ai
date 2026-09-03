<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\ToolResource;

/**
 * Porte OFFICIELLE de modération des tutoriels par identifiant vidéo - créée pour le mandat #2201
 * (2026-09-03), après mesure : 20 tutoriels affichés en production n'avaient aucun lien avec leur
 * outil (homonymie de nom commun : « Monologue » l'outil de dictée récoltait du théâtre et le
 * synthétiseur Korg Monologue, « Motion » récoltait du motion design, « Make » un dessin animé).
 *
 * DÉSAPPROUVE (is_approved=false), ne supprime JAMAIS : une ressource désapprouvée disparaît du
 * site public (scope approved()) tout en restant en base, ce qui la VACCINE contre le re-scan -
 * EnrichTutorialsCommand détecte ses doublons par video_id seul, sans regarder is_approved, donc
 * elle ne sera jamais recréée. La suppression ferait exactement l'inverse : le prochain scan la
 * ré-ajouterait. --restore rétablit une désapprobation erronée (réversibilité complète).
 */
class ModerateTutorialsCommand extends Command
{
    protected $signature = 'tools:moderate-tutorials
        {--videos= : Identifiants vidéo YouTube à modérer, séparés par des virgules}
        {--restore : Rétablir (is_approved=true) au lieu de désapprouver}
        {--dry-run : Afficher ce qui serait modifié sans rien écrire}';

    protected $description = 'Désapprouve (ou rétablit) des tutoriels par identifiant vidéo YouTube - jamais de suppression';

    public function handle(): int
    {
        if (! class_exists(ToolResource::class)) {
            $this->error('Le module Directory est désactivé ou introuvable.');

            return self::FAILURE;
        }

        $videoIds = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('videos')))));

        if (empty($videoIds)) {
            $this->error('Aucun identifiant vidéo fourni (--videos=id1,id2,...).');

            return self::FAILURE;
        }

        $restore = (bool) $this->option('restore');
        $dryRun = (bool) $this->option('dry-run');
        $targetState = $restore;

        $resources = ToolResource::with('tool')->whereIn('video_id', $videoIds)->get();

        $foundIds = $resources->pluck('video_id')->all();
        foreach (array_diff($videoIds, $foundIds) as $missing) {
            $this->warn("  Introuvable en base : {$missing}");
        }

        $changed = 0;
        foreach ($resources as $resource) {
            $toolName = $resource->tool?->getTranslation('name', 'fr_CA') ?? $resource->tool?->name ?? '?';
            $label = "{$resource->video_id} « {$resource->title} » (outil : {$toolName})";

            if ((bool) $resource->is_approved === $targetState) {
                $this->line('  Déjà dans l\'état voulu : '.$label);

                continue;
            }

            if ($dryRun) {
                $this->info(($restore ? '  [dry-run] Rétablirait : ' : '  [dry-run] Désapprouverait : ').$label);
                $changed++;

                continue;
            }

            $resource->update(['is_approved' => $targetState]);
            Log::info('[ModerateTutorials] '.($restore ? 'Rétabli' : 'Désapprouvé'), [
                'video_id' => $resource->video_id,
                'title' => $resource->title,
                'tool_id' => $resource->directory_tool_id,
            ]);
            $this->info(($restore ? '  Rétabli : ' : '  Désapprouvé : ').$label);
            $changed++;
        }

        $verb = $restore ? 'rétabli(s)' : 'désapprouvé(s)';
        $this->newLine();
        $this->info("=== BILAN : {$changed} tutoriel(s) {$verb}".($dryRun ? ' (dry-run, rien écrit)' : '').', '.count($videoIds).' identifiant(s) demandé(s) ===');

        return self::SUCCESS;
    }
}
