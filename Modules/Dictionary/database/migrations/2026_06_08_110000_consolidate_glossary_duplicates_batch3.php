<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

return new class extends Migration
{
    /**
     * Consolidation de 8 doublons de glossaire (même concept sous 2 slugs) :
     *   tokens→token, moe→mixture-of-experts, context-window→fenetre-de-contexte,
     *   shadow-ai→ia-fantome, infiltration-de-requete→prompt-injection,
     *   knowledge-distillation→distillation-de-modele, affinage→fine-tuning, edge-ai→ia-embarquee.
     * Le nom + les alias uniques du doublon sont fusionnés dans « Aussi appelé » (Term.aliases) du canonique ;
     * le doublon est dépublié ; les liens broader/narrower vers le doublon sont nettoyés (self-ref retirés,
     * byoai.broader shadow-ai → ia-fantome). Redirections 301 côté routes. RÉVERSIBLE, aucun DELETE.
     */
    public function up(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->pairs() as $pair) {
            $canonical = Term::where('slug->fr_CA', $pair['canonical'])->first();
            if ($canonical) {
                $canonical->aliases = array_values(array_unique(array_merge($canonical->aliases ?? [], $pair['extra'])));
                $canonical->save();
            }

            $dup = Term::where('slug->fr_CA', $pair['dup'])->first();
            if ($dup && $dup->is_published) {
                $dup->is_published = false;
                $dup->save();
                echo "[dedup] dépublié {$pair['dup']}\n";
            }
        }

        foreach ($this->linkOps() as $op) {
            $term = Term::where('slug->fr_CA', $op['slug'])->first();
            if (! $term) {
                continue;
            }
            $arr = $term->{$op['field']} ?? [];
            if ($op['type'] === 'remove') {
                $arr = array_values(array_diff($arr, [$op['value']]));
            } elseif ($op['type'] === 'replace') {
                $arr = array_values(array_map(fn ($v) => $v === $op['from'] ? $op['to'] : $v, $arr));
            }
            $term->{$op['field']} = $arr;
            $term->save();
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach ($this->pairs() as $pair) {
            $canonical = Term::where('slug->fr_CA', $pair['canonical'])->first();
            if ($canonical) {
                $canonical->aliases = array_values(array_diff($canonical->aliases ?? [], $pair['extra']));
                $canonical->save();
            }

            $dup = Term::where('slug->fr_CA', $pair['dup'])->first();
            if ($dup) {
                $dup->is_published = true;
                $dup->save();
            }
        }

        foreach ($this->linkOps() as $op) {
            $term = Term::where('slug->fr_CA', $op['slug'])->first();
            if (! $term) {
                continue;
            }
            $arr = $term->{$op['field']} ?? [];
            if ($op['type'] === 'remove') {
                if (! in_array($op['value'], $arr, true)) {
                    $arr[] = $op['value'];
                }
                $arr = array_values($arr);
            } elseif ($op['type'] === 'replace') {
                $arr = array_values(array_map(fn ($v) => $v === $op['to'] ? $op['from'] : $v, $arr));
            }
            $term->{$op['field']} = $arr;
            $term->save();
        }
    }

    private function pairs(): array
    {
        return [
            ['canonical' => 'token', 'dup' => 'tokens', 'extra' => ['Text Tokens', 'LLM Tokens', 'Input Tokens', 'Output Tokens', 'jetons IA']],
            ['canonical' => 'mixture-of-experts', 'dup' => 'moe', 'extra' => ['mixtures of experts']],
            ['canonical' => 'fenetre-de-contexte', 'dup' => 'context-window', 'extra' => ['context windows', 'fenêtres de contexte', 'context length', 'token window']],
            ['canonical' => 'ia-fantome', 'dup' => 'shadow-ai', 'extra' => ['Shadow IT IA', "usage non autorisé de l'IA", 'IA clandestine']],
            ['canonical' => 'prompt-injection', 'dup' => 'infiltration-de-requete', 'extra' => ['Infiltration de requête', 'Injection de prompt']],
            ['canonical' => 'distillation-de-modele', 'dup' => 'knowledge-distillation', 'extra' => ['Teacher-Student Learning', 'Knowledge Transfer']],
            ['canonical' => 'fine-tuning', 'dup' => 'affinage', 'extra' => ['Affinage']],
            ['canonical' => 'ia-embarquee', 'dup' => 'edge-ai', 'extra' => ['intelligence artificielle embarquée']],
        ];
    }

    private function linkOps(): array
    {
        return [
            ['type' => 'remove', 'slug' => 'token', 'field' => 'narrower_slugs', 'value' => 'tokens'],
            ['type' => 'remove', 'slug' => 'fine-tuning', 'field' => 'narrower_slugs', 'value' => 'affinage'],
            ['type' => 'remove', 'slug' => 'ia-fantome', 'field' => 'broader_slugs', 'value' => 'shadow-ai'],
            ['type' => 'remove', 'slug' => 'distillation-de-modele', 'field' => 'narrower_slugs', 'value' => 'knowledge-distillation'],
            ['type' => 'remove', 'slug' => 'ia-embarquee', 'field' => 'narrower_slugs', 'value' => 'edge-ai'],
            ['type' => 'remove', 'slug' => 'mixture-of-experts', 'field' => 'narrower_slugs', 'value' => 'moe'],
            ['type' => 'remove', 'slug' => 'prompt-injection', 'field' => 'narrower_slugs', 'value' => 'infiltration-de-requete'],
            ['type' => 'replace', 'slug' => 'byoai', 'field' => 'broader_slugs', 'from' => 'shadow-ai', 'to' => 'ia-fantome'],
        ];
    }
};
