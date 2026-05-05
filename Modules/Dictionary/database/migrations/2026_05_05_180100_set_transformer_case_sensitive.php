<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-05 #145 : data migration pour les termes ambigus connus avec verbes/noms FR.
 * Marque "Transformer" en case_sensitive pour ne plus matcher le verbe "transformer".
 *
 * La stop-list FR du Service GlossaryLinkifier escalade automatiquement, mais on persiste
 * la valeur en DB pour cohérence et pour que l'admin voie l'état dans /admin/dictionary.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('dictionary_terms', 'match_strategy')) {
            return;
        }

        $ambiguousTerms = [
            'transformer', 'modèle', 'modeler', 'agent', 'vision',
            'attention', 'apprentissage', 'plateforme', 'architecture',
        ];

        foreach ($ambiguousTerms as $name) {
            DB::table('dictionary_terms')
                ->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.fr_CA"))) = ?', [$name])
                ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.fr"))) = ?', [$name])
                ->update(['match_strategy' => 'case_sensitive']);
        }
    }

    public function down(): void
    {
        // Réversibilité : revient en 'loose' (la stop-list escalade quand même au runtime)
        DB::table('dictionary_terms')->where('match_strategy', 'case_sensitive')->update(['match_strategy' => 'loose']);
    }
};
