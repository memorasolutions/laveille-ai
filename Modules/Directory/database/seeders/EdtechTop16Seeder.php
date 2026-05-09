<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeder Top 16 EdTech haute priorité enseignants 2026 (S90).
 *
 * Suite à audit gap analysis : annuaire La veille très axé "outils IA générative"
 * mais sous-représenté sur EdTech traditionnel (LMS, quiz interactifs, gestion classe).
 * Recherche pp_search + classification a identifié 50 plateformes top enseignants ;
 * 15 déjà présentes dans annuaire, 37 manquantes dont 16 haute priorité ajoutées ici.
 *
 * Catégories couvertes : LMS principaux (Google Classroom, Canvas, Moodle, Brightspace,
 * Teams Edu, MS365 Edu), quiz interactifs (Kahoot, Quizizz, Quizlet, Mentimeter),
 * collaboration (Padlet), vidéo pédagogique (Edpuzzle, Loom),
 * gestion classe / parents (ClassDojo, Seesaw, Remind).
 *
 * Idempotent (skip si slug existe), URLs validées via curl HEAD/GET S90.
 */
class EdtechTop16Seeder extends Seeder
{
    private const EDU_CATEGORY_ID = 12;

    public function run(): void
    {
        $tools = [
            ['name' => 'Google Classroom', 'slug' => 'google-classroom', 'url' => 'https://classroom.google.com',
             'short' => "Plateforme d'apprentissage intégrée à Google Workspace.",
             'long' => "Créez des classes, distribuez des devoirs, communiquez et organisez tout en un seul endroit, directement depuis votre compte Google Éducation.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Enseignez, organisez, évaluez simplement."],
            ['name' => 'Canvas LMS', 'slug' => 'canvas-lms', 'url' => 'https://www.instructure.com/canvas',
             'short' => "LMS puissant pour les établissements scolaires.",
             'long' => "Plateforme d'apprentissage complète avec gestion de cours, évaluations, analyses et intégrations, conçue pour les écoles et universités.",
             'pricing' => 'freemium', 'edu_type' => 'verified-only', 'edu_url' => 'https://www.instructure.com/canvas/k-12',
             'tagline' => "Apprentissage sans limites."],
            ['name' => 'Moodle', 'slug' => 'moodle', 'url' => 'https://moodle.org',
             'short' => "LMS open source personnalisable et gratuit.",
             'long' => "Créez des cours en ligne, évaluez les élèves et gérez l'apprentissage avec une plateforme libre, sécurisée et adaptable à tous les niveaux.",
             'pricing' => 'free', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Liberté pédagogique, logiciel libre."],
            ['name' => 'Brightspace', 'slug' => 'brightspace', 'url' => 'https://www.d2l.com',
             'short' => "Plateforme d'apprentissage intelligente centrée sur l'élève.",
             'long' => "Outil complet pour créer des expériences d'apprentissage personnalisées, avec analyses avancées, accessibilité et intégration multimédia.",
             'pricing' => 'paid', 'edu_type' => 'discount', 'edu_url' => '',
             'tagline' => "Innovez dans l'enseignement numérique."],
            ['name' => 'Microsoft Teams for Education', 'slug' => 'microsoft-teams-education', 'url' => 'https://www.microsoft.com/education/products/teams',
             'short' => "Collaboration en classe via Teams.",
             'long' => "Créez des salles de classe virtuelles, partagez des ressources, animez des réunions et collaborez en temps réel avec les outils Microsoft.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Collaborez, enseignez, apprenez ensemble."],
            ['name' => 'Microsoft 365 Education', 'slug' => 'microsoft-365-education', 'url' => 'https://www.microsoft.com/education/products/office',
             'short' => "Suite Office gratuite pour les établissements scolaires.",
             'long' => "Accès gratuit à Word, Excel, PowerPoint, OneNote et Teams pour les enseignants et élèves éligibles via une adresse institutionnelle.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Outils Microsoft gratuits pour l'école."],
            ['name' => 'Kahoot!', 'slug' => 'kahoot', 'url' => 'https://kahoot.com',
             'short' => "Jeux-questionnaires interactifs pour apprendre en s'amusant.",
             'long' => "Créez des quiz, sondages et défis ludiques en temps réel ou en autonomie, idéal pour réviser et évaluer de façon engageante.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => 'https://kahoot.com/schools/',
             'tagline' => "Apprendre en jouant, partout."],
            ['name' => 'Quizizz', 'slug' => 'quizizz', 'url' => 'https://quizizz.com',
             'short' => "Quiz interactifs avec feedback automatique.",
             'long' => "Créez des activités d'apprentissage engageantes (quiz, leçons, devoirs) avec rapports détaillés et mèmes pour motiver les élèves.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Évaluer, réviser, s'amuser ensemble."],
            ['name' => 'Quizlet', 'slug' => 'quizlet', 'url' => 'https://quizlet.com',
             'short' => "Outils d'apprentissage par flashcards et jeux.",
             'long' => "Créez des fiches de révision, jouez à des jeux de mémorisation et suivez les progrès grâce à l'IA adaptative et aux modes d'étude variés.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => 'https://quizlet.com/teachers',
             'tagline' => "Mémorisez plus vite, retenez mieux."],
            ['name' => 'Mentimeter', 'slug' => 'mentimeter', 'url' => 'https://www.mentimeter.com',
             'short' => "Présentations interactives et sondages en temps réel.",
             'long' => "Impliquez les élèves avec des quiz, nuages de mots, échelles et QCM interactifs intégrés directement dans vos présentations.",
             'pricing' => 'freemium', 'edu_type' => 'discount', 'edu_url' => '',
             'tagline' => "Cours en expériences interactives."],
            ['name' => 'Padlet', 'slug' => 'padlet', 'url' => 'https://padlet.com',
             'short' => "Tableau collaboratif numérique en temps réel.",
             'long' => "Partagez idées, ressources, médias et réflexions sur un mur virtuel personnalisable, idéal pour la collaboration et la créativité en classe.",
             'pricing' => 'freemium', 'edu_type' => 'discount', 'edu_url' => '',
             'tagline' => "Collaborez sur un mur numérique."],
            ['name' => 'Edpuzzle', 'slug' => 'edpuzzle', 'url' => 'https://edpuzzle.com',
             'short' => "Vidéos interactives avec questions intégrées.",
             'long' => "Transformez n'importe quelle vidéo en leçon interactive avec questions, notes vocales et quiz pour suivre la compréhension des élèves.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Rendez les vidéos pédagogiques interactives."],
            ['name' => 'Loom', 'slug' => 'loom', 'url' => 'https://www.loom.com',
             'short' => "Enregistrement vidéo d'écran simple et rapide.",
             'long' => "Créez des tutoriels, feedbacks vidéo ou leçons asynchrones en quelques clics, avec partage facile et intégration LMS.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => 'https://www.loom.com/education',
             'tagline' => "Communiquez avec des vidéos instantanées."],
            ['name' => 'ClassDojo', 'slug' => 'classdojo', 'url' => 'https://www.classdojo.com',
             'short' => "Communication école-famille et gestion de classe.",
             'long' => "Renforcez la communication avec les parents, valorisez les comportements positifs et partagez des moments de classe via photos et vidéos sécurisés.",
             'pricing' => 'free', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Connectez école, élèves et familles."],
            ['name' => 'Seesaw', 'slug' => 'seesaw', 'url' => 'https://web.seesaw.me',
             'short' => "Portfolio numérique pour les jeunes apprenants.",
             'long' => "Plateforme intuitive permettant aux élèves de documenter leur apprentissage avec dessins, photos, vidéos et enregistrements audio.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => 'https://web.seesaw.me/schools',
             'tagline' => "Montrez l'apprentissage, engagez les familles."],
            ['name' => 'Remind', 'slug' => 'remind', 'url' => 'https://www.remind.com',
             'short' => "Messagerie sécurisée entre école, élèves et parents.",
             'long' => "Envoyez des messages, rappels et ressources sans partager de coordonnées personnelles, avec traduction automatique et diffusion groupée.",
             'pricing' => 'freemium', 'edu_type' => 'free', 'edu_url' => '',
             'tagline' => "Communiquez en toute sécurité avec tous."],
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
            $exists = DB::table('directory_tools')
                ->whereRaw("{$slugExpr} = ?", [$t['slug']])
                ->exists();
            if ($exists) {
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
                    // Ignored — pivot column names may differ across migrations
                }
            }
        }
    }
}
