<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bibliothèque de gabarits curés (2026-08-20, Brique 1) : additive et réversible, comme les
// migrations précédentes sur cette table (voir add_tags_favorite_to_saved_prompts). Un gabarit
// officiel reste un SavedPrompt ordinaire - ce flag est la SEULE différence de schéma tolérée
// (frontière inscrite dans docs/CONTRAINTES-SOUS-AGENTS.md et le design approuvé : jamais de champ
// propre au gabarit au-delà de ce marqueur booléen).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('saved_prompts', 'is_official')) {
            Schema::table('saved_prompts', function (Blueprint $table) {
                $table->boolean('is_official')->default(false)->after('is_favorite');
                $table->index('is_official');
            });
        }
    }

    public function down(): void
    {
        Schema::table('saved_prompts', function (Blueprint $table) {
            if (Schema::hasColumn('saved_prompts', 'is_official')) {
                $table->dropIndex(['is_official']);
                $table->dropColumn('is_official');
            }
        });
    }
};
