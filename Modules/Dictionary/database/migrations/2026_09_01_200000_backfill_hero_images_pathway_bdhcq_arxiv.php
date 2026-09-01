<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Backfill de hero_image pour les 3 termes ajoutés le 2026-09-01 (Pathway, BDH-CQ, arXiv), livrés
 * sans image par leurs migrations d'origine (2026_09_01_100000, 2026_09_01_180000, 2026_09_01_140000)
 * faute de contexte de navigation Gemini partageable entre agents simultanés (mémoire projet
 * session-partagee-un-contexte-par-lot-2026-09-01). Images générées via /nanobanana (compte Gemini
 * Workspace stephane@memora.ca, Playwright, UN SEUL contexte pour les trois), traitées 1200x669
 * (magick + cwebp) et déposées dans public/images/glossaire/ AVANT cette migration, suivies par git.
 * Ne fait qu'UPDATE hero_image, ne recrée ni ne modifie aucune autre colonne.
 * RÉVERSIBLE : down() remet hero_image à null pour ces 3 slugs.
 */
return new class extends Migration
{
    private array $slugs = [
        'pathway',
        'bdh-cq',
        'arxiv',
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
