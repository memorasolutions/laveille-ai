<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\CertificateService;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\EnrollmentService;
use Modules\Academy\Services\ProgressService;
use Spatie\Permission\Models\Role;

/**
 * Seeder de DÉMONSTRATION complet de l'Académie.
 *
 * Objectif : permettre d'essayer TOUTES les fonctionnalités sans exception
 * (catalogue, fiche cours, lecteur de leçon avec items document/vidéo/quiz,
 * inscription gratuite, progression, certificat, brouillon, cours payant).
 *
 * Caractéristiques :
 *   - IDEMPOTENT : firstOrCreate / updateOrCreate partout, ré-exécutable sans doublon.
 *   - ADDITIF : ne supprime jamais rien (aucun delete, aucun truncate).
 *   - RÉVERSIBLE : toutes les données portent le préfixe « demo- » (slugs) ou
 *     « .demo@laveille.ai » (courriels) ; supprimables manuellement sans risque.
 *
 * ─────────────────────────────────────────────────────────────────────────
 *  IDENTIFIANTS DE DÉMO (à communiquer à l'utilisateur, mots de passe en clair) :
 *
 *    Formateur démo  : formateur.demo@laveille.ai  /  Demo-Academie-2026!
 *    Étudiant démo   : etudiant.demo@laveille.ai   /  Demo-Academie-2026!
 *
 *  Le formateur a le rôle Spatie « instructor » ; l'étudiant a le rôle « student ».
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Exécution :
 *   php artisan db:seed --class="Modules\Academy\Database\Seeders\AcademyDemoSeeder"
 */
class AcademyDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Demo-Academie-2026!';

    private const COURSE_FREE_SLUG = 'demo-decouvrir-ia';

    private const COURSE_PAID_SLUG = 'demo-atelier-ia-avance';

    public function run(): void
    {
        // 1. Garantir les rôles instructor + student (et toutes les permissions Académie).
        //    AcademyPermissionsSeeder est lui-même idempotent (firstOrCreate).
        $this->call(AcademyPermissionsSeeder::class);

        // 2. Utilisateurs de démo (idempotents).
        $instructor = $this->ensureDemoUser(
            email: 'formateur.demo@laveille.ai',
            name: 'Formateur DEMO',
            role: 'instructor',
        );

        $student = $this->ensureDemoUser(
            email: 'etudiant.demo@laveille.ai',
            name: 'Étudiant DEMO',
            role: 'student',
        );

        // 3. Cours principal publié et gratuit + structure complète.
        $course = $this->seedMainCourse($instructor);

        // 4. Cours secondaire en brouillon et payant.
        $this->seedDraftPaidCourse($instructor);

        // 5. Inscription de l'étudiant + progression partielle + (si 100 %) certificat.
        $this->enrollAndProgress($student, $course);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Utilisateurs
    // ──────────────────────────────────────────────────────────────────────

    private function ensureDemoUser(string $email, string $name, string $role): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        // Garantir le rôle Spatie (additif : assignRole n'écrase rien d'autre).
        if (class_exists(Role::class)) {
            try {
                Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
                if (! $user->hasRole($role)) {
                    $user->assignRole($role);
                }
            } catch (\Throwable) {
                // Ne jamais bloquer le seeding pour un souci de rôle.
            }
        }

        return $user;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Cours principal : publié, gratuit, structure riche
    // ──────────────────────────────────────────────────────────────────────

    private function seedMainCourse(User $instructor): Course
    {
        $course = Course::firstOrCreate(
            ['slug' => self::COURSE_FREE_SLUG],
            [
                'title'            => "Découvrir l'IA au Québec",
                'subtitle'         => "Un premier pas concret dans l'intelligence artificielle",
                'summary'          => "Parcours d'introduction gratuit pour comprendre ce qu'est l'IA générative, l'essayer en toute sécurité et connaître le cadre québécois (Loi 25).",
                'description'      => "Cette formation gratuite s'adresse à toute personne curieuse de l'intelligence artificielle, sans prérequis technique. En quelques leçons courtes, tu découvriras ce qu'est réellement l'IA générative, comment elle fonctionne, comment l'utiliser de façon responsable et ce que dit la loi québécoise sur la protection des renseignements personnels.\n\nAu programme : un document pédagogique de mise en contexte, un quiz pour valider tes acquis, une leçon vidéo de démonstration, puis un approfondissement sur les bonnes pratiques. À la fin, tu obtiens un certificat de réussite.",
                'language'         => 'fr-CA',
                'level'            => 'intro',
                'visibility'       => 'public',
                'access_type'      => 'free',
                'status'           => 'published',
                'published_at'     => now(),
                'duration_minutes' => 45,
                'created_by'       => $instructor->id,
            ]
        );

        // Le formateur devient owner du cours (idempotent).
        CourseRole::firstOrCreate([
            'course_id' => $course->id,
            'user_id'   => $instructor->id,
            'role'      => 'owner',
        ]);

        // Ne (re)construire la structure que si le cours n'a pas encore de chapitre,
        // afin de rester idempotent sans dépendre de slugs de leçon globalement uniques.
        if ($course->chapters()->exists()) {
            return $course;
        }

        // ── Chapitre 1 : Les bases ──────────────────────────────────────────
        $ch1 = Chapter::create([
            'course_id' => $course->id,
            'title'     => 'Les bases',
            'position'  => 0,
            'summary'   => "Comprendre ce qu'est l'IA et la mettre à l'épreuve avec un quiz.",
        ]);

        // Leçon 1.1 : document + quiz
        $l11 = Lesson::create([
            'chapter_id'        => $ch1->id,
            'title'             => "Qu'est-ce que l'intelligence artificielle ?",
            'slug'              => 'qu-est-ce-que-l-ia',
            'position'          => 0,
            'summary'           => "Une définition simple, des exemples concrets et un premier quiz.",
            'estimated_minutes' => 12,
        ]);

        LessonItem::create([
            'lesson_id'         => $l11->id,
            'type'              => 'document',
            'title'             => "L'IA générative expliquée simplement",
            'position'          => 0,
            'is_required'       => true,
            'estimated_minutes' => 7,
            'payload'           => [
                'rich_text' => "L'intelligence artificielle générative est une famille d'outils capables de produire du texte, des images ou du code à partir d'une simple consigne écrite en langage courant. Contrairement à un moteur de recherche qui retrouve des pages existantes, un modèle génératif compose une réponse nouvelle, mot après mot, en s'appuyant sur des régularités apprises dans d'immenses corpus de textes.\n\nConcrètement, quand tu écris une demande (on parle de « prompt »), le modèle prédit la suite la plus plausible. C'est puissant pour rédiger un brouillon, résumer un document ou expliquer un concept, mais cela ne garantit jamais l'exactitude : un modèle peut inventer une référence ou se tromper avec aplomb. La règle d'or est donc de toujours vérifier les informations importantes et de ne jamais déposer de renseignements personnels ou confidentiels dans un outil grand public.\n\nAu Québec, la Loi 25 encadre la collecte et l'usage des renseignements personnels. Avant d'utiliser un outil d'IA dans un contexte professionnel, demande-toi toujours : est-ce que je partage des données que je ne devrais pas ? Cette vigilance, conjuguée à un esprit critique, transforme l'IA en véritable alliée plutôt qu'en risque.",
                'attachments' => [],
            ],
        ]);

        LessonItem::create([
            'lesson_id'         => $l11->id,
            'type'              => 'quiz',
            'title'             => "Quiz : valide tes acquis sur l'IA",
            'position'          => 1,
            'is_required'       => true,
            'estimated_minutes' => 5,
            'external_ref'      => 'qt-questions',
            'payload'           => [
                // QtService ignore qt_bank_key et bâtit le round via QtService::newRound()
                // (banque qt-questions + vrai/faux + réponse courte). Quiz JOUABLE tel quel.
                'qt_bank_key'      => 'qt-questions',
                'passing_score'    => 50,
                'attempts_allowed' => 5,
            ],
        ]);

        // Leçon 1.2 : vidéo (ScreenPal)
        $l12 = Lesson::create([
            'chapter_id'        => $ch1->id,
            'title'             => "Démonstration : ta première conversation avec une IA",
            'slug'              => 'premiere-conversation-ia',
            'position'          => 1,
            'summary'           => "Une courte vidéo pour voir l'IA générative en action.",
            'estimated_minutes' => 8,
        ]);

        LessonItem::create([
            'lesson_id'         => $l12->id,
            'type'              => 'video',
            'title'             => "Vidéo de démonstration (ScreenPal)",
            'position'          => 0,
            'is_required'       => true,
            'estimated_minutes' => 6,
            'external_ref'      => 'sp-demo-placeholder-001',
            'payload'           => [
                // Vidéo ScreenPal, URL d'intégration /player/… (pas /watch/…) pour respecter
                // le verrou de domaine — voir video-player.blade.php. Domaine générique
                // go.screenpal.com (aucune marque blanche, zéro dépendance CNAME/SSL tierce).
                'player_url'       => 'https://go.screenpal.com/player/cOn6rBn0hMl?ff=1&ahc=1&dcc=1&tl=1&bg=transparent&share=0&download=0&embed=1&cl=1',
                'duration_seconds' => 360,
                'domain_lock'      => true,
                'aspect_ratio'     => 1.320866,
            ],
        ]);

        // ── Chapitre 2 : Aller plus loin ────────────────────────────────────
        $ch2 = Chapter::create([
            'course_id' => $course->id,
            'title'     => 'Aller plus loin',
            'position'  => 1,
            'summary'   => "Bonnes pratiques et validation finale.",
        ]);

        // Leçon 2.1 : document + quiz
        $l21 = Lesson::create([
            'chapter_id'        => $ch2->id,
            'title'             => "Utiliser l'IA de façon responsable",
            'slug'              => 'utiliser-ia-responsable',
            'position'          => 0,
            'summary'           => "Cinq réflexes pour tirer le meilleur de l'IA en toute sécurité.",
            'estimated_minutes' => 10,
        ]);

        LessonItem::create([
            'lesson_id'         => $l21->id,
            'type'              => 'document',
            'title'             => "Cinq réflexes pour une IA responsable",
            'position'          => 0,
            'is_required'       => true,
            'estimated_minutes' => 6,
            'payload'           => [
                'rich_text' => "Adopter l'IA générative au quotidien demande quelques réflexes simples mais essentiels.\n\nPremièrement, formule des consignes claires et précises : plus ton prompt est explicite (rôle, contexte, format attendu), meilleure sera la réponse. Deuxièmement, vérifie systématiquement les faits, les chiffres et les citations : un modèle peut produire des affirmations fausses avec une grande assurance. Troisièmement, ne partage jamais de renseignements personnels, médicaux, financiers ou confidentiels dans un outil grand public ; au besoin, anonymise tes données avant de les soumettre.\n\nQuatrièmement, garde toujours la main : l'IA propose, c'est toi qui décides. Relis, corrige et assume le résultat final. Cinquièmement, reste transparent sur ton usage de l'IA quand le contexte l'exige, en particulier dans un cadre professionnel ou pédagogique. Ces cinq réflexes, conjugués au respect de la Loi 25 sur la protection des renseignements personnels, te permettent d'exploiter l'IA comme un véritable accélérateur tout en maîtrisant les risques.",
                'attachments' => [],
            ],
        ]);

        LessonItem::create([
            'lesson_id'         => $l21->id,
            'type'              => 'quiz',
            'title'             => "Quiz final : prêt à utiliser l'IA ?",
            'position'          => 1,
            'is_required'       => true,
            'estimated_minutes' => 5,
            'external_ref'      => 'qt-questions',
            'payload'           => [
                'qt_bank_key'      => 'qt-questions',
                'passing_score'    => 60,
                'attempts_allowed' => 3,
            ],
        ]);

        return $course;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Cours secondaire : brouillon, payant
    // ──────────────────────────────────────────────────────────────────────

    private function seedDraftPaidCourse(User $instructor): Course
    {
        $course = Course::firstOrCreate(
            ['slug' => self::COURSE_PAID_SLUG],
            [
                'title'            => "Atelier IA avancé",
                'subtitle'         => "Maîtriser le prompting et automatiser ses tâches",
                'summary'          => "Atelier de démonstration en BROUILLON et PAYANT, pour tester la variété de l'éditeur et du catalogue (paiement unique).",
                'description'      => "Cet atelier avancé (cours de démonstration en brouillon) approfondit les techniques de prompting, l'enchaînement de tâches et l'automatisation assistée par IA. Il sert ici à illustrer un cours payant non publié dans l'éditeur et le catalogue.",
                'language'         => 'fr-CA',
                'level'            => 'avance',
                'visibility'       => 'public',
                'access_type'      => 'paid_one_time',
                'price_cents'      => 4900,
                'currency'         => 'CAD',
                'status'           => 'draft',
                'published_at'     => null,
                'duration_minutes' => 120,
                'created_by'       => $instructor->id,
            ]
        );

        CourseRole::firstOrCreate([
            'course_id' => $course->id,
            'user_id'   => $instructor->id,
            'role'      => 'owner',
        ]);

        if ($course->chapters()->exists()) {
            return $course;
        }

        $ch = Chapter::create([
            'course_id' => $course->id,
            'title'     => "Prompting avancé",
            'position'  => 0,
            'summary'   => "Techniques pour des résultats fiables et reproductibles.",
        ]);

        $lesson = Lesson::create([
            'chapter_id'        => $ch->id,
            'title'             => "Structurer un prompt professionnel",
            'slug'              => 'structurer-prompt-pro',
            'position'          => 0,
            'summary'           => "La méthode rôle-contexte-tâche-format.",
            'estimated_minutes' => 15,
        ]);

        LessonItem::create([
            'lesson_id'         => $lesson->id,
            'type'              => 'document',
            'title'             => "La méthode RCTF",
            'position'          => 0,
            'is_required'       => true,
            'estimated_minutes' => 8,
            'payload'           => [
                'rich_text' => "Un prompt professionnel suit une structure éprouvée : Rôle, Contexte, Tâche, Format. Tu indiques d'abord à l'IA quel rôle adopter (par exemple « tu es un réviseur linguistique »), puis tu fournis le contexte utile, tu décris précisément la tâche, et tu spécifies le format de sortie attendu. Cette discipline rend les résultats nettement plus fiables et reproductibles.",
                'attachments' => [],
            ],
        ]);

        return $course;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Inscription + progression partielle
    // ──────────────────────────────────────────────────────────────────────

    private function enrollAndProgress(User $student, Course $course): void
    {
        // Inscription gratuite via le service métier (idempotent : firstOrCreate interne).
        try {
            app(EnrollmentService::class)->enrollFree($student, $course);
        } catch (\Throwable) {
            // Repli défensif : inscription directe cohérente avec EnrollmentService.
            Enrollment::firstOrCreate(
                ['user_id' => $student->id, 'course_id' => $course->id],
                ['status' => 'active', 'source' => 'free', 'enrolled_at' => now()]
            );
        }

        // Progression PARTIELLE : compléter le premier item requis (document de la leçon 1.1)
        // pour afficher une barre de progression non nulle mais < 100 %.
        $firstRequiredItem = LessonItem::whereHas(
            'lesson.chapter',
            fn ($q) => $q->where('course_id', $course->id)
        )
            ->where('is_required', true)
            ->where('type', 'document')
            ->orderBy('id')
            ->first();

        if ($firstRequiredItem !== null) {
            // CompletionService est idempotent (ne recrée pas si déjà 'completed')
            // et recalcule la progression via ProgressService automatiquement.
            CompletionService::markComplete($student, $firstRequiredItem, 100);
        }

        // Garantir l'existence d'une ligne de progression (au cas où aucun item requis).
        if (class_exists(ProgressService::class)) {
            try {
                ProgressService::recalculate($student, $course);
            } catch (\Throwable) {
                // Silencieux.
            }
        }

        // Note : le CERTIFICAT s'obtient automatiquement à 100 % (ProgressService
        // appelle CertificateService::issueFor). Ici la progression est volontairement
        // PARTIELLE, donc aucun certificat n'est émis : l'utilisateur le déclenchera
        // en complétant tous les items requis du cours (les 2 quiz + la vidéo + le 2e doc),
        // ce qui démontre le parcours certifiant de bout en bout.
    }
}
