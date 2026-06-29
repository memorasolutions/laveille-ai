<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TESTS GOLDEN-MASTER / CARACTÉRISATION — éditeurs RÉPÉTEURS de CourseEditor :
 * Feedback (F18), Database (F20), Workshop (F21).
 *
 * OBJECTIF : FIGER le comportement ACTUEL des 3 éditeurs répéteurs AVANT toute
 * extraction en traits/services. Ces tests décrivent CE QUI EST, pas ce qui devrait
 * idéalement être. Si un comportement paraît étrange, on le fige tel quel (commentaire
 * CARACTÉRISATION ou BIZARRERIE).
 *
 * COUVERTURE :
 *  FB. Feedback (F18)
 *      FB1  : blankFeedbackQuestion (forme observée via addFeedbackQuestion)
 *      FB2  : loadFeedbackEditor — charge le tampon depuis le payload
 *      FB3  : cancelFeedbackEditor — vide le tampon, rien persisté
 *      FB4  : addFeedbackQuestion — ajoute une question vierge (édition)
 *      FB5  : removeFeedbackQuestion — retire une question et réindexe (édition)
 *      FB6  : saveFeedback — persiste payload modifié
 *      FB7  : saveFeedback — vide le tampon après sauvegarde
 *      FB8  : saveFeedback — échoue si toutes les questions ont un libellé vide
 *      FB9  : addNewFeedbackQuestion — ajoute au tampon de CRÉATION
 *      FB10 : removeNewFeedbackQuestion — retire du tampon de CRÉATION
 *      FB11 : loadFeedbackEditor — ignoré si l'item n'est pas de type feedback
 *
 *  DB. Database (F20)
 *      DB1  : blankDatabaseField (forme observée via addDatabaseField)
 *      DB2  : loadDatabaseEditor — charge le tampon depuis le payload + schéma
 *      DB3  : cancelDatabaseEditor — vide le tampon, rien persisté
 *      DB4  : addDatabaseField — ajoute un champ vierge (édition)
 *      DB5  : removeDatabaseField — retire le champ d'index donné et réindexe
 *      DB6  : saveDatabase — persiste payload + synchronise le schéma
 *      DB7  : saveDatabase — vide le tampon après sauvegarde
 *      DB8  : saveDatabase — persiste allow_student_add et require_approval
 *      DB9  : addNewDatabaseField — ajoute au tampon de CRÉATION
 *      DB10 : removeNewDatabaseField — retire du tampon de CRÉATION
 *      DB11 : loadDatabaseEditor — ignoré si l'item n'est pas de type database
 *
 *  WS. Workshop (F21)
 *      WS1  : blankWorkshopCriterion (forme observée via addWorkshopCriterion)
 *      WS2  : loadWorkshopEditor — charge le tampon depuis le payload + critères
 *      WS3  : cancelWorkshopEditor — vide le tampon, rien persisté
 *      WS4  : addWorkshopCriterion — ajoute un critère vierge (édition)
 *      WS5  : removeWorkshopCriterion — retire le critère d'index donné et réindexe
 *      WS6  : saveWorkshop — persiste payload + synchronise la grille
 *      WS7  : saveWorkshop — vide le tampon après sauvegarde
 *      WS8  : saveWorkshop — PRÉSERVE la phase actuelle (l'éditeur ne la change pas)
 *      WS9  : saveWorkshop — persiste reviews_per_student et anonymous
 *      WS10 : addNewWorkshopCriterion — ajoute au tampon de CRÉATION
 *      WS11 : removeNewWorkshopCriterion — retire du tampon de CRÉATION
 *      WS12 : loadWorkshopEditor — ignoré si l'item n'est pas de type workshop
 *
 * GARDE-FOU : si le module Academy est désactivé, tous les tests sont SKIPPED.
 * PRÉFIXE helpers : `gmCER_` (golden master CourseEditor Repeater — évite collision).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\DatabaseService;
use Modules\Academy\Services\FeedbackService;
use Modules\Academy\Services\WorkshopService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe gmCER_ pour éviter collision avec gmCE_ et autres fichiers)
// ─────────────────────────────────────────────────────────────────────────────

