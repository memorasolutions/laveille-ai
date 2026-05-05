<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-05-05 #151 : ajoute champ aliases JSON pour stocker les variations d'écriture d'un terme.
 *
 * Format : ["token", "tokens", "Tokens", "tokenization", "tokenisation"]
 * Le service GlossaryLinkifier load chaque alias comme entry séparée avec la même strategy
 * que le terme principal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            if (! Schema::hasColumn('dictionary_terms', 'aliases')) {
                $table->json('aliases')->nullable()->after('match_strategy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            $table->dropColumn('aliases');
        });
    }
};
