<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F20 BASE DE DONNÉES collaborative : nouvelle ACTIVITÉ de leçon « database »
 * (le gérant définit un SCHÉMA de champs, les inscrits soumettent des fiches selon ce
 * schéma ; type Moodle « Database »). Prouve, de façon AUTONOME (helpers préfixés v20) :
 *
 *  - création d'un item database via l'éditeur (intro / réglages) + synchro du schéma ;
 *  - gestion du schéma réservée au gérant (éditeur interdit au non-gérant) ;
 *  - un inscrit ajoute une fiche (gaté inscription : 403 si non inscrit / anonyme) ;
 *  - validation PAR TYPE : number / url / select / required rejetés si invalides ;
 *  - un inscrit ne peut PAS éditer / supprimer la fiche d'un AUTRE (403) ;
 *  - modération gérant : approuver / supprimer une fiche ; non-gérant 403 ;
 *  - require_approval cache la fiche aux autres jusqu'à approbation ;
 *  - allow_student_add=false bloque l'étudiant (le gérant ajoute quand même) ;
 *  - anti-XSS : une valeur avec <script> est neutralisée au rendu (renderValue) ;
 *  - honeypot rempli => rejet SILENCIEUX ; anti-IDOR (fiche / item d'un autre cours) ;
 *  - route throttlée + bornes ; rétrocompat (autres types inchangés ; database => manual).
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\DatabaseEntry;
use Modules\Academy\Models\DatabaseField;
use Modules\Academy\Models\DatabaseValue;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\DatabaseService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe v20 - autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v20Course(string $slug = 'cours-v20'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V20',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v20Lesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);
}

function v20DbItem(Lesson $lesson, array $payload = [], int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'database',
        'title'       => 'Base '.$position,
        'position'    => $position,
        'payload'     => array_merge([
            'intro'             => 'Documentons des outils ensemble.',
            'allow_student_add' => true,
            'require_approval'  => false,
        ], $payload),
        'is_required' => false,
    ]);
}

/**
 * Crée un schéma simple sur l'item : un champ texte requis + un champ url + un select.
 *
 * @return array{texte: DatabaseField, lien: DatabaseField, choix: DatabaseField}
 */
function v20Schema(LessonItem $item): array
{
    DatabaseService::syncFields($item, [
        ['label' => 'Nom',     'type' => 'text',   'required' => true],
        ['label' => 'Site',    'type' => 'url',    'required' => false],
        ['label' => 'Categorie', 'type' => 'select', 'required' => false, 'options' => "IA\nBureautique"],
    ]);

    $fields = DatabaseService::fields($item);

    return [
        'texte' => $fields[0],
        'lien'  => $fields[1],
        'choix' => $fields[2],
    ];
}

function v20Student(string $name = 'Étudiant Test'): User
{
    $u = User::factory()->create(['name' => $name]);
    $u->assignRole('student');

    return $u;
}

function v20Owner(Course $course): User
{
    $u = User::factory()->create(['name' => 'Formateur Test']);
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function v20Enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v20ShowUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

function v20AddUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/database/entries";
}

