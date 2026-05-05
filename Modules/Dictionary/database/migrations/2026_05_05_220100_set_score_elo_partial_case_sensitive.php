<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-05 #150 : Score Elo et autres termes ambigus passent en partial_case_sensitive
 * (1ère lettre tolérante, reste strict). Permet "Score Elo" ET "score Elo" sans matcher "score elo".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('dictionary_terms', 'match_strategy')) return;

        // Termes a passer en partial_case_sensitive (au lieu de case_sensitive strict)
        $partialCaseTerms = [
            'score-elo', 'transformer', 'modèle', 'agent', 'vision',
            'attention', 'apprentissage', 'plateforme', 'architecture',
        ];

        foreach ($partialCaseTerms as $slug) {
            DB::table('dictionary_terms')
                ->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(slug, "$.fr_CA"))) = ?', [$slug])
                ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(slug, "$.fr"))) = ?', [$slug])
                ->update(['match_strategy' => 'partial_case_sensitive']);
        }
    }

    public function down(): void
    {
        DB::table('dictionary_terms')
            ->where('match_strategy', 'partial_case_sensitive')
            ->update(['match_strategy' => 'case_sensitive']);
    }
};
