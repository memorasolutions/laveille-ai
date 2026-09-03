<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ACTION: migration additive - trace le RESULTAT de la derniere tentative de capture (screenshot,
// og_image, fallback, failed) et sa date, pour que directory:dispatch-margin-recapture puisse
// distinguer un candidat neuf d'une "recapture futile" deja tentee recemment sans jamais produire
// de master.
// MCP: SELF (migration < 5 lignes de logique, meme convention que screenshot_focal_y/screenshot_master_stale)
// RAISON: ticket #2087 lot 1 (2026-09-03) - mesure sur 100 jobs de dispatch-margin-recapture : 48
// echecs francs et 48 "succes" par repli og:image qui NE PRODUIT PAS de master (voir
// ScreenshotService::capture(), le chemin og:image n'ecrit jamais dans screenshots/masters/) - ces
// outils redeviennent candidats au dispatch suivant sans jamais progresser (3 masters sur 100).
// Colonnes nullable, aucune donnee existante affectee, down() symetrique.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('directory_tools', 'screenshot_last_attempt_at')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->timestamp('screenshot_last_attempt_at')->nullable()->after('screenshot_master_stale');
                $table->string('screenshot_last_attempt_result', 20)->nullable()->after('screenshot_last_attempt_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('directory_tools', 'screenshot_last_attempt_at')) {
            Schema::table('directory_tools', function (Blueprint $table): void {
                $table->dropColumn(['screenshot_last_attempt_at', 'screenshot_last_attempt_result']);
            });
        }
    }
};
