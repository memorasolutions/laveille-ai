<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Backfill de hero_image pour 13 termes du glossaire, publiés sans image lors de leur insertion
 * initiale (batch #681 du 2026-06-24 environ : benchmark/miniF2F/Lean 4/Apache 2.0/Leanstral/
 * Putnam/FATE-H/FATE-X/Thariq Shihipar/Fable 5/blindspot pass/unknown unknowns, + 2 termes
 * antérieurs javascript/interface-pam).
 *
 * Détecté le 2026-07-06 via comparaison du sitemap public (446 URLs /glossaire/*) contre le
 * listing réel de public/images/glossaire/ en production : ces 13 slugs n'avaient AUCUNE image
 * (ni jpg ni webp), en contradiction avec la règle absolue "chaque terme doit avoir son image
 * personnalisée" (skill /glossaire, décision utilisateur 2026-07-06). Images générées via
 * /nanobanana (compte Gemini Workspace stephane@memora.ca) et déposées dans
 * public/images/glossaire/.
 *
 * Cette migration ne fait qu'UPDATE le champ hero_image (les termes existent déjà) - elle ne
 * recrée aucun terme. RÉVERSIBLE : down() remet hero_image à null pour ces 13 slugs.
 */
return new class extends Migration
{
    private array $slugs = [
        'applescript',
        'blindspot-pass',
        'fable-5',
        'fate-h-fate-x',
        'interface-pam',
        'javascript',
        'lean-4',
        'leanstral',
        'licence-apache-2-0',
        'minif2f',
        'putnambench',
        'thariq-shihipar',
        'unknown-unknowns',
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