/** Crée une entrée directement (raccourci de fabrique) avec ses valeurs. */
function v20Entry(LessonItem $item, ?User $user, array $values, bool $approved = true): DatabaseEntry
{
    $entry = DatabaseEntry::create([
        'lesson_item_id' => $item->id,
        'user_id'        => $user?->id,
        'is_approved'    => $approved,
    ]);
    foreach ($values as $fieldId => $value) {
        DatabaseValue::create([
            'database_entry_id' => $entry->id,
            'database_field_id' => $fieldId,
            'value'             => $value,
        ]);
    }

    return $entry;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE + défauts
// ─────────────────────────────────────────────────────────────────────────────

test('DatabaseService lit la configuration avec ses défauts', function (): void {
    $item = LessonItem::create([
        'lesson_id' => v20Lesson(v20Course())->id,
        'type'      => 'database',
        'title'     => 'D',
        'position'  => 1,
        'payload'   => [],
    ]);
    expect(DatabaseService::allowsStudentAdd($item))->toBeTrue();
    expect(DatabaseService::requiresApproval($item))->toBeFalse();
    expect(DatabaseService::intro($item))->toBe('');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CRÉATION DE L'ITEM + SCHÉMA via l'éditeur
// ─────────────────────────────────────────────────────────────────────────────

test('un gérant crée un item database ; payload + schéma synchronisés', function (): void {
    $course = v20Course('cours-db-create');
    $lesson = v20Lesson($course);
    $owner  = v20Owner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Annuaire des outils')
        ->set("newItem.{$lesson->id}.database_intro", 'Listons les outils IA.')
        ->set("newItem.{$lesson->id}.allow_student_add", true)
        ->set("newItem.{$lesson->id}.database_fields", [
            ['label' => 'Nom', 'type' => 'text', 'required' => true, 'options' => ''],
            ['label' => 'Lien', 'type' => 'url', 'required' => false, 'options' => ''],
        ])
        ->call('addItem', $lesson->id, 'database')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'database')->first();
    expect($item)->not->toBeNull();
    expect($item->payload['intro'])->toBe('Listons les outils IA.');
    expect($item->payload['allow_student_add'])->toBeTrue();

    $fields = DatabaseField::forItem($item->id)->get();
    expect($fields)->toHaveCount(2);
    expect($fields[0]->label)->toBe('Nom');
    expect($fields[0]->type)->toBe('text');
    expect($fields[0]->required)->toBeTrue();
    expect($fields[1]->type)->toBe('url');
});

test('un item database créé sans réglage autorise l\'ajout étudiant par défaut', function (): void {
    $course = v20Course('cours-db-default');
    $lesson = v20Lesson($course);
    $owner  = v20Owner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Base libre')
        ->call('addItem', $lesson->id, 'database')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'database')->first();
    expect(DatabaseService::allowsStudentAdd($item))->toBeTrue();
});

test('GESTION DU SCHÉMA réservée au gérant : l\'éditeur est interdit au non-gérant', function (): void {
    $course  = v20Course('cours-db-schema-403');
    $lesson  = v20Lesson($course);
    v20DbItem($lesson);
    $student = v20Student();

    Livewire::actingAs($student)
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();
});

test('syncFields met à jour et soft-supprime les champs retirés', function (): void {
    $item   = v20DbItem(v20Lesson(v20Course('cours-db-sync')));
    $schema = v20Schema($item);
    expect(DatabaseField::forItem($item->id)->count())->toBe(3);

    // On garde uniquement le 1er champ (par id) et on en ajoute un nouveau.
    DatabaseService::syncFields($item, [
        ['id' => $schema['texte']->id, 'label' => 'Nom (modifié)', 'type' => 'text', 'required' => false],
        ['label' => 'Note', 'type' => 'number', 'required' => false],
    ]);

    $fields = DatabaseField::forItem($item->id)->get();
    expect($fields)->toHaveCount(2);
    expect($fields[0]->label)->toBe('Nom (modifié)');
    expect($fields[0]->required)->toBeFalse();
    expect($fields[1]->type)->toBe('number');

    // Les champs retirés sont soft-supprimés (conservés en base).
    expect(DatabaseField::withTrashed()->where('lesson_item_id', $item->id)->count())->toBe(4);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. AJOUTER UNE FICHE (inscrit) + GATING INSCRIPTION
// ─────────────────────────────────────────────────────────────────────────────

test('un inscrit ajoute une fiche (valeurs persistées)', function (): void {
    $course  = v20Course('cours-db-add');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson);
    $schema  = v20Schema($item);
    $student = v20Student();
    v20Enroll($course, $student);

    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $item), [
            'values' => [
                $schema['texte']->id => 'ChatGPT',
                $schema['lien']->id  => 'https://chat.openai.com',
                $schema['choix']->id => 'IA',
            ],
        ])
        ->assertRedirect();

    $entry = DatabaseEntry::where('lesson_item_id', $item->id)->first();
    expect($entry)->not->toBeNull();
    expect($entry->user_id)->toBe($student->id);
    expect($entry->is_approved)->toBeTrue();
    $vals = DatabaseService::valuesByField($entry->load('values'));
    expect($vals[$schema['texte']->id])->toBe('ChatGPT');
    expect($vals[$schema['lien']->id])->toBe('https://chat.openai.com');
});

