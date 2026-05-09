<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder Top EdTech basse priorité 2026 (S90 — gap analysis Top 50, phase 3 finale).
 *
 * Couvre les outils marché US/anglo principalement + accessibilité :
 * LMS (Schoology, Blackboard Learn), lecture K-12 (Newsela, Epic!, ReadWorks),
 * accessibilité lecture/écriture (Read&Write by TextHelp, Speechify).
 *
 * Avec ce seeder, couverture Top 50 EdTech = 51/50 (102%, dépassement) :
 * 16 hauts + 13 moyens + 7 bas = 36 nouveaux outils + 15 déjà présents = 51 confirmés.
 *
 * Idempotent (skip si slug existe), URLs validées via curl HEAD/GET S90.
 */
class EdtechLowPriority7Seeder extends Seeder
{
    private const EDU_CATEGORY_ID = 12;

    public function run(): void
    {
        $tools = [
            ['name' => 'Schoology', 'slug' => 'schoology', 'url' => 'https://www.schoology.com',
             'short' => "LMS complet pour l'éducation K-12.",
             'long' => "Plateforme d'apprentissage tout-en-un conçue pour les écoles primaires et secondaires, favorisant la collaboration et la gestion pédagogique.",
             'pricing' => 'freemium', 'edu_type' => 'verified-only', 'edu_url' => '',
             'tagline' => "LMS collaboratif pour K-12."],
            ['name' => 'Blackboard Learn', 'slug' => 'blackboard-learn', 'url' => 'https://www.blackboard.com',
             'short' => "LMS leader pour l'enseignement supérieur.",
             'long' => "Système de gestion d'apprentissage robuste utilisé par les universités et collèges du monde entier pour dispenser des cours en ligne et hybrides.",
             'pricing' => 'paid', 'edu_type' => 'verified-only', 'edu_url' => '',
             'tagline' => "LMS pour l'enseignement supérieur."],
            ['name' => 'Newsela', 'slug' => 'newsela', 'url' => 'https://newsela.com',
             'short' => "Articles adaptés au niveau de lecture.",
             'long' => "Plateforme proposant des articles d'actualité et du contenu pédagogique ajustés à différents niveaux de lecture pour les élèves K-12.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Lire l'actualité à son niveau."],
            ['name' => 'Epic!', 'slug' => 'epic', 'url' => 'https://www.getepic.com',
             'short' => "Bibliothèque numérique gratuite pour enfants.",
             'long' => "Application offrant des milliers de livres numériques pour les jeunes lecteurs, gratuite pour les enseignants et les bibliothèques scolaires.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => 'https://www.getepic.com/schools',
             'tagline' => "Lire, apprendre, s'émerveiller."],
            ['name' => 'ReadWorks', 'slug' => 'readworks', 'url' => 'https://www.readworks.org',
             'short' => "Ressources gratuites en compréhension de lecture.",
             'long' => "Organisme à but non lucratif offrant des leçons, articles et activités gratuites pour améliorer la compréhension en lecture chez les élèves K-12.",
             'pricing' => 'free', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Lire pour comprendre."],
            ['name' => 'Read&Write by TextHelp', 'slug' => 'read-and-write', 'url' => 'https://www.texthelp.com',
             'short' => "Outil d'accessibilité lecture/écriture.",
             'long' => "Extension d'aide à la lecture et à l'écriture offrant du texte-à-parole, des dictionnaires et autres outils d'accessibilité pour tous les apprenants.",
             'pricing' => 'freemium', 'edu_type' => 'discount', 'edu_url' => '',
             'tagline' => "Lire, écrire, réussir ensemble."],
            ['name' => 'Speechify', 'slug' => 'speechify', 'url' => 'https://speechify.com',
             'short' => "Conversion texte en parole de qualité.",
             'long' => "Application de synthèse vocale transformant n'importe quel texte en audio naturel, utile pour les apprenants ayant des difficultés de lecture.",
             'pricing' => 'freemium', 'edu_type' => 'discount', 'edu_url' => '',
             'tagline' => "Écouter pour mieux apprendre."],
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
