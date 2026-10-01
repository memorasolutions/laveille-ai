<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Migrations\Migration;
use Modules\Ads\Models\AdPlacement;

/**
 * Active les emplacements AdSense de laveille.ai avec les identifiants d'unité réels
 * (créés le 2026-10-01 dans le compte pub-2358625447182467).
 *
 * Idempotente : firstOrNew par clé. Si une ligne existe déjà avec une pub directe maison
 * (ex. encart livre sur article-inline), son ad_code est PRÉSERVÉ -> l'emplacement alterne
 * alors chaque jour entre AdSense et la pub directe. Un emplacement neuf est créé en AdSense pur.
 *
 * Carte complète et dimensions : docs/specs/2026-10-01-ads-adsense-placement-design.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->placements() as $p) {
            $ad = AdPlacement::firstOrNew(['key' => $p['key']]);

            if (! $ad->exists) {
                $ad->name = $p['name'];
                $ad->description = $p['description'];
                $ad->ad_code = null; // AdSense pur : aucune pub directe maison.
            }
            // Si la ligne existait déjà, on ne touche NI à son nom NI à son ad_code :
            // une pub directe maison déjà en place est conservée (alternance automatique).

            $ad->ad_slot = $p['slot'];
            $ad->ad_format = $p['format'];
            $ad->min_height = $p['min_height'];
            $ad->lazy = true;
            $ad->is_external = $p['external'];
            $ad->is_active = true;
            $ad->save();
        }
    }

    public function down(): void
    {
        // On ne supprime aucune ligne (aucune donnée perdue) : on retire seulement la
        // configuration AdSense. Les colonnes elles-mêmes sont retirées par la migration
        // de schéma qui précède (000001) lors du rollback.
        foreach ($this->placements() as $p) {
            $ad = AdPlacement::where('key', $p['key'])->first();
            if ($ad && $ad->ad_slot === $p['slot']) {
                $ad->ad_slot = null;
                // Un emplacement qui n'avait pas de pub directe redevient inactif;
                // celui qui en a une (alternance) reste actif avec sa pub directe.
                if (empty($ad->ad_code)) {
                    $ad->is_active = false;
                }
                $ad->save();
            }
        }
    }

    /**
     * @return array<int, array{key:string,name:string,description:string,slot:string,format:string,min_height:int,external:bool}>
     */
    private function placements(): array
    {
        return [
            // Zone article : external=false pour qu'une éventuelle pub directe maison
            // (encart livre) garde son label « Publicité » les jours où elle est servie.
            ['key' => 'article-top', 'name' => 'Article - après introduction', 'description' => 'AdSense In-Article, après l\'introduction.', 'slot' => '6448028197', 'format' => 'fluid', 'min_height' => 280, 'external' => false],
            ['key' => 'article-inline', 'name' => 'Article - milieu', 'description' => 'AdSense In-Article au milieu; alterne avec l\'encart livre s\'il est présent.', 'slot' => '9999336950', 'format' => 'fluid', 'min_height' => 280, 'external' => false],
            ['key' => 'article-bottom', 'name' => 'Article - fin', 'description' => 'AdSense display responsive, avant les articles liés.', 'slot' => '4523648998', 'format' => 'auto', 'min_height' => 280, 'external' => false],
            // Zones sans pub directe maison attendue : external=true (AdSense s'auto-étiquette).
            ['key' => 'sidebar-rectangle', 'name' => 'Barre latérale', 'description' => 'AdSense display responsive, barre latérale (collante desktop).', 'slot' => '3210567329', 'format' => 'auto', 'min_height' => 600, 'external' => true],
            ['key' => 'glossary-top', 'name' => 'Glossaire', 'description' => 'AdSense display responsive, après la définition.', 'slot' => '3869042877', 'format' => 'auto', 'min_height' => 280, 'external' => true],
            ['key' => 'directory-tool-top', 'name' => 'Annuaire - fiche outil', 'description' => 'AdSense display responsive, après la description de la fiche.', 'slot' => '7401842201', 'format' => 'auto', 'min_height' => 280, 'external' => true],
            ['key' => 'directory-bottom', 'name' => 'Annuaire - bas de liste', 'description' => 'AdSense display responsive, bas de liste.', 'slot' => '5126284226', 'format' => 'auto', 'min_height' => 280, 'external' => true],
        ];
    }
};
