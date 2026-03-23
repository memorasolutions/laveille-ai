<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tools\Models\Tool;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            ['name' => 'Calculatrice de taxes', 'slug' => 'calculatrice-taxes', 'description' => 'Calculez la TPS et la TVQ sur vos achats au Québec.', 'icon' => '🧮', 'sort_order' => 1],
            ['name' => 'Générateur de mots de passe', 'slug' => 'generateur-mots-passe', 'description' => 'Générez des mots de passe sécurisés et personnalisables.', 'icon' => '🔐', 'sort_order' => 2],
            ['name' => 'Générateur d\'équipes', 'slug' => 'generateur-equipes', 'description' => 'Créez des équipes aléatoires à partir d\'une liste de participants.', 'icon' => '👥', 'sort_order' => 3],
            ['name' => 'Tirage de présentations', 'slug' => 'tirage-presentations', 'description' => 'Tirez au sort l\'ordre des présentations.', 'icon' => '🎤', 'sort_order' => 4],
            ['name' => 'Liens Google', 'slug' => 'liens-google', 'description' => 'Générez des liens Google personnalisés (recherche, maps, traduction).', 'icon' => '🔍', 'sort_order' => 5],
            ['name' => 'Générateur de code QR', 'slug' => 'code-qr', 'description' => 'Créez des codes QR pour vos URLs, textes et contacts.', 'icon' => '📱', 'sort_order' => 6],
            ['name' => 'Constructeur de prompts', 'slug' => 'constructeur-prompts', 'description' => 'Construisez des prompts IA structurés et efficaces.', 'icon' => '💡', 'sort_order' => 7],
            ['name' => 'Simulateur fiscal', 'slug' => 'simulateur-fiscal', 'description' => 'Simulez vos impôts et visualisez la répartition avec des graphiques.', 'icon' => '📊', 'sort_order' => 8],
            ['name' => 'Roue de tirage', 'slug' => 'roue-tirage', 'description' => 'Une roue de la fortune interactive pour vos tirages au sort.', 'icon' => '🎡', 'sort_order' => 9],
            ['name' => 'Oscilloscope RLC', 'slug' => 'oscilloscope-rlc', 'description' => 'Simulateur d\'oscilloscope pour visualiser les signaux dans les circuits RLC (résistance, inductance, capacitance).', 'icon' => '📡', 'sort_order' => 10],
        ];

        foreach ($tools as $tool) {
            Tool::firstOrCreate(['slug' => $tool['slug']], $tool);
        }
    }
}