test('un non-inscrit ne peut pas ajouter de fiche (403)', function (): void {
    $course = v20Course('cours-db-gate');
    $lesson = v20Lesson($course);
    $item   = v20DbItem($lesson);
    $schema = v20Schema($item);
    $user   = v20Student();

    $this->actingAs($user)
        ->post(v20AddUrl($course, $lesson, $item), ['values' => [$schema['texte']->id => 'X']])
        ->assertForbidden();
    expect(DatabaseEntry::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('un anonyme ne peut pas ajouter de fiche (redirigé vers la connexion)', function (): void {
    $course = v20Course('cours-db-gate-anon');
    $lesson = v20Lesson($course);
    $item   = v20DbItem($lesson);
    $schema = v20Schema($item);

    $this->post(v20AddUrl($course, $lesson, $item), ['values' => [$schema['texte']->id => 'X']])->assertRedirect();
    expect(DatabaseEntry::where('lesson_item_id', $item->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. VALIDATION PAR TYPE
// ─────────────────────────────────────────────────────────────────────────────

test('validation par type : number / url / select / required rejetés si invalides', function (): void {
    $course  = v20Course('cours-db-validate');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson);
    $student = v20Student();
    v20Enroll($course, $student);

    // Schéma typé : number (requis), url, select.
    DatabaseService::syncFields($item, [
        ['label' => 'Note', 'type' => 'number', 'required' => true],
        ['label' => 'Site', 'type' => 'url', 'required' => false],
        ['label' => 'Cat',  'type' => 'select', 'required' => false, 'options' => "A\nB"],
    ]);
    $fields = DatabaseService::fields($item);
    [$num, $url, $sel] = [$fields[0], $fields[1], $fields[2]];

    // number requis manquant => erreur ; aucune écriture.
    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $item), ['values' => [$url->id => 'https://ok.test']])
        ->assertSessionHasErrors('values.'.$num->id);

    // number non numérique => erreur.
    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $item), ['values' => [$num->id => 'abc']])
        ->assertSessionHasErrors('values.'.$num->id);

    // url invalide => erreur.
    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $item), ['values' => [$num->id => '5', $url->id => 'pas-une-url']])
        ->assertSessionHasErrors('values.'.$url->id);

    // select hors options => erreur.
    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $item), ['values' => [$num->id => '5', $sel->id => 'Z']])
        ->assertSessionHasErrors('values.'.$sel->id);

    expect(DatabaseEntry::where('lesson_item_id', $item->id)->count())->toBe(0);

    // Soumission valide => OK.
    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $item), ['values' => [$num->id => '7', $url->id => 'https://ok.test', $sel->id => 'A']])
        ->assertRedirect();
    expect(DatabaseEntry::where('lesson_item_id', $item->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. ÉDITER / SUPPRIMER SA FICHE (anti-IDOR fiche d'autrui)
// ─────────────────────────────────────────────────────────────────────────────

test('un inscrit ne peut PAS éditer la fiche d\'un AUTRE (403)', function (): void {
    $course = v20Course('cours-db-idor-own');
    $lesson = v20Lesson($course);
    $item   = v20DbItem($lesson);
    $schema = v20Schema($item);
    $author = v20Student('Auteur');
    $other  = v20Student('Autre');
    v20Enroll($course, $author);
    v20Enroll($course, $other);

    $entry = v20Entry($item, $author, [$schema['texte']->id => 'Original']);

    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/database/entries/{$entry->id}/update";
    $this->actingAs($other)->post($url, ['values' => [$schema['texte']->id => 'Pirate']])->assertForbidden();

    $entry->refresh()->load('values');
    expect(DatabaseService::valuesByField($entry)[$schema['texte']->id])->toBe('Original');
});

test('un inscrit ne peut PAS supprimer la fiche d\'un AUTRE (403) ; l\'auteur oui', function (): void {
    $course = v20Course('cours-db-del-own');
    $lesson = v20Lesson($course);
    $item   = v20DbItem($lesson);
    $schema = v20Schema($item);
    $author = v20Student('Auteur');
    $other  = v20Student('Autre');
    v20Enroll($course, $author);
    v20Enroll($course, $other);

    $entry = v20Entry($item, $author, [$schema['texte']->id => 'A']);
    $url   = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/database/entries/{$entry->id}/delete";

    $this->actingAs($other)->post($url)->assertForbidden();
    expect(DatabaseEntry::where('id', $entry->id)->count())->toBe(1);

    $this->actingAs($author)->post($url)->assertRedirect();
    expect(DatabaseEntry::where('id', $entry->id)->count())->toBe(0);
    expect(DatabaseEntry::withTrashed()->where('id', $entry->id)->count())->toBe(1);
});

test('l\'auteur édite sa propre fiche (valeurs remplacées)', function (): void {
    $course  = v20Course('cours-db-edit-own');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson);
    $schema  = v20Schema($item);
    $student = v20Student();
    v20Enroll($course, $student);

    $entry = v20Entry($item, $student, [$schema['texte']->id => 'Avant']);
    $url   = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/database/entries/{$entry->id}/update";

    $this->actingAs($student)->post($url, ['values' => [$schema['texte']->id => 'Apres']])->assertRedirect();
    $entry->refresh()->load('values');
    expect(DatabaseService::valuesByField($entry)[$schema['texte']->id])->toBe('Apres');
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. MODÉRATION (gérant) + require_approval
// ─────────────────────────────────────────────────────────────────────────────

test('require_approval : la fiche d\'un étudiant naît en attente et reste cachée aux autres', function (): void {
    $course  = v20Course('cours-db-approval');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson, ['require_approval' => true]);
    $schema  = v20Schema($item);
    $author  = v20Student('Auteur');
    $other   = v20Student('Autre');
    v20Enroll($course, $author);
    v20Enroll($course, $other);

    $this->actingAs($author)
        ->post(v20AddUrl($course, $lesson, $item), ['values' => [$schema['texte']->id => 'Brouillon']])
        ->assertRedirect();

    $entry = DatabaseEntry::where('lesson_item_id', $item->id)->first();
    expect($entry->is_approved)->toBeFalse();

    // Un AUTRE inscrit ne voit pas la fiche en attente (filtrage service).
    $visibleForOther = DatabaseService::entries($item, $other->id, false);
    expect($visibleForOther->total())->toBe(0);

    // L'auteur voit SA fiche en attente.
    $visibleForAuthor = DatabaseService::entries($item, $author->id, false);
    expect($visibleForAuthor->total())->toBe(1);
});

test('un gérant approuve une fiche en attente ; un non-gérant ne peut pas (403)', function (): void {
    $course  = v20Course('cours-db-approve');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson, ['require_approval' => true]);
    $schema  = v20Schema($item);
    $owner   = v20Owner($course);
    $author  = v20Student('Auteur');
    $other   = v20Student('Autre');
    v20Enroll($course, $author);
    v20Enroll($course, $other);

    $entry = v20Entry($item, $author, [$schema['texte']->id => 'A'], approved: false);
    $url   = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/database/entries/{$entry->id}/approve";

    $this->actingAs($other)->post($url)->assertForbidden();
    expect($entry->fresh()->is_approved)->toBeFalse();

    $this->actingAs($owner)->post($url)->assertRedirect();
    expect($entry->fresh()->is_approved)->toBeTrue();
});

test('un gérant supprime n\'importe quelle fiche (modération, soft-delete)', function (): void {
    $course  = v20Course('cours-db-mod-del');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson);
    $schema  = v20Schema($item);
    $owner   = v20Owner($course);
    $student = v20Student();
    v20Enroll($course, $student);

    $entry = v20Entry($item, $student, [$schema['texte']->id => 'A']);
    $url   = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/database/entries/{$entry->id}/delete";

    $this->actingAs($owner)->post($url)->assertRedirect();
    expect(DatabaseEntry::where('id', $entry->id)->count())->toBe(0);
    expect(DatabaseEntry::withTrashed()->where('id', $entry->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. allow_student_add
// ─────────────────────────────────────────────────────────────────────────────

test('allow_student_add=false : l\'étudiant ne peut pas ajouter (403), le gérant oui', function (): void {
    $course  = v20Course('cours-db-noadd');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson, ['allow_student_add' => false]);
    $schema  = v20Schema($item);
    $owner   = v20Owner($course);
    $student = v20Student();
    v20Enroll($course, $student);

    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $item), ['values' => [$schema['texte']->id => 'X']])
        ->assertForbidden();
    expect(DatabaseEntry::where('lesson_item_id', $item->id)->count())->toBe(0);

    $this->actingAs($owner)
        ->post(v20AddUrl($course, $lesson, $item), ['values' => [$schema['texte']->id => 'Fiche gérant']])
        ->assertRedirect();
    expect(DatabaseEntry::where('lesson_item_id', $item->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 8. ANTI-XSS
// ─────────────────────────────────────────────────────────────────────────────

test('anti-XSS : une valeur texte avec <script> est neutralisée au rendu', function (): void {
    $item   = v20DbItem(v20Lesson(v20Course('cours-db-xss')));
    $schema = v20Schema($item);

    $html = DatabaseService::renderValue($schema['texte'], 'Bonjour <script>alert(9)</script> fin');
    expect($html)->not->toContain('<script>alert(9)');
});

test('anti-XSS bout en bout : la fiche affichée ne contient pas le script brut', function (): void {
    $course  = v20Course('cours-db-xss-e2e');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson);
    $schema  = v20Schema($item);
    $student = v20Student();
    v20Enroll($course, $student);

    v20Entry($item, $student, [$schema['texte']->id => 'Hi <script>alert(9)</script>']);

    $this->actingAs($student)->get(v20ShowUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee('<script>alert(9)', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 9. HONEYPOT + SÉCURITÉ (anti-IDOR, throttle, bornes)
// ─────────────────────────────────────────────────────────────────────────────

test('honeypot rempli => fiche rejetée SILENCIEUSEMENT (aucune écriture)', function (): void {
    $course  = v20Course('cours-db-hp');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson);
    $schema  = v20Schema($item);
    $student = v20Student();
    v20Enroll($course, $student);

    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $item), [
            'values'              => [$schema['texte']->id => 'Spam'],
            DatabaseService::HONEYPOT => 'http://spam.example',
        ])
        ->assertRedirect();

    expect(DatabaseEntry::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('ANTI-IDOR : ajouter une fiche sur un item d\'un AUTRE cours est refusé (404)', function (): void {
    $courseA = v20Course('cours-db-idor-a');
    $lessonA = v20Lesson($courseA);

    $courseB = v20Course('cours-db-idor-b');
    $lessonB = v20Lesson($courseB);
    $itemB   = v20DbItem($lessonB);
    $schemaB = v20Schema($itemB);

    $student = v20Student();
    v20Enroll($courseA, $student);

    $this->actingAs($student)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/database/entries", ['values' => [$schemaB['texte']->id => 'X']])
        ->assertNotFound();
    expect(DatabaseEntry::where('lesson_item_id', $itemB->id)->count())->toBe(0);
});

test('ANTI-IDOR : approuver la fiche d\'un AUTRE cours via sa propre route est refusé (404)', function (): void {
    $courseA = v20Course('cours-db-idor2-a');
    $lessonA = v20Lesson($courseA);
    $itemA   = v20DbItem($lessonA);
    $ownerA  = v20Owner($courseA);

    $courseB = v20Course('cours-db-idor2-b');
    $lessonB = v20Lesson($courseB);
    $itemB   = v20DbItem($lessonB);
    $schemaB = v20Schema($itemB);
    $entryB  = v20Entry($itemB, null, [$schemaB['texte']->id => 'B'], approved: false);

    $this->actingAs($ownerA)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemA->id}/database/entries/{$entryB->id}/approve")
        ->assertNotFound();
    expect($entryB->fresh()->is_approved)->toBeFalse();
});

test('la route d\'ajout de fiche est throttlée (429 après le quota)', function (): void {
    $course  = v20Course('cours-db-throttle');
    $lesson  = v20Lesson($course);
    $item    = v20DbItem($lesson);
    $schema  = v20Schema($item);
    $student = v20Student();
    v20Enroll($course, $student);

    $statuses = [];
    for ($i = 0; $i < 25; $i++) {
        $statuses[] = $this->actingAs($student)
            ->post(v20AddUrl($course, $lesson, $item), ['values' => [$schema['texte']->id => 'Fiche '.$i]])
            ->getStatusCode();
    }

    expect($statuses)->toContain(429);
});

// ─────────────────────────────────────────────────────────────────────────────
// 10. RÉTROCOMPAT
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : ajouter une fiche sur un item NON-database (document) est refusé (404)', function (): void {
    $course  = v20Course('cours-db-retro');
    $lesson  = v20Lesson($course);
    $doc     = LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'document',
        'title'     => 'Doc',
        'position'  => 1,
        'payload'   => ['rich_text' => 'Notes'],
    ]);
    $student = v20Student();
    v20Enroll($course, $student);

    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $doc), ['values' => []])
        ->assertNotFound();
});

test('rétrocompat : les défauts d\'achèvement des autres types sont inchangés ; database => add', function (): void {
    $lesson = v20Lesson(v20Course('cours-db-retro-defaults'));
    $video  = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'video', 'title' => 'V', 'position' => 1, 'payload' => []]);
    $quiz   = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'quiz', 'title' => 'Q', 'position' => 2, 'payload' => []]);
    $wiki   = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'wiki', 'title' => 'W', 'position' => 3, 'payload' => []]);
    $db     = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'database', 'title' => 'B', 'position' => 4, 'payload' => []]);

    expect(ActivityCompletionService::criterionFor($video))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($quiz))->toBe('min_grade');
    expect(ActivityCompletionService::criterionFor($wiki))->toBe('edit');
    expect(ActivityCompletionService::criterionFor($db))->toBe('add'); // database => achèvement par ajout de fiche
});

