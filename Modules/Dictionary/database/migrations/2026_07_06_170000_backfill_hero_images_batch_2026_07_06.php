<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;

/**
 * Backfill de hero_image pour les 7 termes ajoutés le 2026-07-06 sans image au moment de leur
 * insertion initiale (Data Privacy Framework, Noyb, IWF, NCA, Kernels, OmniDocBench, R-SWA).
 *
 * Contexte : le skill /glossaire appliquait jusqu'ici une "décision éditoriale" permettant de
 * sauter l'image pour les termes jugés trop abstraits. L'utilisateur a explicitement révoqué
 * cette clause le 2026-07-06 : CHAQUE terme doit désormais avoir une image personnalisée
 * (skill /glossaire mis à jour en conséquence). Les 7 images ont été générées rétroactivement
 * via /nanobanana (compte Gemini Workspace) et déposées dans public/images/glossaire/.
 *
 * Cette migration ne fait qu'UPDATE le champ hero_image (les lignes existent déjà, insérées par
 * les migrations 2026_07_06_090000 à 2026_07_06_150000) - elle ne recrée aucun terme.
 * RÉVERSIBLE : down() remet hero_image à null pour ces 7 slugs.
 */
return new class extends Migration
{
    private array $slugs = [
        'data-privacy-framework',
        'noyb',
        'iwf',
        'nca',
        'kernels',
        'omnidocbench',
        'r-swa',
    ];

    public function up(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->slugs as $slug) {
            $term = Term::where('slug->fr_CA', $slug)->first();
            if (! $term) {
                echo "[glossaire] {$slug} absent, skip\n";

                continue;
            }

            $term->hero_image = 'images/glossaire/'.$slug.'.webp';
            $term->save();
            echo "[glossaire] {$slug} : hero_image mis à jour\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->slugs as $slug) {
            $term = Term::where('slug->fr_CA', $slug)->first();
            if ($term) {
                $term->hero_image = null;
                $term->save();
            }
        }
    }
};
