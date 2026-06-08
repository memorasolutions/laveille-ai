<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

return new class extends Migration
{
    /**
     * Correction des liens internes cassés du glossaire (broader_slugs / narrower_slugs) :
     *  - refs vers doublons dépubliés remappées vers le canonique (differential-privacy → confidentialite-differentielle) ;
     *  - refs vers slugs inexistants retirées (protection-vie-privee, hash-sha-256, hallucination-ia).
     * Réversible (down inverse chaque opération), aucun DELETE de terme.
     */
    public function up(): void
    {
        if (! class_exists(Term::class)) {
            echo "[liens] Term model not found, skipping.\n";
            return;
        }

        foreach ($this->ops() as $op) {
            $t = Term::where('slug->fr_CA', $op['slug'])->first();
            if (! $t) {
                continue;
            }

            $arr = $t->{$op['field']} ?? [];

            if ($op['type'] === 'remove') {
                $arr = array_values(array_diff($arr, [$op['value']]));
            } elseif ($op['type'] === 'replace') {
                $arr = array_values(array_map(fn ($v) => $v === $op['from'] ? $op['to'] : $v, $arr));
            }

            $t->{$op['field']} = $arr;
            $t->save();
            echo "[liens] {$op['slug']}.{$op['field']} corrigé\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            echo "[liens] Term model not found, skipping rollback.\n";
            return;
        }

        foreach ($this->ops() as $op) {
            $t = Term::where('slug->fr_CA', $op['slug'])->first();
            if (! $t) {
                continue;
            }

            $arr = $t->{$op['field']} ?? [];

            if ($op['type'] === 'remove') {
                if (! in_array($op['value'], $arr, true)) {
                    $arr[] = $op['value'];
                }
                $arr = array_values($arr);
            } elseif ($op['type'] === 'replace') {
                $arr = array_values(array_map(fn ($v) => $v === $op['to'] ? $op['from'] : $v, $arr));
            }

            $t->{$op['field']} = $arr;
            $t->save();
        }
    }

    private function ops(): array
    {
        return [
            ['type' => 'remove', 'slug' => 'tokenisation', 'field' => 'broader_slugs', 'value' => 'protection-vie-privee'],
            ['type' => 'remove', 'slug' => 'anonymisation', 'field' => 'broader_slugs', 'value' => 'protection-vie-privee'],
            ['type' => 'replace', 'slug' => 'anonymisation', 'field' => 'narrower_slugs', 'from' => 'differential-privacy', 'to' => 'confidentialite-differentielle'],
            ['type' => 'remove', 'slug' => 'pseudonymisation', 'field' => 'broader_slugs', 'value' => 'protection-vie-privee'],
            ['type' => 'remove', 'slug' => 'pseudonymisation', 'field' => 'narrower_slugs', 'value' => 'hash-sha-256'],
            ['type' => 'remove', 'slug' => 'k-anonymity', 'field' => 'broader_slugs', 'value' => 'protection-vie-privee'],
            ['type' => 'replace', 'slug' => 'k-anonymity', 'field' => 'narrower_slugs', 'from' => 'differential-privacy', 'to' => 'confidentialite-differentielle'],
            ['type' => 'remove', 'slug' => 'hallucination', 'field' => 'narrower_slugs', 'value' => 'hallucination-ia'],
        ];
    }
};
