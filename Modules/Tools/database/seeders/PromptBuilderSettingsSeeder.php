<?php

declare(strict_types=1);

namespace Modules\Tools\Database\Seeders;

use Illuminate\Database\Seeder;

class PromptBuilderSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (!class_exists(\Modules\Settings\Models\Setting::class)) {
            return;
        }

        $defaults = [
            [
                'group' => 'tools',
                'key' => 'tools.prompt_builder.personas',
                'value' => json_encode([
                    ['value' => 'expert_marketing', 'label' => 'Expert en marketing digital'],
                    ['value' => 'redacteur_web', 'label' => 'Rédacteur web professionnel'],
                    ['value' => 'enseignant', 'label' => 'Enseignant pédagogue'],
                    ['value' => 'developpeur', 'label' => 'Développeur senior'],
                    ['value' => 'consultant', 'label' => 'Consultant en stratégie'],
                    ['value' => 'graphiste', 'label' => 'Graphiste créatif'],
                    ['value' => 'analyste', 'label' => 'Analyste de données'],
                    ['value' => 'gestionnaire', 'label' => 'Gestionnaire de projet'],
                    ['value' => 'coach', 'label' => 'Coach professionnel'],
                    ['value' => 'journaliste', 'label' => 'Journaliste d\'investigation'],
                    ['value' => 'chercheur', 'label' => 'Chercheur scientifique'],
                    ['value' => 'rh', 'label' => 'Spécialiste en ressources humaines'],
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'description' => 'Personas prédéfinis du constructeur de prompts',
            ],
            [
                'group' => 'tools',
                'key' => 'tools.prompt_builder.verbs',
                'value' => json_encode(['Rédige', 'Analyse', 'Crée', 'Génère', 'Explique', 'Compare', 'Résume', 'Traduis', 'Optimise', 'Évalue', 'Développe', 'Conçois', 'Planifie', 'Diagnostique'], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'description' => 'Verbes d\'action du constructeur de prompts',
            ],
            [
                'group' => 'tools',
                'key' => 'tools.prompt_builder.audiences',
                'value' => json_encode([
                    ['value' => 'pro', 'label' => 'Professionnels du secteur'],
                    ['value' => 'debutants', 'label' => 'Débutants'],
                    ['value' => 'entrepreneurs', 'label' => 'Entrepreneurs et dirigeants'],
                    ['value' => 'etudiants', 'label' => 'Étudiants universitaires'],
                    ['value' => 'grand_public', 'label' => 'Grand public'],
                    ['value' => 'techniques', 'label' => 'Collègues techniques'],
                    ['value' => 'direction', 'label' => 'Direction générale'],
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'description' => 'Audiences prédéfinies du constructeur de prompts',
            ],
        ];

        foreach ($defaults as $setting) {
            \Modules\Settings\Models\Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
