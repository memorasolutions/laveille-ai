<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Backfill de hero_image pour les 6 termes ajoutés le 2026-07-10 (MTIA, Broadcom, TSMC, AMD,
 * PyTorch, DMA), images générées via /nanobanana (compte Gemini Workspace) et déposées dans
 * public/images/glossaire/ après l'insertion des lignes par les migrations 2026_07_10_090000 à
 * 2026_07_10_140000. Cette migration ne fait qu'UPDATE hero_image, elle ne recrée aucun terme.
 * RÉVERSIBLE : down() remet hero_image à null pour ces 6 slugs.
 */
return new class extends Migration
{
    private array $slugs = [
        'mtia',
        'broadcom',
        'tsmc',
        'amd',
        'pytorch',
        'dma',
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
