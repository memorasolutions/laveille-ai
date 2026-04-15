<?php

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationPricingSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            'chatgpt' => [
                'type' => 'free',
                'details' => 'ChatGPT for Teachers — espace de travail gratuit avec contrôles de confidentialité pour les enseignants K-12. Vérification requise.',
                'url' => 'https://openai.com/chatgpt/education/',
            ],
            'canva-ai' => [
                'type' => 'free',
                'details' => "Canva pour l'éducation — accès Pro complet gratuit pour les enseignants et étudiants. Inscription avec courriel .edu.",
                'url' => 'https://www.canva.com/education/',
            ],
            'github-copilot' => [
                'type' => 'free',
                'details' => 'GitHub Student Developer Pack — Copilot Pro gratuit pour les étudiants et enseignants. Vérification avec courriel .edu.',
                'url' => 'https://education.github.com/',
            ],
            'gemini' => [
                'type' => 'free',
                'details' => 'Google AI Pro gratuit 12 mois pour les étudiants vérifiés (Gemini 3.1 Pro, NotebookLM Plus, 2 To stockage). Vérification SheerID.',
                'url' => 'https://one.google.com/explore-plan/gemini-advanced',
            ],
            'perplexity' => [
                'type' => 'discount',
                'details' => 'Perplexity Education Pro — 10$/mois au lieu de 20$ (50% de rabais) pour étudiants et enseignants. Vérification SheerID.',
                'url' => 'https://www.perplexity.ai/edu',
            ],
            'notebooklm' => [
                'type' => 'free',
                'details' => 'Inclus dans Google AI Pro étudiant (gratuit 12 mois). NotebookLM Plus avec fonctionnalités avancées.',
                'url' => 'https://notebooklm.google.com/',
            ],
            'magicschool-ai' => [
                'type' => 'free',
                'details' => "MagicSchool AI — 80+ outils gratuits pour la planification de cours et l'évaluation. Conforme FERPA/COPPA.",
                'url' => 'https://www.magicschool.ai/',
            ],
            'grammarly' => [
                'type' => 'free',
                'details' => "Grammarly pour l'éducation — licence gratuite pour les institutions. Contacter l'institution pour accès.",
                'url' => 'https://www.grammarly.com/edu',
            ],
            'adobe-firefly' => [
                'type' => 'discount',
                'details' => "Adobe Creative Cloud pour l'éducation — rabais 60%+ pour étudiants et enseignants. Inclut Firefly, Photoshop, Illustrator.",
                'url' => 'https://www.adobe.com/education.html',
            ],
            'copilot' => [
                'type' => 'free',
                'details' => 'Microsoft Copilot gratuit pour les établissements éducatifs via Microsoft 365 Education. Vérification institutionnelle.',
                'url' => 'https://www.microsoft.com/education',
            ],
        ];

        $slugExpr = "JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"'))";

        foreach ($tools as $slug => $data) {
            DB::table('directory_tools')
                ->whereRaw("{$slugExpr} = ?", [$slug])
                ->update([
                    'has_education_pricing' => true,
                    'education_pricing_type' => $data['type'],
                    'education_pricing_details' => json_encode(['fr_CA' => $data['details']]),
                    'education_pricing_url' => $data['url'],
                    'updated_at' => now(),
                ]);
        }
    }
}
