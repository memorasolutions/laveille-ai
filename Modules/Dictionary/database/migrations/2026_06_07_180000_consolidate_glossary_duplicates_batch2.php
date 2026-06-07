<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

return new class extends Migration
{
    /**
     * Consolidation des doublons admin du glossaire (mêmes concepts sous 2 slugs) :
     *  - differential-privacy  → canonique confidentialite-differentielle
     *  - hallucination-ia      → canonique hallucination
     * Garde l'original (seeder, contenu propre), fusionne les alias uniques du doublon,
     * dépublie le doublon (redirections 301 gérées côté routes). Réversible, AUCUN DELETE.
     */
    public function up(): void
    {
        if (! class_exists(Term::class)) {
            echo "[dedup] Term model not found, skipping.\n";
            return;
        }

        foreach ($this->pairs() as $pair) {
            $c = Term::where('slug->fr_CA', $pair['canonical'])->first();
            if ($c) {
                $c->aliases = array_values(array_unique(array_merge($c->aliases ?? [], $pair['extra'])));
                $c->save();
                echo "[dedup] alias fusionnés: {$pair['canonical']}\n";
            }

            $d = Term::where('slug->fr_CA', $pair['dup'])->first();
            if ($d && $d->is_published) {
                $d->is_published = false;
                $d->save();
                echo "[dedup] dépublié {$pair['dup']} id={$d->id}\n";
            } else {
                echo "[dedup] {$pair['dup']} absent/déjà dépublié\n";
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            echo "[dedup] Term model not found, skipping rollback.\n";
            return;
        }

        foreach ($this->pairs() as $pair) {
            $c = Term::where('slug->fr_CA', $pair['canonical'])->first();
            if ($c) {
                $c->aliases = array_values(array_diff($c->aliases ?? [], $pair['extra']));
                $c->save();
            }

            $d = Term::where('slug->fr_CA', $pair['dup'])->first();
            if ($d) {
                $d->is_published = true;
                $d->save();
            }
        }
    }

    private function pairs(): array
    {
        return [
            [
                'canonical' => 'confidentialite-differentielle',
                'dup' => 'differential-privacy',
                'extra' => ['differential privacy', 'differential-privacy'],
            ],
            [
                'canonical' => 'hallucination',
                'dup' => 'hallucination-ia',
                'extra' => ['hallucination IA', 'hallucination-ia', 'Hallucination LLM'],
            ],
        ];
    }
};
