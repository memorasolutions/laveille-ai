<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F15 SAUVEGARDE / RESTAURATION / IMPORT de cours.
 *
 * Prouve que :
 *  - export() produit une structure versionnée contenant l'arbre + les payloads
 *    COMPLETS, les devoirs/grilles, le carnet et la banque référencée ;
 *  - export() EXCLUT toute donnée d'étudiant (inscriptions, rôles, tentatives...) ;
 *  - import() crée un NOUVEAU cours (brouillon, owner = importateur, slug unique) ;
 *  - REMAP correct des références internes : access_restrictions.item_id et
 *    grade_items.item_id pointent vers les NOUVEAUX items (pas les anciens),
 *    question_bank.category_id vers la NOUVELLE catégorie, completion_criteria.items
 *    remappé ;
 *  - un JSON invalide / un format inconnu est rejeté proprement (exception métier) ;
 *  - anti-IDOR : exporter un cours qu'on ne gère pas = 403 ;
 *  - rétrocompatibilité : un item sans access_restrictions reste inchangé.
 *
 * Fichier AUTONOME : helpers locaux préfixés Bk (aucune collision inter-fichiers).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Exceptions\InvalidCourseBackupException;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\GradeCategory;
use Modules\Academy\Models\GradeItem;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\RubricCriterion;
use Modules\Academy\Models\RubricLevel;
use Modules\Academy\Services\CourseBackupService;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

/** Cours gratuit en brouillon. */
function makeBkCourse(string $slug, string $title): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => $title,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);
}

/** Formateur owner du cours. */
function makeBkOwner(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $user->id, 'role' => 'owner']);

    return $user;
}

/** Importateur (formateur SANS rôle sur le cours source). */
function makeBkInstructor(): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');

    return $user;
}

/**
 * Peuple un cours « riche » : 2 items quiz/document avec restriction d'accès
 * (item_id) + lien de banque + un devoir avec grille + carnet de notes lié au quiz +
 * completion_criteria ciblant un item. Retourne les ids des entités clés.
 *
 * @return array<string, int>
 */
function seedBkRichCourse(Course $course): array
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre 1', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'Leçon 1', 'slug' => 'bk-lecon-1', 'position' => 1]);

    // Banque de questions du formateur.
    $owner = makeBkOwner($course);
    $bankCat = QuestionCategory::create(['owner_id' => $owner->id, 'parent_id' => null, 'name' => 'Banque module 1', 'position' => 0]);
    Question::create([
        'category_id' => $bankCat->id, 'owner_id' => $owner->id, 'type' => 'truefalse',
        'prompt' => 'L\'IA est un outil ?', 'payload' => ['answer' => true], 'points' => 1, 'is_active' => true,
    ]);

    // Item A = quiz (référencé par la restriction + le carnet + completion_criteria).
    $quiz = LessonItem::create([
        'lesson_id' => $lesson->id, 'type' => 'quiz', 'title' => 'Quiz intro', 'position' => 1,
        'payload' => [
            'qt_bank_key' => 'qt.module1',
            'passing_score' => 70,
            'question_bank' => ['category_id' => $bankCat->id, 'draw_count' => 3, 'include_subcategories' => true],
        ],
    ]);

    // Item B = document VERROUILLÉ tant que le quiz A n'a pas 80 % (restriction item_id = A).
    $doc = LessonItem::create([
        'lesson_id' => $lesson->id, 'type' => 'document', 'title' => 'Suite', 'position' => 2,
        'payload' => [
            'rich_text' => 'Contenu.',
            'access_restrictions' => [
                'match' => 'all',
                'conditions' => [
                    ['type' => 'grade', 'item_id' => $quiz->id, 'min_percent' => 80, 'hide' => false],
                    ['type' => 'group', 'group_id' => 999, 'hide' => false], // sera retiré (cohorte non exportée)
                ],
            ],
        ],
    ]);

    // Devoir + grille.
    $assignment = Assignment::create([
        'course_id' => $course->id, 'lesson_id' => $lesson->id, 'title' => 'Travail final',
        'max_points' => 100, 'is_published' => true, 'position' => 1,
    ]);
    $criterion = RubricCriterion::create(['assignment_id' => $assignment->id, 'description' => 'Clarté', 'position' => 0]);
    RubricLevel::create(['criterion_id' => $criterion->id, 'description' => 'Excellent', 'points' => 10, 'position' => 0]);

    // Carnet : une catégorie + un grade_item lié au quiz (item_type='quiz') et un autre au devoir.
    $gradeCat = GradeCategory::create(['course_id' => $course->id, 'name' => 'Évaluations', 'weight' => 100, 'position' => 0]);
    GradeItem::create(['course_id' => $course->id, 'item_type' => 'quiz', 'item_id' => $quiz->id, 'grade_category_id' => $gradeCat->id, 'weight' => 1, 'position' => 0]);
    GradeItem::create(['course_id' => $course->id, 'item_type' => 'assignment', 'item_id' => $assignment->id, 'grade_category_id' => $gradeCat->id, 'weight' => 2, 'position' => 1]);

    // Critère de complétion du cours ciblant l'item quiz.
    $course->update(['completion_criteria' => ['type' => 'selected_activities', 'items' => [$quiz->id]]]);

    return [
        'owner_id'    => $owner->id,
        'quiz_id'     => $quiz->id,
        'doc_id'      => $doc->id,
        'assignment_id' => $assignment->id,
        'bank_cat_id' => $bankCat->id,
        'grade_cat_id' => $gradeCat->id,
    ];
}

