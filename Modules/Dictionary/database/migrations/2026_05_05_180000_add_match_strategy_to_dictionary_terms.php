<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-05 #145 : ajoute match_strategy pour WSD (Word Sense Disambiguation).
 *
 * Valeurs possibles :
 *  - 'loose'          (défaut)  : matching insensible casse (comportement actuel)
 *  - 'case_sensitive' : ne match que la casse exacte (ex : "Transformer" architecture vs "transformer" verbe)
 *  - 'exact_phrase'   : match seulement la phrase complète (ex : "Intelligence artificielle générative")
 *  - 'never_auto'     : pas de matching automatique (terme listé mais ambiguïté trop forte)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            if (! Schema::hasColumn('dictionary_terms', 'match_strategy')) {
                $table->string('match_strategy', 20)->default('loose')->after('is_published');
                $table->index('match_strategy', 'idx_terms_match_strategy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            $table->dropIndex('idx_terms_match_strategy');
            $table->dropColumn('match_strategy');
        });
    }
};