function gmCER_makeCourse(string $slug = 'cours-cer', string $title = 'Cours CER'): Course
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

function gmCER_makeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    return $admin;
}

function gmCER_addLesson(Course $course, string $title = 'Leçon CER'): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre CER',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => $title,
        'slug'       => \Illuminate\Support\Str::slug($title).'-'.$chapter->id.'-1',
        'position'   => 1,
    ]);
}

/** Crée un item feedback pré-configuré avec 2 questions. */
function gmCER_feedbackItem(Lesson $lesson, string $title = 'Sondage CER'): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'feedback',
        'title'       => $title,
        'position'    => 1,
        'payload'     => [
            'intro'     => 'Intro du sondage.',
            'anonymous' => false,
            'questions' => [
                ['type' => 'rating', 'label' => 'Question 1', 'scale' => 5, 'required' => false],
                ['type' => 'text',   'label' => 'Question 2', 'required' => true],
            ],
        ],
        'is_required' => false,
    ]);
}

/** Crée un item database pré-configuré (schéma vide au départ). */
function gmCER_databaseItem(Lesson $lesson, string $title = 'Base CER'): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'database',
        'title'       => $title,
        'position'    => 1,
        'payload'     => [
            'intro'             => 'Intro de la base.',
            'allow_student_add' => true,
            'require_approval'  => false,
        ],
        'is_required' => false,
    ]);
}

/** Crée un item workshop pré-configuré (grille vide au départ). */
function gmCER_workshopItem(Lesson $lesson, string $title = 'Atelier CER'): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'workshop',
        'title'       => $title,
        'position'    => 1,
        'payload'     => [
            'intro'               => "Intro de l'atelier.",
            'phase'               => 'submission',
            'reviews_per_student' => 2,
            'anonymous'           => true,
        ],
        'is_required' => false,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Setup commun
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->course = gmCER_makeCourse();
    $this->admin  = gmCER_makeAdmin();
    $this->lesson = gmCER_addLesson($this->course);
});

// ─────────────────────────────────────────────────────────────────────────────
// FB. FEEDBACK — éditeur répéteur de questions (F18)
// ─────────────────────────────────────────────────────────────────────────────

test('FB1 : blankFeedbackQuestion — forme d\'une question vierge observée via addFeedbackQuestion', function (): void {
    // CARACTÉRISATION : blankFeedbackQuestion() est privée ; on observe sa forme
    // indirectement via addFeedbackQuestion qui l'appelle et pousse le résultat.
    $item = gmCER_feedbackItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadFeedbackEditor', $item->id)
        ->call('addFeedbackQuestion', $item->id);

    $questions = $component->get("editFeedback.{$item->id}.questions");

    // L'éditeur avait 2 questions préchargées + 1 vierge.
    expect($questions)->toHaveCount(3);

    // La vierge (en dernière position) doit avoir la forme exacte de blankFeedbackQuestion().
    $blank = end($questions);
    expect($blank['type'])->toBe('rating');
    expect($blank['label'])->toBe('');
    expect($blank['scale'])->toBe(FeedbackService::DEFAULT_SCALE);
    expect($blank['options'])->toBe('');
    expect($blank['required'])->toBeFalse();
});

test('FB2 : loadFeedbackEditor — charge le tampon depuis le payload de l\'item', function (): void {
    // CARACTÉRISATION : le tampon editFeedback[$itemId] est peuplé avec le titre,
    // l'intro, l'anonymat et les questions converties (options en chaîne multiligne).
    $item = gmCER_feedbackItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadFeedbackEditor', $item->id);

    $buffer = $component->get("editFeedback.{$item->id}");
    expect($buffer)->not->toBeNull();
    expect($buffer['title'])->toBe('Sondage CER');
    expect($buffer['intro'])->toBe('Intro du sondage.');
    expect($buffer['anonymous'])->toBeFalse();
    expect($buffer['questions'])->toHaveCount(2);
    expect($buffer['questions'][0]['label'])->toBe('Question 1');
    expect($buffer['questions'][0]['type'])->toBe('rating');
    expect($buffer['questions'][1]['label'])->toBe('Question 2');
    expect($buffer['questions'][1]['type'])->toBe('text');
});

