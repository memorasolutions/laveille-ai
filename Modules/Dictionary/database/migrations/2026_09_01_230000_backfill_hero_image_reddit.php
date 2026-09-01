<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Backfill de hero_image pour le terme "Reddit" (migration d'origine 2026_09_01_220000_add_reddit_term,
 * livrée sans image faute de contexte de navigation Gemini disponible au moment de la rédaction -
 * sept processus navigateur actifs, décision correcte de l'agent d'alors). Image générée via
 * /nanobanana (compte Gemini Workspace stephane@memora.ca, Playwright, voie Node secours - un seul
 * contexte de navigation pour cette seule image), traitée 1200x669 (magick + cwebp), inspectée
 * visuellement (aucun texte, aucun logo, aucune mascotte Reddit/Snoo, aucune personne) et déposée
 * dans public/images/glossaire/ AVANT cette migration, suivie par git (reddit.jpg + reddit.webp).
 * Ne fait qu'UPDATE hero_image, ne recrée ni ne modifie aucune autre colonne.
 * RÉVERSIBLE : down() remet hero_image à null (valeur confirmée avant cette migration).
 */
return new class extends Migration
{
    private string $slug = 'reddit';

    public function up(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $term = Term::where('slug->fr_CA', $this->slug)->first();
        if (! $term) {
            echo "[glossaire] {$this->slug} absent, skip\n";

            return;
        }

        $term->hero_image = 'images/glossaire/'.$this->slug.'.webp';
        $term->save();
        echo "[glossaire] {$this->slug} : hero_image mis à jour\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $term = Term::where('slug->fr_CA', $this->slug)->first();
        if ($term) {
            $term->hero_image = null;
            $term->save();
        }
    }
};
