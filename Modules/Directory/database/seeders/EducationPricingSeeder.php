<?php

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder tarifs éducation outils annuaire.
 *
 * Source of truth après audit complet S90 (2026-05-09) :
 * - 12 outils originaux S74 (10 ici + Notion AI id=7 + Wooclap id=238 ajoutés)
 * - 5 enrichis manuellement S90 (Claude, Khanmigo, Beautiful.ai, Gamma, Google AI Studio)
 * - 8 enrichis via audit LLM + curl validation S90 (ElevenLabs, Codeium, Otter.ai,
 *   Semantic Scholar, Poe, InVideo, Fliki, Crazy Egg)
 * - 1 URL update : Perplexity /edu → /enterprise (Perplexity Enterprise Pro)
 *
 * Total : 25 outils edu sur 282 publiés (8.9% du catalogue).
 * Tous URLs validés via curl ou pp_search. last_checked_at populé à exécution.
 */
class EducationPricingSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            // ── 12 outils originaux S74 ──
            'chatgpt' => [
                'type' => 'free',
                'details' => 'ChatGPT for Teachers — espace de travail gratuit avec contrôles de confidentialité pour les enseignants K-12. Vérification requise.',
                'url' => 'https://openai.com/chatgpt/education/',
                'verif' => true,
            ],
            'canva-ai' => [
                'type' => 'free',
                'details' => "Canva pour l'éducation — accès Pro complet gratuit pour les enseignants et étudiants. Inscription avec courriel .edu.",
                'url' => 'https://www.canva.com/education/',
                'verif' => true,
            ],
            'github-copilot' => [
                'type' => 'free',
                'details' => 'GitHub Student Developer Pack — Copilot Pro gratuit pour les étudiants et enseignants. Vérification avec courriel .edu.',
                'url' => 'https://education.github.com/',
                'verif' => true,
            ],
            'gemini' => [
                'type' => 'free',
                'details' => 'Google AI Pro gratuit 12 mois pour les étudiants vérifiés (Gemini 3.1 Pro, Gemini Notebook Plus [anciennement NotebookLM Plus], 2 To stockage). Vérification SheerID.',
                'url' => 'https://one.google.com/explore-plan/gemini-advanced',
                'verif' => true,
            ],
            'perplexity' => [
                // S90 update : URL déménagée /edu → /enterprise (Perplexity Enterprise Pro B2B institutionnel)
                'type' => 'verified-only',
                'details' => "Tarifs spéciaux écoles, universités et organismes via Perplexity Enterprise Pro.",
                'url' => 'https://www.perplexity.ai/enterprise',
                'verif' => true,
            ],
            'notebooklm' => [
                'type' => 'free',
                'details' => 'Inclus dans Google AI Pro étudiant (gratuit 12 mois). Gemini Notebook Plus (anciennement NotebookLM Plus) avec fonctionnalités avancées.',
                'url' => 'https://notebooklm.google.com/',
                'verif' => false,
            ],
            'magicschool-ai' => [
                'type' => 'free',
                'details' => "MagicSchool AI — 80+ outils gratuits pour la planification de cours et l'évaluation. Conforme FERPA/COPPA.",
                'url' => 'https://www.magicschool.ai/',
                'verif' => false,
            ],
            'grammarly' => [
                'type' => 'free',
                'details' => "Grammarly pour l'éducation — licence gratuite pour les institutions. Contacter l'institution pour accès.",
                'url' => 'https://www.grammarly.com/edu',
                'verif' => true,
            ],
            'adobe-firefly' => [
                'type' => 'discount',
                'details' => "Adobe Creative Cloud pour l'éducation — rabais 60%+ pour étudiants et enseignants. Inclut Firefly, Photoshop, Illustrator.",
                'url' => 'https://www.adobe.com/education.html',
                'verif' => true,
            ],
            'copilot' => [
                'type' => 'free',
                'details' => 'Microsoft Copilot gratuit pour les établissements éducatifs via Microsoft 365 Education. Vérification institutionnelle.',
                'url' => 'https://www.microsoft.com/education',
                'verif' => true,
            ],
            'notion-ai' => [
                'type' => 'free',
                'details' => "Notion pour l'éducation — plan Plus gratuit pour étudiants et enseignants vérifiés.",
                'url' => 'https://www.notion.com/product/notion-for-education',
                'verif' => true,
            ],
            'wooclap' => [
                'type' => 'free',
                'details' => "Wooclap propose une offre gratuite pour les enseignants et institutions éducatives.",
                'url' => 'https://www.wooclap.com/fr/pricing/tarifs-education/',
                'verif' => false,
            ],

            // ── 5 enrichis manuellement S90 (cf pp_search batched) ──
            'claude' => [
                'type' => 'verified-only',
                'details' => "Accès institutionnel sur campus pour étudiants, faculté et personnel.",
                'url' => 'https://www.anthropic.com/education',
                'verif' => true,
            ],
            'khanmigo' => [
                'type' => 'free',
                'details' => "Gratuit pour tous les enseignants américains grâce au partenariat Microsoft.",
                'url' => 'https://www.khanacademy.org/khan-labs',
                'verif' => false,
            ],
            'beautifulai' => [
                'type' => 'free',
                'details' => "Abonnement Pro gratuit annuel pour étudiants avec courriel .edu vérifié.",
                'url' => 'https://www.beautiful.ai/education',
                'verif' => true,
            ],
            'gamma' => [
                'type' => 'discount',
                'details' => "50% de rabais sur tous les plans pour étudiants (offre périodique).",
                'url' => 'https://gamma.app/solutions/educators',
                'verif' => false,
            ],
            'google-ai-studio' => [
                'type' => 'free',
                'details' => "Gemini Pro inclus sans frais dans Workspace for Education Fundamentals.",
                'url' => 'https://edu.google.com/workspace-for-education/',
                'verif' => false,
            ],

            // ── 8 enrichis via audit LLM + curl HEAD validation S90 ──
            'elevenlabs' => [
                'type' => 'discount',
                'details' => "Réduction étudiante disponible sur demande.",
                'url' => 'https://elevenlabs.io/education',
                'verif' => false,
            ],
            'codeium' => [
                'type' => 'free',
                'details' => "Codeium gratuit pour étudiants vérifiés.",
                'url' => 'https://codeium.com/education',
                'verif' => true,
            ],
            'otterai' => [
                'type' => 'discount',
                'details' => "Otter.ai pour l'éducation (réduction).",
                'url' => 'https://otter.ai/education',
                'verif' => false,
            ],
            'semantic-scholar' => [
                'type' => 'free',
                'details' => "Gratuit pour tous (recherche académique ouverte).",
                'url' => 'https://www.semanticscholar.org',
                'verif' => false,
            ],
            'poe' => [
                'type' => 'free',
                'details' => "Accès gratuit via Poe pour étudiants vérifiés.",
                'url' => 'https://poe.com/education',
                'verif' => true,
            ],
            'invideo' => [
                'type' => 'discount',
                'details' => "Réduction éducation disponible sur demande.",
                'url' => 'https://invideo.io/education/',
                'verif' => false,
            ],
            'fliki' => [
                'type' => 'discount',
                'details' => "Réduction étudiante via GitHub Student Pack.",
                'url' => 'https://fliki.ai/education',
                'verif' => false,
            ],
            'crazy-egg' => [
                'type' => 'discount',
                'details' => "Réduction éducation disponible sur demande.",
                'url' => 'https://www.crazyegg.com/education/',
                'verif' => false,
            ],
        ];

        $slugExpr = "JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"'))";
        $now = now();

        foreach ($tools as $slug => $data) {
            $details = json_encode([
                'fr_CA' => $data['details'],
                'fr' => $data['details'],
                'en' => $data['details'],
            ], JSON_UNESCAPED_UNICODE);

            DB::table('directory_tools')
                ->whereRaw("{$slugExpr} = ?", [$slug])
                ->update([
                    'has_education_pricing' => true,
                    'education_pricing_type' => $data['type'],
                    'education_pricing_details' => $details,
                    'education_pricing_url' => $data['url'],
                    'education_verification_required' => $data['verif'] ? 1 : 0,
                    'education_last_checked_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }
}