test('FB3 : cancelFeedbackEditor — vide le tampon sans persister quoi que ce soit', function (): void {
    $item = gmCER_feedbackItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadFeedbackEditor', $item->id);

    // Le tampon est chargé.
    expect($component->get("editFeedback.{$item->id}"))->not->toBeNull();

    // Cancel vide le tampon.
    $component->call('cancelFeedbackEditor', $item->id);
    expect($component->get("editFeedback.{$item->id}"))->toBeNull();

    // CARACTÉRISATION : rien n'a changé en DB (les questions restent intactes).
    $fresh = $item->fresh();
    expect($fresh->payload['questions'])->toHaveCount(2);
    expect($fresh->payload['questions'][0]['label'])->toBe('Question 1');
});

test('FB4 : addFeedbackQuestion — ajoute une question vierge au tampon d\'édition', function (): void {
    $item = gmCER_feedbackItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadFeedbackEditor', $item->id)
        ->call('addFeedbackQuestion', $item->id)
        ->call('addFeedbackQuestion', $item->id);

    $questions = $component->get("editFeedback.{$item->id}.questions");
    // 2 préchargées + 2 vierges.
    expect($questions)->toHaveCount(4);
});

test('FB5 : removeFeedbackQuestion — retire la question d\'index donné et réindexe le tableau', function (): void {
    $item = gmCER_feedbackItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadFeedbackEditor', $item->id)
        ->call('removeFeedbackQuestion', $item->id, 0); // retire la 1re question (index 0)

    $questions = $component->get("editFeedback.{$item->id}.questions");

    // CARACTÉRISATION : après retrait de l'index 0, il reste 1 question réindexée à 0.
    expect($questions)->toHaveCount(1);
    expect(array_keys($questions))->toBe([0]); // array_values() appliqué = réindexé
    expect($questions[0]['label'])->toBe('Question 2'); // ancienne index 1 → maintenant index 0
});

test('FB6 : saveFeedback — persiste le titre modifié et le payload des questions', function (): void {
    $item = gmCER_feedbackItem($this->lesson);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadFeedbackEditor', $item->id)
        ->set("editFeedback.{$item->id}.title", 'Sondage modifié')
        ->call('saveFeedback', $item->id)
        ->assertHasNoErrors();

    $fresh = $item->fresh();
    expect($fresh->title)->toBe('Sondage modifié');
    // Les questions sont intégrées au payload.
    expect($fresh->payload['questions'])->toHaveCount(2);
    expect($fresh->payload['questions'][0]['label'])->toBe('Question 1');
});

test('FB7 : saveFeedback — vide le tampon editFeedback après sauvegarde réussie', function (): void {
    // CARACTÉRISATION : unset($this->editFeedback[$itemId]) est appelé dans saveFeedback.
    $item = gmCER_feedbackItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadFeedbackEditor', $item->id)
        ->call('saveFeedback', $item->id);

    expect($component->get("editFeedback.{$item->id}"))->toBeNull();
});

test('FB8 : saveFeedback — échoue si toutes les questions ont un libellé vide (min:1 après normalisation)', function (): void {
    // CARACTÉRISATION : FeedbackService::normalizeQuestions() écarte les questions sans libellé.
    // Si le tableau résultant est vide, la règle 'required|array|min:1' échoue.
    $item = gmCER_feedbackItem($this->lesson);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadFeedbackEditor', $item->id)
        ->set("editFeedback.{$item->id}.questions", [
            ['type' => 'rating', 'label' => '', 'scale' => 5, 'options' => '', 'required' => false],
        ])
        ->call('saveFeedback', $item->id)
        ->assertHasErrors(['feedback_questions']);

    // Rien n'a changé en DB.
    expect($item->fresh()->payload['questions'])->toHaveCount(2);
});