/** Items d'un cours (toutes leçons), indexés par titre. */
function bkItemsByTitle(Course $course): \Illuminate\Support\Collection
{
    return LessonItem::whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $course->id))
        ->get()->keyBy('title');
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. EXPORT - structure + payloads + exclusion des données d'étudiant
// ─────────────────────────────────────────────────────────────────────────────

test('export contient l\'arbre, les payloads complets, devoirs, carnet et banque', function (): void {
    $source = makeBkCourse('bk-export', 'Cours à exporter');
    $ids    = seedBkRichCourse($source);

    $data = app(CourseBackupService::class)->export($source->fresh());

    expect($data['format_version'])->toBe(CourseBackupService::FORMAT_VERSION);
    expect($data['course']['title'])->toBe('Cours à exporter');
    expect($data['chapters'])->toHaveCount(1);
    expect($data['chapters'][0]['lessons'][0]['items'])->toHaveCount(2);

    // Payload complet préservé (restriction + banque).
    $items = collect($data['chapters'][0]['lessons'][0]['items'])->keyBy('title');
    expect($items['Quiz intro']['payload']['question_bank']['category_id'])->toBe($ids['bank_cat_id']);
    expect($items['Suite']['payload']['access_restrictions']['conditions'][0]['item_id'])->toBe($ids['quiz_id']);

    // Devoirs + grille, carnet, banque présents.
    expect($data['assignments'])->toHaveCount(1);
    expect($data['assignments'][0]['rubric_criteria'][0]['levels'][0]['points'])->toBe(10);
    expect($data['grade_categories'])->toHaveCount(1);
    expect($data['grade_items'])->toHaveCount(2);
    expect($data['question_bank']['categories'])->toHaveCount(1);
    expect($data['question_bank']['questions'])->toHaveCount(1);

    // completion_criteria du cours exporté.
    expect($data['course']['completion_criteria']['items'])->toBe([$ids['quiz_id']]);
});

