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
 * Alias de glossaire pour l'auto-lien : « mésalignement » et « misalignment » vers la fiche
 * « alignement-ia », « grader » vers « llm-as-a-judge ». Fusion dans les alias existants (aucun
 * retrait). « correcteur » volontairement exclu : trop courant, il sur-lierait hors contexte IA.
 *
 * Idempotente. Terme introuvable : message et saut, jamais d'échec. down() retire exactement
 * ces alias et rien d'autre.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const ADD = [
        'alignement-ia' => ['mésalignement', 'misalignment'],
        'llm-as-a-judge' => ['grader'],
    ];

    private function find(string $slug): ?Term
    {
        return Term::where('slug->fr_CA', $slug)->first()
            ?? Term::where('slug->fr', $slug)->first();
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! class_exists(Term::class)) {
            return;
        }

        foreach (self::ADD as $slug => $aliases) {
            $term = $this->find($slug);
            if ($term === null) {
                echo "[glossaire] {$slug} introuvable, alias ignorés\n";

                continue;
            }
            $current = array_values((array) ($term->aliases ?? []));
            $merged = $current;
            foreach ($aliases as $a) {
                if (! in_array($a, $merged, true)) {
                    $merged[] = $a;
                }
            }
            if ($merged !== $current) {
                $term->aliases = $merged;
                $term->save();
                echo "[glossaire] alias ajoutés à {$slug}\n";
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        foreach (self::ADD as $slug => $aliases) {
            $term = $this->find($slug);
            if ($term === null) {
                continue;
            }
            $term->aliases = array_values(array_diff((array) ($term->aliases ?? []), $aliases));
            $term->save();
        }
    }
};
