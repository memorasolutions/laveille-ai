<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Round 6 (skill /100) : cascadeOnDelete() sur creator_id supprimait INTÉGRALEMENT un sondage
 * (créneaux + tous les votes de tiers) dès que le créateur supprimait son compte utilisateur -
 * silencieux et sans préavis possible pour les votants (aucun compte requis pour voter). Décision
 * explicite de l'utilisateur : orpheliner (creator_id -> NULL) plutôt que cascader, pour protéger
 * les données des participants tiers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
        });

        Schema::table('decido_polls', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable()->change();
            $table->foreign('creator_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
        });

        Schema::table('decido_polls', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable(false)->change();
            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
