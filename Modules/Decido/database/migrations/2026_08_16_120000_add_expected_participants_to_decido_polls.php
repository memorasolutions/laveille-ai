<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi des non-répondants, SANS carnet d'adresses (2026-08-16, LOT 3) : l'organisateur peut
 * déclarer combien de personnes il attend au total (un simple entier facultatif, jamais une
 * liste de noms ni d'adresses courriel) - l'outil affiche alors une progression "X sur Y
 * réponses reçues" sur sa page de gestion. NULL = aucun nombre déclaré, comportement identique
 * à aujourd'hui (juste le compte de réponses reçues, sans cible).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            if (! Schema::hasColumn('decido_polls', 'expected_participants')) {
                $table->unsignedSmallInteger('expected_participants')->nullable()->after('extension_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            if (Schema::hasColumn('decido_polls', 'expected_participants')) {
                $table->dropColumn('expected_participants');
            }
        });
    }
};
