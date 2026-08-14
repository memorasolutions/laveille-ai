<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ACTION: migration additive - indicateur de maitre de vignette perime. Pose quand une recapture
// (upload manuel) produit une image trop courte une fois mise a l'echelle : le maitre EXISTANT et
// le point focal regle par l'administrateur sont desormais conserves intacts (jamais supprimes ni
// reinitialises), et cette colonne rend l'ecart VISIBLE cote admin au lieu de le trancher seul.
// MCP: SELF (migration < 5 lignes de logique, meme convention que screenshot_focal_y)
// RAISON: correctif 2026-08-14 (perte de travail admin silencieuse) - colonne nullable/defaut
// false, aucune donnee existante affectee, down() symetrique.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('directory_tools', 'screenshot_master_stale')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->boolean('screenshot_master_stale')->default(false)->after('screenshot_focal_y');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('directory_tools', 'screenshot_master_stale')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->dropColumn('screenshot_master_stale');
            });
        }
    }
};
