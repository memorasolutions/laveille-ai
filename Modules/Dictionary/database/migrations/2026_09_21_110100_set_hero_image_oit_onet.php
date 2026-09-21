<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif du 2026-09-21 : pose hero_image sur deux fiches deja en production, OIT et O*NET,
 * ajoutees par 2026_09_21_090000_add_oit_onet_terms.php avec has_image=false faute d image
 * disponible a ce moment-la. Les images existent maintenant (public/images/glossaire/oit.webp,
 * oit.jpg, onet.webp, onet.jpg), verifiees avant l ecriture de cette migration.
 *
 * Cette migration NE TOUCHE PAS 2026_09_21_090000_add_oit_onet_terms.php, deja jouee en
 * production : la corriger ne rejouerait rien et mentirait sur l historique. C est une migration
 * NOUVELLE, distincte, qui pose UNIQUEMENT hero_image sur les deux slugs concernes - aucun autre
 * champ n est touche.
 *
 * Recherche du Term par slug avec le meme repli que resolveCategoryId du gabarit
 * (slug->fr_CA puis slug->fr). Un slug introuvable n est PAS cree : il est signale par echo, et
 * la fiche correspondante est simplement ignoree.
 *
 * Reversible : down() remet hero_image a null sur ces deux slugs seulement.
 */
return new class extends Migration
{
    private function findTermBySlug(string $slug): ?Term
    {
        return Term::where('slug->fr_CA', $slug)->first()
            ?? Term::where('slug->fr', $slug)->first();
    }

    /**
     * @return array<string, string>
     */
    private function heroImages(): array
    {
        return [
            'oit' => 'images/glossaire/oit.webp',
            'onet' => 'images/glossaire/onet.webp',
        ];
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! class_exists(Term::class)) {
            echo "[glossaire] modele Term absent, ignore\n";

            return;
        }

        foreach ($this->heroImages() as $slug => $heroImage) {
            $term = $this->findTermBySlug($slug);

            if (! $term) {
                echo "[glossaire] slug introuvable, ignore : {$slug}\n";

                continue;
            }

            $term->hero_image = $heroImage;
            $term->save();

            echo "[glossaire] hero_image pose : {$slug}\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach (array_keys($this->heroImages()) as $slug) {
            $term = $this->findTermBySlug($slug);

            if (! $term) {
                echo "[glossaire] slug introuvable, ignore : {$slug}\n";

                continue;
            }

            $term->hero_image = null;
            $term->save();

            echo "[glossaire] hero_image retire : {$slug}\n";
        }
    }
};
