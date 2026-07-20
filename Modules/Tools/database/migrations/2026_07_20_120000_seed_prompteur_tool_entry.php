<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed de l'entrée annuaire pour le nouvel outil gratuit « Prompteur »
 * (/outils/prompteur). Pattern calqué sur 2026_07_04_120000_seed_minuteur_visuel_tool_entry.php
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
            'name' => 'Prompteur',
            'description' => 'Téléprompteur gratuit avec éditeur de script structuré en sections (indication visuelle/action + texte à dire ou grandes lignes, au choix), défilement synchronisé à votre débit et générateur de méta-prompt à copier dans l\'IA de votre choix pour créer le contenu automatiquement. Sans compte, 100 % dans le navigateur.',
            'icon' => '🎬',
            'category' => 'productivite',
            'is_active' => true,
            'sort_order' => 16,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('tools', 'is_under_construction')) {
            $data['is_under_construction'] = true;
        }

        if (Schema::hasColumn('tools', 'featured_image')) {
            $data['featured_image'] = 'images/tools/prompteur.jpg';
        }

        if (Schema::hasColumn('tools', 'answer_summary')) {
            $data['answer_summary'] = 'Un téléprompteur gratuit avec éditeur de script en sections (indication visuelle/action + texte ou grandes lignes) et un générateur de méta-prompt à copier dans l\'IA de votre choix pour générer le contenu automatiquement, sans compte ni installation.';
        }

        if (Schema::hasColumn('tools', 'answer_points')) {
            $data['answer_points'] = json_encode([
                'Éditeur de script en sections avec indication visuelle/action et texte à dire (ou grandes lignes au choix)',
                'Mode téléprompteur plein écran avec défilement ajusté à votre débit de parole',
                'Générateur de méta-prompt à copier-coller dans ChatGPT, Claude ou l\'IA de votre choix (méthode « apportez votre IA »)',
                '100 % gratuit et confidentiel, sans compte, tout se passe dans votre navigateur',
                'Personnalisation du thème, du contraste et de la taille du texte',
            ], JSON_UNESCAPED_UNICODE);
        }

        DB::table('tools')->updateOrInsert(['slug' => 'prompteur'], $data);
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            DB::table('tools')->where('slug', 'prompteur')->delete();
        }
    }
};