// C3 [règle 10] : aucun tiret cadratin dans les vues touchées.
test('aucun tiret cadratin dans les vues database/éditeur touchées', function (): void {
    foreach ([
        base_path('Modules/Academy/resources/views/public/lesson.blade.php'),
        base_path('Modules/Academy/resources/views/public/partials/database-field.blade.php'),
        base_path('Modules/Academy/resources/views/livewire/course-editor.blade.php'),
    ] as $path) {
        expect(file_get_contents($path))->not->toContain('—');
    }
});

// BUG-1 : auto-complétion sur participation (comme le forum).
test('ajouter une fiche auto-complète l\'item base de données (critère add)', function (): void {
    $course = v20Course('cours-db-autocomplete');
    $lesson = v20Lesson($course);
    $item = v20DbItem($lesson);
    $schema = v20Schema($item);
    $student = v20Student();
    v20Enroll($course, $student);

    expect(\Modules\Academy\Models\Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->exists())->toBeFalse();

    $this->actingAs($student)
        ->post(v20AddUrl($course, $lesson, $item), [
            'values' => [
                $schema['texte']->id => 'ChatGPT',
                $schema['lien']->id  => 'https://chat.openai.com',
                $schema['choix']->id => 'IA',
            ],
        ])
        ->assertRedirect();

    expect(\Modules\Academy\Models\Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->exists())->toBeTrue();
});
