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
                    ['value' => 'redacteur_web', 'label' => 'Redacteur web professionnel'],
                    ['value' => 'enseignant', 'label' => 'Enseignant pedagogue'],
                    ['value' => 'developpeur', 'label' => 'Developpeur senior'],
                    ['value' => 'consultant', 'label' => 'Consultant en strategie'],
                    ['value' => 'graphiste', 'label' => 'Graphiste creatif'],
                    ['value' => 'analyste', 'label' => 'Analyste de donnees'],
                    ['value' => 'gestionnaire', 'label' => 'Gestionnaire de projet'],
                    ['value' => 'coach', 'label' => 'Coach professionnel'],
                    ['value' => 'journaliste', 'label' => 'Journaliste d\'investigation'],
                    ['value' => 'chercheur', 'label' => 'Chercheur scientifique'],
                    ['value' => 'rh', 'label' => 'Specialiste en ressources humaines'],
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'description' => 'Personas predefinis du constructeur de prompts',
            ],
            [
                'group' => 'tools',
                'key' => 'tools.prompt_builder.verbs',
                'value' => json_encode(['Redige', 'Analyse', 'Cree', 'Genere', 'Explique', 'Compare', 'Resume', 'Traduis', 'Optimise', 'Evalue', 'Developpe', 'Concois', 'Planifie', 'Diagnostique'], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'description' => 'Verbes d\'action du constructeur de prompts',
            ],
            [
                'group' => 'tools',
                'key' => 'tools.prompt_builder.audiences',
                'value' => json_encode([
                    ['value' => 'pro', 'label' => 'Professionnels du secteur'],
                    ['value' => 'debutants', 'label' => 'Debutants'],
                    ['value' => 'entrepreneurs', 'label' => 'Entrepreneurs et dirigeants'],
                    ['value' => 'etudiants', 'label' => 'Etudiants universitaires'],
                    ['value' => 'grand_public', 'label' => 'Grand public'],
                    ['value' => 'techniques', 'label' => 'Collegues techniques'],
                    ['value' => 'direction', 'label' => 'Direction generale'],
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'description' => 'Audiences predefinis du constructeur de prompts',
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