test('FB9 : addNewFeedbackQuestion — ajoute une question vierge au tampon de CRÉATION', function (): void {
    // CARACTÉRISATION : addNewFeedbackQuestion manipule newItem[$lessonId]['feedback_questions']
    // (tampon de création, distinct du tampon d'édition editFeedback).
    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('addNewFeedbackQuestion', $this->lesson->id);

    $questions = $component->get("newItem.{$this->lesson->id}.feedback_questions");
    expect($questions)->toHaveCount(1);
    expect($questions[0]['type'])->toBe('rating');
    expect($questions[0]['label'])->toBe('');
    expect($questions[0]['scale'])->toBe(FeedbackService::DEFAULT_SCALE);
});

test('FB10 : removeNewFeedbackQuestion — retire la question d\'index donné du tampon de CRÉATION', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('addNewFeedbackQuestion', $this->lesson->id)
        ->call('addNewFeedbackQuestion', $this->lesson->id) // 2 questions vierges
        ->call('removeNewFeedbackQuestion', $this->lesson->id, 0); // retire la 1re

    $questions = $component->get("newItem.{$this->lesson->id}.feedback_questions");
    // CARACTÉRISATION : 1 seule question reste, réindexée à 0.
    expect($questions)->toHaveCount(1);
    expect(array_keys($questions))->toBe([0]);
});

