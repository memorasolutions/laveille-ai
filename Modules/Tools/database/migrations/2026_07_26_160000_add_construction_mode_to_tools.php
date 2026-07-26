<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Tools\Models\Tool;

/**
 * #325 : distingue 2 messages/designs de placeholder pour un outil gaté
 * (`tools.is_under_construction`) : un outil JAMAIS lancé ('construction', défaut — comportement
 * existant conservé) vs un outil RETIRÉ temporairement après avoir été public ('revision', ex.
 * constructeur-prompts remis en révision le 2026-07-26). Idempotente (guard hasColumn).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tools', 'construction_mode')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->string('construction_mode', 20)
                    ->default('construction')
                    ->after('is_under_construction');
            });
        }

        if (Schema::hasColumn('tools', 'construction_mode')) {
            Tool::where('slug', 'constructeur-prompts')->update(['construction_mode' => 'revision']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tools', 'construction_mode')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->dropColumn('construction_mode');
            });
        }
    }
};
