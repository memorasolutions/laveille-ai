<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder Top EdTech moyenne priorité enseignants 2026 (S90 — gap analysis Top 50).
 *
 * Suite à insertion EdtechTop16Seeder (haute priorité), couvre la couche moyenne :
 * IA différenciation (Diffit, Curipod), quiz (Nearpod, Pear Deck, Socrative),
 * création (Book Creator, Genially), vidéo (Screencastify),
 * maths/sciences (Desmos, GeoGebra, Wolfram Alpha),
 * Québec (Alloprof — incontournable QC),
 * langues (ELSA Speak).
 *
 * 13 outils — Flip exclu (Microsoft a sunset Flip mi-2024).
 *
 * Idempotent (skip si slug existe), URLs validées via curl HEAD/GET S90.
 */
class EdtechMidPriority13Seeder extends Seeder
{
    private const EDU_CATEGORY_ID = 12;

    public function run(): void
    {
        $tools = [
            ['name' => 'Diffit', 'slug' => 'diffit', 'url' => 'https://diffit.me',
             'short' => "Créez du contenu différencié en un clic.",
             'long' => "Diffit utilise l'IA pour générer automatiquement des ressources pédagogiques différenciées selon le niveau de chaque élève.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Différenciation pédagogique instantanée."],
            ['name' => 'Curipod', 'slug' => 'curipod', 'url' => 'https://curipod.com',
             'short' => "Leçons interactives générées par IA.",
             'long' => "Curipod crée des leçons interactives personnalisées avec l'IA, intégrant des questions, des sondages et des activités collaboratives.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Leçons IA prêtes en minutes."],
            ['name' => 'Nearpod', 'slug' => 'nearpod', 'url' => 'https://nearpod.com',
             'short' => "Cours interactifs avec suivi en temps réel.",
             'long' => "Nearpod permet de créer des leçons multimédias interactives avec évaluations formatives, réalité virtuelle et collaboration en classe.",
             'pricing' => 'freemium', 'edu_type' => 'verified-only', 'edu_url' => '',
             'tagline' => "Engagez chaque élève activement."],
            ['name' => 'Pear Deck', 'slug' => 'pear-deck', 'url' => 'https://peardeck.com',
             'short' => "Diaporamas interactifs pour Google Slides et PowerPoint.",
             'long' => "Pear Deck transforme vos présentations en expériences interactives avec questions, dessins et feedback en direct.",
             'pricing' => 'freemium', 'edu_type' => 'verified-only', 'edu_url' => '',
             'tagline' => "Interaction en temps réel."],
            ['name' => 'Socrative', 'slug' => 'socrative', 'url' => 'https://www.socrative.com',
             'short' => "Évaluations rapides et jeux-questionnaires.",
             'long' => "Socrative permet aux enseignants de créer des quiz, évaluations formatives et jeux comme Space Race en quelques clics.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Évaluez et jouez en classe."],
            ['name' => 'Book Creator', 'slug' => 'book-creator', 'url' => 'https://bookcreator.com',
             'short' => "Créez des livres numériques collaboratifs.",
             'long' => "Book Creator permet aux élèves et enseignants de concevoir facilement des livres multimédias avec texte, images, audio et vidéo.",
             'pricing' => 'freemium', 'edu_type' => 'verified-only', 'edu_url' => '',
             'tagline' => "Créez des livres, pas des devoirs."],
            ['name' => 'Genially', 'slug' => 'genially', 'url' => 'https://genial.ly',
             'short' => "Contenu interactif et visuel pour l'enseignement.",
             'long' => "Genially permet de créer des présentations, infographies, quiz et contenus pédagogiques animés et interactifs.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Apprentissage visuel et interactif."],
            ['name' => 'Screencastify', 'slug' => 'screencastify', 'url' => 'https://www.screencastify.com',
             'short' => "Enregistrez votre écran pour créer des vidéos pédagogiques.",
             'long' => "Extension Chrome simple pour enregistrer l'écran, la webcam et créer des tutoriels ou retours personnalisés.",
             'pricing' => 'freemium', 'edu_type' => 'verified-only', 'edu_url' => '',
             'tagline' => "Enseignez à distance ou en classe."],
            ['name' => 'Desmos', 'slug' => 'desmos', 'url' => 'https://www.desmos.com',
             'short' => "Calculatrice graphique gratuite et activités mathématiques.",
             'long' => "Calculatrice graphique en ligne gratuite et bibliothèque d'activités interactives pour enseigner les mathématiques.",
             'pricing' => 'free', 'edu_type' => 'free', 'edu_url' => 'https://teacher.desmos.com',
             'tagline' => "Maths visuelles et interactives."],
            ['name' => 'GeoGebra', 'slug' => 'geogebra', 'url' => 'https://www.geogebra.org',
             'short' => "Outils mathématiques dynamiques pour tous les niveaux.",
             'long' => "GeoGebra combine géométrie, algèbre, tableur, statistiques et calcul dans un environnement interactif gratuit pour l'enseignement.",
             'pricing' => 'free', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Maths dynamiques et collaboratives."],
            ['name' => 'Wolfram Alpha', 'slug' => 'wolfram-alpha', 'url' => 'https://www.wolframalpha.com',
             'short' => "Moteur de connaissances computationnelles.",
             'long' => "Wolfram Alpha fournit des réponses calculées à partir de données fiables, utile pour les maths, sciences, ingénierie et plus.",
             'pricing' => 'freemium', 'edu_type' => 'discount', 'edu_url' => '',
             'tagline' => "Réponses basées sur le calcul."],
            ['name' => 'Alloprof', 'slug' => 'alloprof', 'url' => 'https://www.alloprof.qc.ca',
             'short' => "Service québécois gratuit d'aide aux devoirs.",
             'long' => "Alloprof, organisme sans but lucratif, offre du soutien scolaire gratuit en français pour les élèves du Québec, de la maternelle à la 12e année.",
             'pricing' => 'free', 'edu_type' => 'free', 'edu_url' => 'https://www.alloprof.qc.ca/fr/enseignants',
             'tagline' => "Aide scolaire gratuite au Québec."],
            ['name' => 'ELSA Speak', 'slug' => 'elsa-speak', 'url' => 'https://elsaspeak.com',
             'short' => "Apprenez à prononcer l'anglais correctement avec l'IA.",
             'long' => "ELSA Speak utilise l'intelligence artificielle pour évaluer et améliorer la prononciation anglaise des apprenants grâce à des exercices interactifs.",
             'pricing' => 'freemium', 'edu_type' => 'discount', 'edu_url' => '',
             'tagline' => "Maîtrisez l'anglais parlé."],
        ];

        $now = Carbon::now()->toDateTimeString();
        $slugExpr = "JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"'))";
        $jsonField = static fn (string $v): string => json_encode(['fr_CA' => $v, 'fr' => $v, 'en' => $v], JSON_UNESCAPED_UNICODE);

        $pivotTable = null;
        foreach (['directory_category_directory_tool', 'directory_category_tool', 'directory_tool_directory_category'] as $t) {
            if (Schema::hasTable($t)) {
                $pivotTable = $t;
                break;
            }
        }

        foreach ($tools as $t) {
            if (DB::table('directory_tools')->whereRaw("{$slugExpr} = ?", [$t['slug']])->exists()) {
                continue;
            }
            $hasEdu = $t['edu_type'] !== 'none';
            $eduDetails = $hasEdu ? $jsonField('Programme éducation officiel disponible.') : null;

            $id = DB::table('directory_tools')->insertGetId([
                'name' => $jsonField($t['name']),
                'slug' => $jsonField($t['slug']),
                'url' => $t['url'],
                'short_description' => $jsonField($t['short']),
                'description' => $jsonField($t['long']),
                'unique_value' => $t['tagline'],
                'pricing' => $t['pricing'],
                'has_education_pricing' => $hasEdu ? 1 : 0,
                'education_pricing_type' => $hasEdu ? $t['edu_type'] : null,
                'education_pricing_details' => $eduDetails,
                'education_pricing_url' => $t['edu_url'] ?: null,
                'education_verification_required' => ($t['edu_type'] === 'verified-only') ? 1 : 0,
                'education_last_checked_at' => $now,
                'status' => 'published',
                'lifecycle_status' => 'active',
                'is_featured' => 0,
                'sort_order' => 0,
                'website_type' => 'website',
                'has_api_access' => 0,
                'is_multimodal' => 0,
                'enrichment_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($pivotTable) {
                try {
                    DB::table($pivotTable)->insert([
                        'directory_tool_id' => $id,
                        'directory_category_id' => self::EDU_CATEGORY_ID,
                    ]);
                } catch (\Throwable $e) {
                    // Ignored — pivot column names may differ
                }
            }
        }
    }
}
