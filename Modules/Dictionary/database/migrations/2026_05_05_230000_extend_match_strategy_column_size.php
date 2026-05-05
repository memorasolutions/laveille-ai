<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-05 #150 fix : étend match_strategy de VARCHAR(20) à VARCHAR(30).
 * Nécessaire pour stocker la valeur 'partial_case_sensitive' (22 chars).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('dictionary_terms', 'match_strategy')) {
            DB::statement("ALTER TABLE dictionary_terms MODIFY COLUMN match_strategy VARCHAR(30) DEFAULT 'loose'");
        }
        if (Schema::hasColumn('acronyms', 'match_strategy')) {
            DB::statement("ALTER TABLE acronyms MODIFY COLUMN match_strategy VARCHAR(30) DEFAULT 'case_sensitive'");
        }
    }

    public function down(): void
    {
        // Pas de revert (risque truncation si données 'partial_case_sensitive' existent)
    }
};
