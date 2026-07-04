<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed de l'entrée annuaire pour le nouvel outil gratuit « Minuteur visuel »
 * (/outils/minuteur-visuel). Pattern calqué sur 2026_06_13_140000_seed_qt_tool_entry.php
 * (updateOrInsert, down() réversible).
 *
 * is_under_construction=true : gate volontaire pendant le développement — seul un
 * superadmin voit l'outil réel (bypass déjà géré par PublicToolController::show()),
 * tout autre visiteur reçoit le placeholder « En construction ». Passage à false =
 * décision explicite séparée de l'utilisateur (GO de mise en ligne publique).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tools')) {
            return;
        }

        $data = [
            'name' => 'Minuteur visuel',
            'description' => 'Minuteur visuel gratuit avec 5 styles animés (disque TimeTimer, sablier, anneau, chiffres, feu de circulation), présélections rapides et alertes sonores. Sans compte, sans installation.',
            'icon' => '⏱️',
            'category' => 'productivite',
            'is_active' => true,
            'sort_order' => 14,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('tools', 'is_under_construction')) {
            $data['is_under_construction'] = true;
        }

        if (Schema::hasColumn('tools', 'featured_image')) {
            $data['featured_image'] = 'images/tools/minuteur-visuel.jpg';
        }

        if (Schema::hasColumn('tools', 'answer_summary')) {
            $data['answer_summary'] = 'Un minuteur visuel gratuit avec 5 styles animés (disque TimeTimer, sablier, anneau de progression, chiffres, feu de circulation), des présélections rapides et des alertes sonores, sans compte ni installation.';
        }

        if (Schema::hasColumn('tools', 'answer_points')) {
            $data['answer_points'] = json_encode([
                '5 styles visuels au choix (disque, sablier, anneau, chiffres, feu de circulation)',
                'Présélections 5/10/15/25/45 minutes + Pomodoro 25/5',
                'Alerte sonore et notification « bientôt fini »',
                'Mode plein écran pour la classe ou les présentations',
                'Palette de couleur personnalisable, mémorisée sur votre appareil',
            ], JSON_UNESCAPED_UNICODE);
        }

        DB::table('tools')->updateOrInsert(['slug' => 'minuteur-visuel'], $data);
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            DB::table('tools')->where('slug', 'minuteur-visuel')->delete();
        }
    }
};