test('export N\'INCLUT JAMAIS les données d\'étudiant (inscriptions, rôles)', function (): void {
    $source = makeBkCourse('bk-noperso', 'Cours');
    seedBkRichCourse($source);

    // Bruit : une inscription d'élève (donnée personnelle à ne pas exporter).
    $student = User::factory()->create();
    Enrollment::create(['course_id' => $source->id, 'user_id' => $student->id, 'status' => 'active', 'source' => 'admin', 'enrolled_at' => now()]);

    $data = app(CourseBackupService::class)->export($source->fresh());
    $json = json_encode($data);

    // Aucune clé/donnée personnelle.
    expect($data)->not->toHaveKey('enrollments');
    expect($data)->not->toHaveKey('course_roles');
    expect($data)->not->toHaveKey('completions');
    expect($json)->not->toContain($student->email);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. IMPORT - nouveau cours en draft + REMAP des références internes
// ─────────────────────────────────────────────────────────────────────────────

test('import recrée un NOUVEAU cours brouillon possédé par l\'importateur', function (): void {
    $source = makeBkCourse('bk-imp-src', 'Cours source');
    seedBkRichCourse($source);
    $data = app(CourseBackupService::class)->export($source->fresh());

    $importer = makeBkInstructor();
    $copy     = app(CourseBackupService::class)->import($data, $importer);

    expect($copy->id)->not->toBe($source->id);
    expect($copy->status)->toBe('draft');
    expect($copy->slug)->not->toBe($source->slug);
    expect((int) $copy->created_by)->toBe($importer->id);
    expect(CourseRole::where('course_id', $copy->id)->where('user_id', $importer->id)->where('role', 'owner')->exists())->toBeTrue();

    // Même structure (2 items, 1 devoir, 2 grade_items).
    expect(bkItemsByTitle($copy))->toHaveCount(2);
    expect(Assignment::where('course_id', $copy->id)->count())->toBe(1);
    expect(GradeItem::where('course_id', $copy->id)->count())->toBe(2);
});

test('REMAP : restriction item_id pointe le NOUVEL item, condition group retirée', function (): void {
    $source = makeBkCourse('bk-remap-restr', 'Cours');
    seedBkRichCourse($source);
    $data = app(CourseBackupService::class)->export($source->fresh());

    $copy  = app(CourseBackupService::class)->import($data, makeBkInstructor());
    $items = bkItemsByTitle($copy);

    $newQuizId = (int) $items['Quiz intro']->id;
    $newDoc    = $items['Suite'];
    $conds     = $newDoc->payload['access_restrictions']['conditions'];

    // Une seule condition reste (la condition group a été retirée : cohortes non exportées).
    expect($conds)->toHaveCount(1);
    expect($conds[0]['type'])->toBe('grade');
    // item_id remappé vers le NOUVEL item quiz (jamais l'ancien id source).
    expect($conds[0]['item_id'])->toBe($newQuizId);
    expect($conds[0]['item_id'])->not->toBe($source->fresh()->completion_criteria['items'][0]);
});

test('REMAP : grade_items.item_id + question_bank.category_id + completion_criteria remappés', function (): void {
    $source = makeBkCourse('bk-remap-all', 'Cours');
    seedBkRichCourse($source);
    $data = app(CourseBackupService::class)->export($source->fresh());

    $importer = makeBkInstructor();
    $copy     = app(CourseBackupService::class)->import($data, $importer);
    $items    = bkItemsByTitle($copy);
    $newQuiz  = $items['Quiz intro'];

    // grade_item de type quiz pointe le NOUVEL item.
    $quizGi = GradeItem::where('course_id', $copy->id)->where('item_type', 'quiz')->first();
    expect((int) $quizGi->item_id)->toBe((int) $newQuiz->id);
    expect((int) $quizGi->grade_category_id)->toBe((int) GradeCategory::where('course_id', $copy->id)->value('id'));

    // grade_item de type assignment pointe le NOUVEAU devoir.
    $assignGi = GradeItem::where('course_id', $copy->id)->where('item_type', 'assignment')->first();
    expect((int) $assignGi->item_id)->toBe((int) Assignment::where('course_id', $copy->id)->value('id'));

    // Banque : nouvelle catégorie possédée par l'importateur, payload remappé.
    $newCat = QuestionCategory::where('owner_id', $importer->id)->first();
    expect($newCat)->not->toBeNull();
    expect((int) $newQuiz->payload['question_bank']['category_id'])->toBe((int) $newCat->id);
    expect(Question::where('category_id', $newCat->id)->count())->toBe(1);

    // completion_criteria.items remappé vers le NOUVEL item quiz.
    expect($copy->fresh()->completion_criteria['items'])->toBe([(int) $newQuiz->id]);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. VALIDATION - JSON invalide / format inconnu rejeté proprement
// ─────────────────────────────────────────────────────────────────────────────

test('import d\'un format non pris en charge lève une exception métier', function (): void {
    $importer = makeBkInstructor();

    app(CourseBackupService::class)->import(
        ['format_version' => '9.9', 'course' => ['title' => 'X']],
        $importer
    );
})->throws(InvalidCourseBackupException::class);

test('import sans métadonnées de cours lève une exception métier', function (): void {
    $importer = makeBkInstructor();
    $before   = Course::count();

    try {
        app(CourseBackupService::class)->import(['format_version' => '1.0'], $importer);
        $threw = false;
    } catch (InvalidCourseBackupException) {
        $threw = true;
    }

    expect($threw)->toBeTrue();
    expect(Course::count())->toBe($before); // aucune écriture partielle
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ANTI-IDOR - exporter un cours qu'on ne gère pas = 403
// ─────────────────────────────────────────────────────────────────────────────

test('exporter un cours qu\'on ne gère pas est interdit (403)', function (): void {
    $source = makeBkCourse('bk-idor', 'Cours protégé');
    seedBkRichCourse($source); // owner créé à l'intérieur

    $attacker = makeBkInstructor(); // formateur SANS rôle sur ce cours

    $this->actingAs($attacker)
        ->get(route('academy.courses.export', $source->slug))
        ->assertForbidden();
});

test('le owner peut télécharger la sauvegarde (.json) de son cours', function (): void {
    $source = makeBkCourse('bk-dl', 'Cours');
    $ids    = seedBkRichCourse($source);
    $owner  = User::find($ids['owner_id']);

    $response = $this->actingAs($owner)->get(route('academy.courses.export', $source->slug));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/json; charset=utf-8');
    expect($response->headers->get('content-disposition'))->toContain('academie-cours-bk-dl-');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. RÉTROCOMPATIBILITÉ - item sans restriction inchangé après import
// ─────────────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────────────────────
// 6. CHAMPS TEXTE LONGS - tronqués à l'import (anti-DoS mémoire)
// ─────────────────────────────────────────────────────────────────────────────

test('import : description trop longue est tronquée à 200 000 caractères', function (): void {
    $source = makeBkCourse('bk-trunc-desc', 'Cours long');
    seedBkRichCourse($source);
    $data = app(CourseBackupService::class)->export($source->fresh());

    // Injecter une description hors-borne (300 000 caractères).
    $data['course']['description'] = str_repeat('D', 300000);

    $importer = makeBkInstructor();
    $copy     = app(CourseBackupService::class)->import($data, $importer);

    expect(mb_strlen((string) ($copy->description ?? '')))->toBeLessThanOrEqual(200000);
});

test('import : instructions de devoir trop longues sont tronquées à 65 535 caractères', function (): void {
    $source = makeBkCourse('bk-trunc-instr', 'Cours instructions');
    seedBkRichCourse($source);
    $data = app(CourseBackupService::class)->export($source->fresh());

    // Injecter des instructions hors-borne.
    $data['assignments'][0]['instructions'] = str_repeat('I', 100000);

    $copy  = app(CourseBackupService::class)->import($data, makeBkInstructor());
    $instr = Assignment::where('course_id', $copy->id)->value('instructions');

    expect(mb_strlen((string) $instr))->toBeLessThanOrEqual(65535);
});

test('import : certificate_message trop long est tronqué à 65 535 caractères', function (): void {
    $importer = makeBkInstructor();
    $data = [
        'format_version' => '1.0',
        'course' => [
            'title'               => 'Cours certif long',
            'certificate_message' => str_repeat('M', 100000),
        ],
    ];

    $copy = app(CourseBackupService::class)->import($data, $importer);

    expect(mb_strlen((string) ($copy->certificate_message ?? '')))->toBeLessThanOrEqual(65535);
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. COULEUR CERTIFICAT - validation regex (anti-injection style)
// ─────────────────────────────────────────────────────────────────────────────

test('import : certificate_accent_color invalide est ignoré (null en base)', function (): void {
    $importer = makeBkInstructor();

    foreach (['javascript:alert(1)', 'red', '#xyz', '', 'expression(alert(1))'] as $couleur) {
        $data = [
            'format_version' => '1.0',
            'course'         => ['title' => 'Cours couleur', 'certificate_accent_color' => $couleur],
        ];
        $copy = app(CourseBackupService::class)->import($data, $importer);
        expect($copy->certificate_accent_color)->toBeNull("Couleur « {$couleur} » aurait dû être rejetée.");
    }
});

test('import : certificate_accent_color valide est conservé', function (): void {
    $importer = makeBkInstructor();

    foreach (['#abc', '#AABBCC', '#1a2b3c', '#fff', '#12345678'] as $couleur) {
        $data = [
            'format_version' => '1.0',
            'course'         => ['title' => 'Cours couleur valide', 'certificate_accent_color' => $couleur],
        ];
        $copy = app(CourseBackupService::class)->import($data, $importer);
        expect($copy->certificate_accent_color)->toBe($couleur, "Couleur valide « {$couleur} » aurait dû être conservée.");
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 8. assertValidEnvelope PUBLIQUE STATIQUE - réutilisable dès l'aperçu
// ─────────────────────────────────────────────────────────────────────────────

test('assertValidEnvelope est publique et rejette un titre vide', function (): void {
    expect(fn () => CourseBackupService::assertValidEnvelope([
        'format_version' => '1.0',
        'course'         => ['title' => ''],
    ]))->toThrow(InvalidCourseBackupException::class);
});

test('assertValidEnvelope rejette une section corrompue (non-tableau)', function (): void {
    expect(fn () => CourseBackupService::assertValidEnvelope([
        'format_version' => '1.0',
        'course'         => ['title' => 'Cours'],
        'chapters'       => 'pas_un_tableau',
    ]))->toThrow(InvalidCourseBackupException::class);
});

test('assertValidEnvelope accepte une enveloppe minimale valide sans lever d\'exception', function (): void {
    // Ne doit PAS lever d'exception.
    CourseBackupService::assertValidEnvelope([
        'format_version' => '1.0',
        'course'         => ['title' => 'Cours minimal'],
    ]);
    expect(true)->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. RÉTROCOMPATIBILITÉ - item sans restriction inchangé après import
// ─────────────────────────────────────────────────────────────────────────────

test('un item SANS access_restrictions est restauré tel quel (rétrocompat)', function (): void {
    $source  = makeBkCourse('bk-retro', 'Cours simple');
    $chapter = Chapter::create(['course_id' => $source->id, 'title' => 'Ch', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'L', 'slug' => 'bk-retro-l', 'position' => 1]);
    LessonItem::create([
        'lesson_id' => $lesson->id, 'type' => 'video', 'title' => 'Vidéo', 'position' => 1,
        'payload' => ['player_url' => 'https://share.screenpal.com/player/xyz', 'duration_seconds' => 300],
    ]);

    $data = app(CourseBackupService::class)->export($source->fresh());
    $copy = app(CourseBackupService::class)->import($data, makeBkInstructor());

    $video = bkItemsByTitle($copy)['Vidéo'];
    expect($video->payload['player_url'])->toBe('https://share.screenpal.com/player/xyz');
    expect($video->payload)->not->toHaveKey('access_restrictions');
});
