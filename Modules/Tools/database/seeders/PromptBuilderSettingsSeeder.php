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
                // 3 verbes de recherche ajoutés en fin de liste (tâche 2026-08-12) - jamais de
                // retrait ni de réordonnancement des 14 existants (même contrat que le Blade,
                // voir $defaultVerbs).
                'value' => json_encode(['Rédige', 'Analyse', 'Crée', 'Génère', 'Explique', 'Compare', 'Résume', 'Traduis', 'Optimise', 'Évalue', 'Développe', 'Conçois', 'Planifie', 'Diagnostique', 'Recherche', 'Recherche sur Internet, en priorisant les sites officiels et pertinents', 'Recherche en profondeur, Internet inclus'], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'description' => 'Verbes d\'action du constructeur de prompts',
            ],
            [
                'group' => 'tools',
                'key' => 'tools.prompt_builder.audiences',
                // Liste recalibrée sur l'audience réelle du site (consensus panel Codex/DeepSeek/
                // Perplexity 2026-08-06, tâche 1633) : public scolaire d'abord, familles MEQ.
                // Les anciennes valeurs (pro/debutants/entrepreneurs/techniques) sont remappées
                // côté JS à la restauration (migrateAudienceValues, constructeur-prompts-core.js).
                'value' => json_encode([
                    ['value' => 'eleves_primaire', 'label' => 'Élèves du primaire'],
                    ['value' => 'eleves_secondaire', 'label' => 'Élèves du secondaire'],
                    ['value' => 'etudiants', 'label' => 'Étudiants'],
                    ['value' => 'parents', 'label' => 'Parents'],
                    ['value' => 'collegues', 'label' => 'Collègues de travail'],
                    ['value' => 'direction', 'label' => 'Direction ou gestionnaires'],
                    ['value' => 'clients', 'label' => 'Clients'],
                    ['value' => 'grand_public', 'label' => 'Grand public'],
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
