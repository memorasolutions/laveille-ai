<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

return new class extends Migration
{
    /**
     * Consolidation finale du glossaire :
     *  - Fusion du doublon MAL NOMMÉ 'spoiler' → canonique 'data-poisoning' (le vrai « Spoiler » est une faille CPU ;
     *    cette entrée admin décrivait en réalité l'empoisonnement de données). data-poisoning reçoit la catégorie 4
     *    (Sécurité et éthique) et l'alias « empoisonnement de données » ; 'spoiler' est dépublié (301 côté routes).
     *  - Correction du lien taxonomique inversé : embeddings est un TYPE de vectorisation
     *    (on retire vectorisation.broader=embeddings et on pose embeddings.broader=vectorisation).
     * Les paires hiérarchiques (embeddings/vectorisation, ia-multimodale/modele-multimodal, llm/modele-de-langage)
     * sont volontairement CONSERVÉES (concepts distincts liés, pas des synonymes). Réversible, aucun DELETE.
     */
    public function up(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $dp = Term::where('slug->fr_CA', 'data-poisoning')->first();
        if ($dp) {
            if ($dp->dictionary_category_id === null) {
                $dp->dictionary_category_id = 4;
            }
            $al = $dp->aliases ?? [];
            if (! in_array('empoisonnement de données', $al, true)) {
                $al[] = 'empoisonnement de données';
            }
            $dp->aliases = array_values($al);
            $dp->save();
        }

        $sp = Term::where('slug->fr_CA', 'spoiler')->first();
        if ($sp && $sp->is_published) {
            $sp->is_published = false;
            $sp->save();
            echo "[dedup] spoiler dépublié\n";
        }

        $v = Term::where('slug->fr_CA', 'vectorisation')->first();
        if ($v) {
            $v->broader_slugs = array_values(array_diff($v->broader_slugs ?? [], ['embeddings']));
            $v->save();
        }

        $e = Term::where('slug->fr_CA', 'embeddings')->first();
        if ($e) {
            $b = $e->broader_slugs ?? [];
            if (! in_array('vectorisation', $b, true)) {
                $b[] = 'vectorisation';
            }
            $e->broader_slugs = array_values($b);
            $e->save();
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        $dp = Term::where('slug->fr_CA', 'data-poisoning')->first();
        if ($dp) {
            if ($dp->dictionary_category_id === 4) {
                $dp->dictionary_category_id = null;
            }
            $dp->aliases = array_values(array_diff($dp->aliases ?? [], ['empoisonnement de données']));
            $dp->save();
        }

        $sp = Term::where('slug->fr_CA', 'spoiler')->first();
        if ($sp && ! $sp->is_published) {
            $sp->is_published = true;
            $sp->save();
        }

        $v = Term::where('slug->fr_CA', 'vectorisation')->first();
        if ($v) {
            $b = $v->broader_slugs ?? [];
            if (! in_array('embeddings', $b, true)) {
                $b[] = 'embeddings';
            }
            $v->broader_slugs = array_values($b);
            $v->save();
        }

        $e = Term::where('slug->fr_CA', 'embeddings')->first();
        if ($e) {
            $e->broader_slugs = array_values(array_diff($e->broader_slugs ?? [], ['vectorisation']));
            $e->save();
        }
    }
};
