<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-05 #145 : ajoute match_strategy pour WSD acronymes.
 *
 * Pour les acronymes, défaut = 'case_sensitive' car un acronyme implicite est tjrs en majuscules.
 * Valeurs : loose | case_sensitive (défaut acronymes) | exact_phrase | never_auto
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acronyms', function (Blueprint $table) {
            if (! Schema::hasColumn('acronyms', 'match_strategy')) {
                $table->string('match_strategy', 20)->default('case_sensitive')->after('is_published');
                $table->index('match_strategy', 'idx_acronyms_match_strategy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acronyms', function (Blueprint $table) {
            $table->dropIndex('idx_acronyms_match_strategy');
            $table->dropColumn('match_strategy');
        });
    }
};
