<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ACTION: migration additive pour le point focal vertical des captures d'ecran de l'annuaire
// MCP: SELF (migration < 5 lignes de logique, convention identique aux migrations existantes du module)
// RAISON: colonne nullable/defaut 0, aucune donnee existante affectee, down() symetrique (design doc section 4)
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('directory_tools', 'screenshot_focal_y')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->unsignedSmallInteger('screenshot_focal_y')->default(0)->nullable()->after('screenshot_locked');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('directory_tools', 'screenshot_focal_y')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->dropColumn('screenshot_focal_y');
            });
        }
    }
};