test('FB11 : loadFeedbackEditor — retour silencieux si l\'item n\'est pas de type feedback', function (): void {
    // CARACTÉRISATION : le guard `if ($item->type !== 'feedback') return;` dans
    // loadFeedbackEditor ne lance pas d'exception et n'ajoute rien au tampon.
    $item = LessonItem::create([
        'lesson_id'   => $this->lesson->id,
        'type'        => 'document',
        'title'       => 'Doc CER',
        'position'    => 1,
        'payload'     => [],
        'is_required' => false,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadFeedbackEditor', $item->id)
        ->assertHasNoErrors();

    // Le tampon reste absent (retour anticipé silencieux).
    expect($component->get("editFeedback.{$item->id}"))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// DB. DATABASE — éditeur répéteur de champs (schéma) (F20)
// ─────────────────────────────────────────────────────────────────────────────

test('DB1 : blankDatabaseField — forme d\'un champ vierge observée via addDatabaseField', function (): void {
    // CARACTÉRISATION : blankDatabaseField() est privée ; on observe sa forme
    // via addDatabaseField qui l'appelle (schéma vide au départ de l'item).
    $item = gmCER_databaseItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadDatabaseEditor', $item->id)
        ->call('addDatabaseField', $item->id);

    $fields = $component->get("editDatabase.{$item->id}.fields");
    // Aucun champ préexistant + 1 ajouté.
    expect($fields)->toHaveCount(1);

    $blank = $fields[0];
    expect($blank['label'])->toBe('');
    expect($blank['type'])->toBe('text');
    expect($blank['required'])->toBeFalse();
    expect($blank['options'])->toBe('');
});

test('DB2 : loadDatabaseEditor — charge le tampon depuis le payload et les champs du schéma', function (): void {
    $item = gmCER_databaseItem($this->lesson);
    // Ajouter un schéma de 2 champs.
    DatabaseService::syncFields($item, [
        ['label' => 'Champ A', 'type' => 'text', 'required' => true],
        ['label' => 'Champ B', 'type' => 'url',  'required' => false],
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadDatabaseEditor', $item->id);

    $buffer = $component->get("editDatabase.{$item->id}");
    expect($buffer)->not->toBeNull();
    expect($buffer['title'])->toBe('Base CER');
    expect($buffer['intro'])->toBe('Intro de la base.');
    expect($buffer['allow_student_add'])->toBeTrue();
    expect($buffer['require_approval'])->toBeFalse();
    // Les 2 champs du schéma sont chargés.
    expect($buffer['fields'])->toHaveCount(2);
    expect($buffer['fields'][0]['label'])->toBe('Champ A');
    expect($buffer['fields'][0]['type'])->toBe('text');
    expect($buffer['fields'][1]['label'])->toBe('Champ B');
    expect($buffer['fields'][1]['type'])->toBe('url');
});

test('DB3 : cancelDatabaseEditor — vide le tampon sans persister quoi que ce soit', function (): void {
    $item = gmCER_databaseItem($this->lesson);
    DatabaseService::syncFields($item, [
        ['label' => 'Nom', 'type' => 'text', 'required' => true],
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadDatabaseEditor', $item->id);

    expect($component->get("editDatabase.{$item->id}"))->not->toBeNull();

    $component->call('cancelDatabaseEditor', $item->id);
    expect($component->get("editDatabase.{$item->id}"))->toBeNull();

    // CARACTÉRISATION : le schéma en DB est inchangé.
    expect(DatabaseService::fields($item->fresh()))->toHaveCount(1);
    expect(DatabaseService::fields($item->fresh())->first()->label)->toBe('Nom');
});

test('DB4 : addDatabaseField — ajoute un champ vierge au tampon d\'édition', function (): void {
    $item = gmCER_databaseItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadDatabaseEditor', $item->id)
        ->call('addDatabaseField', $item->id)
        ->call('addDatabaseField', $item->id);

    $fields = $component->get("editDatabase.{$item->id}.fields");
    expect($fields)->toHaveCount(2);
});

test('DB5 : removeDatabaseField — retire le champ d\'index donné et réindexe le tableau', function (): void {
    $item = gmCER_databaseItem($this->lesson);
    DatabaseService::syncFields($item, [
        ['label' => 'Alpha', 'type' => 'text'],
        ['label' => 'Beta',  'type' => 'text'],
        ['label' => 'Gamma', 'type' => 'text'],
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadDatabaseEditor', $item->id)
        ->call('removeDatabaseField', $item->id, 1); // retire « Beta » (index 1)

    $fields = $component->get("editDatabase.{$item->id}.fields");
    // CARACTÉRISATION : 2 champs restants, réindexés.
    expect($fields)->toHaveCount(2);
    expect(array_keys($fields))->toBe([0, 1]);
    expect($fields[0]['label'])->toBe('Alpha');
    expect($fields[1]['label'])->toBe('Gamma'); // ancienne index 2 → maintenant index 1
});

test('DB6 : saveDatabase — persiste le payload et synchronise le schéma (DatabaseService::syncFields)', function (): void {
    $item = gmCER_databaseItem($this->lesson);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadDatabaseEditor', $item->id)
        ->set("editDatabase.{$item->id}.title", 'Base modifiée')
        ->set("editDatabase.{$item->id}.fields", [
            ['label' => 'Nom', 'type' => 'text', 'required' => true,  'options' => ''],
            ['label' => 'URL', 'type' => 'url',  'required' => false, 'options' => ''],
        ])
        ->call('saveDatabase', $item->id)
        ->assertHasNoErrors();

    $fresh = $item->fresh();
    expect($fresh->title)->toBe('Base modifiée');

    // SCHÉMA persisté via syncFields.
    $dbFields = DatabaseService::fields($fresh);
    expect($dbFields)->toHaveCount(2);
    expect($dbFields[0]->label)->toBe('Nom');
    expect($dbFields[0]->type)->toBe('text');
    expect($dbFields[1]->label)->toBe('URL');
    expect($dbFields[1]->type)->toBe('url');
});

test('DB7 : saveDatabase — vide le tampon editDatabase après sauvegarde réussie', function (): void {
    // CARACTÉRISATION : unset($this->editDatabase[$itemId]) est appelé dans saveDatabase.
    $item = gmCER_databaseItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadDatabaseEditor', $item->id)
        ->call('saveDatabase', $item->id);

    expect($component->get("editDatabase.{$item->id}"))->toBeNull();
});

test('DB8 : saveDatabase — persiste allow_student_add et require_approval dans le payload', function (): void {
    // CARACTÉRISATION : buildDatabasePayload() lit allow_student_add et require_approval
    // du tampon et les écrit dans payload (défauts : true / false respectivement).
    $item = gmCER_databaseItem($this->lesson);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadDatabaseEditor', $item->id)
        ->set("editDatabase.{$item->id}.allow_student_add", false)
        ->set("editDatabase.{$item->id}.require_approval", true)
        ->call('saveDatabase', $item->id)
        ->assertHasNoErrors();

    $payload = $item->fresh()->payload;
    expect($payload['allow_student_add'])->toBeFalse();
    expect($payload['require_approval'])->toBeTrue();
});

test('DB9 : addNewDatabaseField — ajoute un champ vierge au tampon de CRÉATION', function (): void {
    // CARACTÉRISATION : addNewDatabaseField manipule newItem[$lessonId]['database_fields']
    // (tampon de création, distinct du tampon d'édition editDatabase).
    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('addNewDatabaseField', $this->lesson->id);

    $fields = $component->get("newItem.{$this->lesson->id}.database_fields");
    expect($fields)->toHaveCount(1);
    expect($fields[0]['label'])->toBe('');
    expect($fields[0]['type'])->toBe('text');
    expect($fields[0]['required'])->toBeFalse();
    expect($fields[0]['options'])->toBe('');
});

test('DB10 : removeNewDatabaseField — retire le champ d\'index donné du tampon de CRÉATION', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('addNewDatabaseField', $this->lesson->id)
        ->call('addNewDatabaseField', $this->lesson->id) // 2 champs vierges
        ->call('removeNewDatabaseField', $this->lesson->id, 0); // retire le 1er

    $fields = $component->get("newItem.{$this->lesson->id}.database_fields");
    // CARACTÉRISATION : 1 seul champ reste, réindexé à 0.
    expect($fields)->toHaveCount(1);
    expect(array_keys($fields))->toBe([0]);
});

test('DB11 : loadDatabaseEditor — retour silencieux si l\'item n\'est pas de type database', function (): void {
    // CARACTÉRISATION : le guard `if ($item->type !== 'database') return;` dans
    // loadDatabaseEditor ne lance pas d'exception et n'ajoute rien au tampon.
    $item = LessonItem::create([
        'lesson_id'   => $this->lesson->id,
        'type'        => 'document',
        'title'       => 'Doc CER 2',
        'position'    => 1,
        'payload'     => [],
        'is_required' => false,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadDatabaseEditor', $item->id)
        ->assertHasNoErrors();

    // Le tampon reste absent.
    expect($component->get("editDatabase.{$item->id}"))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// WS. WORKSHOP — éditeur répéteur de critères (grille) (F21)
// ─────────────────────────────────────────────────────────────────────────────

test('WS1 : blankWorkshopCriterion — forme d\'un critère vierge observée via addWorkshopCriterion', function (): void {
    // CARACTÉRISATION : blankWorkshopCriterion() est privée ; on observe sa forme
    // via addWorkshopCriterion qui l'appelle (grille vide au départ de l'item).
    $item = gmCER_workshopItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id)
        ->call('addWorkshopCriterion', $item->id);

    $criteria = $component->get("editWorkshop.{$item->id}.criteria");
    // Aucun critère préexistant + 1 ajouté.
    expect($criteria)->toHaveCount(1);

    $blank = $criteria[0];
    expect($blank['label'])->toBe('');
    expect($blank['description'])->toBe('');
    expect($blank['max_score'])->toBe(10);
    expect($blank['weight'])->toBe(1);
});

test('WS2 : loadWorkshopEditor — charge le tampon depuis le payload et les critères de la grille', function (): void {
    $item = gmCER_workshopItem($this->lesson);
    WorkshopService::syncCriteria($item, [
        ['label' => 'Originalité', 'description' => 'Idée novatrice', 'max_score' => 20, 'weight' => 2],
        ['label' => 'Clarté',      'description' => '',               'max_score' => 10, 'weight' => 1],
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id);

    $buffer = $component->get("editWorkshop.{$item->id}");
    expect($buffer)->not->toBeNull();
    expect($buffer['title'])->toBe('Atelier CER');
    expect($buffer['intro'])->toBe("Intro de l'atelier.");
    expect($buffer['reviews_per_student'])->toBe(2);
    expect($buffer['anonymous'])->toBeTrue();
    // Les 2 critères de la grille sont chargés.
    expect($buffer['criteria'])->toHaveCount(2);
    expect($buffer['criteria'][0]['label'])->toBe('Originalité');
    expect($buffer['criteria'][0]['max_score'])->toBe(20);
    expect($buffer['criteria'][1]['label'])->toBe('Clarté');
});

test('WS3 : cancelWorkshopEditor — vide le tampon sans persister quoi que ce soit', function (): void {
    $item = gmCER_workshopItem($this->lesson);
    WorkshopService::syncCriteria($item, [
        ['label' => 'Fond', 'max_score' => 10, 'weight' => 1],
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id);

    expect($component->get("editWorkshop.{$item->id}"))->not->toBeNull();

    $component->call('cancelWorkshopEditor', $item->id);
    expect($component->get("editWorkshop.{$item->id}"))->toBeNull();

    // CARACTÉRISATION : la grille en DB est inchangée.
    expect(WorkshopService::criteria($item->fresh()))->toHaveCount(1);
    expect(WorkshopService::criteria($item->fresh())->first()->label)->toBe('Fond');
});

test('WS4 : addWorkshopCriterion — ajoute un critère vierge au tampon d\'édition', function (): void {
    $item = gmCER_workshopItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id)
        ->call('addWorkshopCriterion', $item->id)
        ->call('addWorkshopCriterion', $item->id);

    $criteria = $component->get("editWorkshop.{$item->id}.criteria");
    expect($criteria)->toHaveCount(2);
});

test('WS5 : removeWorkshopCriterion — retire le critère d\'index donné et réindexe le tableau', function (): void {
    $item = gmCER_workshopItem($this->lesson);
    WorkshopService::syncCriteria($item, [
        ['label' => 'Critère A', 'max_score' => 10, 'weight' => 1],
        ['label' => 'Critère B', 'max_score' => 20, 'weight' => 2],
        ['label' => 'Critère C', 'max_score' => 15, 'weight' => 1],
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id)
        ->call('removeWorkshopCriterion', $item->id, 1); // retire « Critère B » (index 1)

    $criteria = $component->get("editWorkshop.{$item->id}.criteria");
    // CARACTÉRISATION : 2 critères restants, réindexés.
    expect($criteria)->toHaveCount(2);
    expect(array_keys($criteria))->toBe([0, 1]);
    expect($criteria[0]['label'])->toBe('Critère A');
    expect($criteria[1]['label'])->toBe('Critère C'); // ancienne index 2 → maintenant index 1
});

test('WS6 : saveWorkshop — persiste le payload et synchronise la grille (WorkshopService::syncCriteria)', function (): void {
    $item = gmCER_workshopItem($this->lesson);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id)
        ->set("editWorkshop.{$item->id}.title", 'Atelier modifié')
        ->set("editWorkshop.{$item->id}.criteria", [
            ['label' => 'Fond',  'description' => '', 'max_score' => 20, 'weight' => 2],
            ['label' => 'Forme', 'description' => '', 'max_score' => 10, 'weight' => 1],
        ])
        ->call('saveWorkshop', $item->id)
        ->assertHasNoErrors();

    $fresh = $item->fresh();
    expect($fresh->title)->toBe('Atelier modifié');

    // GRILLE persistée via syncCriteria.
    $dbCriteria = WorkshopService::criteria($fresh);
    expect($dbCriteria)->toHaveCount(2);
    expect($dbCriteria[0]->label)->toBe('Fond');
    expect($dbCriteria[0]->max_score)->toBe(20);
    expect($dbCriteria[1]->label)->toBe('Forme');
    expect($dbCriteria[1]->max_score)->toBe(10);
});

test('WS7 : saveWorkshop — vide le tampon editWorkshop après sauvegarde réussie', function (): void {
    // CARACTÉRISATION : unset($this->editWorkshop[$itemId]) est appelé dans saveWorkshop.
    $item = gmCER_workshopItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id)
        ->call('saveWorkshop', $item->id);

    expect($component->get("editWorkshop.{$item->id}"))->toBeNull();
});

test('WS8 : saveWorkshop — PRÉSERVE la phase actuelle (la phase ne se change pas depuis l\'éditeur)', function (): void {
    // CARACTÉRISATION CLÉ : saveWorkshop injecte WorkshopService::phase($item) dans l'input
    // (jamais depuis le navigateur). La phase 'assessment' doit être préservée après save.
    $item = gmCER_workshopItem($this->lesson);
    // Passer l'atelier en phase « assessment » directement en DB.
    $item->update(['payload' => array_merge($item->payload, ['phase' => 'assessment'])]);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id)
        ->call('saveWorkshop', $item->id)
        ->assertHasNoErrors();

    // CARACTÉRISATION : la phase est préservée à 'assessment' après l'enregistrement.
    expect(WorkshopService::phase($item->fresh()))->toBe('assessment');
});

test('WS9 : saveWorkshop — persiste reviews_per_student et anonymous depuis le tampon', function (): void {
    // CARACTÉRISATION : buildWorkshopPayload() lit reviews_per_student (borné 1..REVIEWS_MAX)
    // et anonymous (truthy) depuis le tampon et les écrit dans le payload.
    $item = gmCER_workshopItem($this->lesson);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id)
        ->set("editWorkshop.{$item->id}.reviews_per_student", 3)
        ->set("editWorkshop.{$item->id}.anonymous", false)
        ->call('saveWorkshop', $item->id)
        ->assertHasNoErrors();

    $fresh = $item->fresh();
    expect(WorkshopService::reviewsPerStudent($fresh))->toBe(3);
    expect(WorkshopService::isAnonymous($fresh))->toBeFalse();
});

test('WS10 : addNewWorkshopCriterion — ajoute un critère vierge au tampon de CRÉATION', function (): void {
    // CARACTÉRISATION : addNewWorkshopCriterion manipule newItem[$lessonId]['workshop_criteria']
    // (tampon de création, distinct du tampon d'édition editWorkshop).
    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('addNewWorkshopCriterion', $this->lesson->id);

    $criteria = $component->get("newItem.{$this->lesson->id}.workshop_criteria");
    expect($criteria)->toHaveCount(1);
    expect($criteria[0]['label'])->toBe('');
    expect($criteria[0]['description'])->toBe('');
    expect($criteria[0]['max_score'])->toBe(10);
    expect($criteria[0]['weight'])->toBe(1);
});

test('WS11 : removeNewWorkshopCriterion — retire le critère d\'index donné du tampon de CRÉATION', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('addNewWorkshopCriterion', $this->lesson->id)
        ->call('addNewWorkshopCriterion', $this->lesson->id) // 2 critères vierges
        ->call('removeNewWorkshopCriterion', $this->lesson->id, 0); // retire le 1er

    $criteria = $component->get("newItem.{$this->lesson->id}.workshop_criteria");
    // CARACTÉRISATION : 1 seul critère reste, réindexé à 0.
    expect($criteria)->toHaveCount(1);
    expect(array_keys($criteria))->toBe([0]);
});

test('WS12 : loadWorkshopEditor — retour silencieux si l\'item n\'est pas de type workshop', function (): void {
    // CARACTÉRISATION : le guard `if ($item->type !== 'workshop') return;` dans
    // loadWorkshopEditor ne lance pas d'exception et n'ajoute rien au tampon.
    $item = LessonItem::create([
        'lesson_id'   => $this->lesson->id,
        'type'        => 'document',
        'title'       => 'Doc CER 3',
        'position'    => 1,
        'payload'     => [],
        'is_required' => false,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadWorkshopEditor', $item->id)
        ->assertHasNoErrors();

    // Le tampon reste absent.
    expect($component->get("editWorkshop.{$item->id}"))->toBeNull();
});
