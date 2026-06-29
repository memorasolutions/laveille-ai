<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TESTS GOLDEN-MASTER / CARACTÉRISATION — CourseEditor : blocs restrictions (V5-d)
 * et feedback global par tranche de score (V1-a).
 *
 * OBJECTIF : FIGER le comportement ACTUEL de ces 2 blocs AVANT toute extraction
 * en traits/services. Ces tests décrivent CE QUI EST, pas ce qui devrait
 * idéalement être. Si un comportement paraît étrange, on le fige tel quel
 * (commentaire CARACTÉRISATION ou BIZARRERIE).
 *
 * COUVERTURE :
 *  RC. Restrictions d'accès par item (V5-d)
 *      RC1  : loadItemRestrictions — tampon vide si aucune restriction dans le payload
 *      RC2  : loadItemRestrictions — charge match + conditions depuis un payload existant
 *      RC3  : loadItemRestrictions — match non reconnu → normalisé à 'all'
 *      RC4  : cancelItemRestrictions — vide le tampon sans persister quoi que ce soit
 *      RC5  : addRestrictionCondition type=completion — forme vierge attendue
 *      RC6  : addRestrictionCondition type=date — forme vierge attendue
 *      RC7  : addRestrictionCondition type=grade — forme vierge attendue
 *      RC8  : addRestrictionCondition type=group — forme vierge attendue
 *      RC9  : addRestrictionCondition type invalide → repli sur type=completion
 *      RC10 : addRestrictionCondition sans loadItemRestrictions préalable → silencieux
 *      RC11 : removeRestrictionCondition — retire l'index donné et réindexe
 *      RC12 : removeRestrictionCondition — index inexistant → silencieux
 *      RC13 : saveItemRestrictions — conditions valides → payload persisté
 *      RC14 : saveItemRestrictions — conditions vides → retire access_restrictions du payload
 *      RC15 : saveItemRestrictions — reflète l'état sanitisé dans le tampon Livewire
 *      RC16 : restrictionRefItems — exclut l'item courant et liste les autres items du cours
 *
 *  OF. Feedback global par tranche de score — tampon Livewire (V1-a)
 *      OF1  : loadOverallFeedback — charge les bornes existantes depuis le payload
 *      OF2  : loadOverallFeedback — payload sans bornes → 1 ligne vierge {min_percent:80, message:''}
 *      OF3  : addOverallBoundary — auto-initialise le tampon si absent, ajoute {min_percent:0, message:''}
 *      OF4  : addOverallBoundary — garde-fou MAX_BOUNDARIES (pas de dépassement)
 *      OF5  : removeOverallBoundary — retire l'index donné et réindexe
 *      OF6  : removeOverallBoundary — index inexistant → silencieux
 *      OF7  : saveOverallFeedback — reflète l'état NORMALISÉ dans le tampon (tri DESC)
 *      OF8  : saveOverallFeedback — liste vide → retire la clé du payload, tampon = 1 ligne vierge
 *
 * GARDE-FOU : si le module Academy est désactivé, tous les tests sont SKIPPED.
 * PRÉFIXE helpers : `gmCERS_` (golden master CourseEditor Restrictions & Score — évite collision).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\QuizFeedbackService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe gmCERS_ pour éviter toute collision inter-fichiers)
// ─────────────────────────────────────────────────────────────────────────────

function gmCERS_makeCourse(string $slug = 'cours-cers'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours CERS',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);
}

function gmCERS_makeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name'       => 'academy.manage',
                'guard_name' => 'web',
            ])
        );
    }

    return $admin;
}

function gmCERS_addLesson(Course $course, string $title = 'Leçon CERS', int $chapPos = 1): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre CERS '.$chapPos,
        'position'  => $chapPos,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => $title,
        'slug'       => \Illuminate\Support\Str::slug($title).'-'.$chapter->id,
        'position'   => 1,
    ]);
}

function gmCERS_addItem(Lesson $lesson, string $type = 'document', int $position = 1, array $payload = []): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => $type,
        'title'       => ucfirst($type).' CERS '.$position,
        'position'    => $position,
        'payload'     => $payload,
        'is_required' => false,
    ]);
}

/** Item quiz pré-configuré pour les tests de feedback global (OF). */
function gmCERS_quizItem(Lesson $lesson): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz CERS',
        'position'    => 1,
        'payload'     => ['passing_score' => 60],
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

    $this->course = gmCERS_makeCourse();
    $this->admin  = gmCERS_makeAdmin();
    $this->lesson = gmCERS_addLesson($this->course);
});

// ─────────────────────────────────────────────────────────────────────────────
// RC. RESTRICTIONS D'ACCÈS PAR ITEM (V5-d)
// ─────────────────────────────────────────────────────────────────────────────

test('RC1 : loadItemRestrictions — tampon vide si aucune restriction dans le payload', function (): void {
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id);

    $tampon = $component->get("editRestrictions.{$item->id}");

    // Le tampon est initialisé (non null) mais sans condition.
    expect($tampon)->not->toBeNull();
    expect($tampon['match'])->toBe('all');        // défaut 'all'
    expect($tampon['conditions'])->toBeArray();
    expect($tampon['conditions'])->toBeEmpty();
});

test('RC2 : loadItemRestrictions — charge match et conditions depuis un payload existant', function (): void {
    $ref  = gmCERS_addItem($this->lesson, 'document', 1);
    $item = gmCERS_addItem($this->lesson, 'document', 2);

    // Pose une restriction directement dans le payload (comme si elle avait été
    // sauvegardée précédemment via saveItemRestrictions).
    $item->update(['payload' => [
        'access_restrictions' => [
            'match'      => 'any',
            'conditions' => [
                ['type' => 'completion', 'item_id' => $ref->id, 'hide' => false],
            ],
        ],
    ]]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id);

    $tampon = $component->get("editRestrictions.{$item->id}");
    expect($tampon['match'])->toBe('any');
    expect($tampon['conditions'])->toHaveCount(1);
    expect($tampon['conditions'][0]['type'])->toBe('completion');
    expect($tampon['conditions'][0]['item_id'])->toBe($ref->id);
    expect($tampon['conditions'][0]['hide'])->toBeFalse();
});

test('RC3 : loadItemRestrictions — match non reconnu → normalisé à \'all\'', function (): void {
    // CARACTÉRISATION : la normalisation est `=== 'any' ? 'any' : 'all'`.
    // Seul 'any' passe exactement ; tout autre valeur ('ALL', 'invalid', …) → 'all'.
    $item = gmCERS_addItem($this->lesson);
    $item->update(['payload' => [
        'access_restrictions' => [
            'match'      => 'invalid_value',
            'conditions' => [],
        ],
    ]]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id);

    expect($component->get("editRestrictions.{$item->id}.match"))->toBe('all');
});

test('RC4 : cancelItemRestrictions — vide le tampon sans persister quoi que ce soit', function (): void {
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id);

    // Le tampon est chargé.
    expect($component->get("editRestrictions.{$item->id}"))->not->toBeNull();

    // Ajouter une condition au tampon (sans sauvegarder).
    $component->call('addRestrictionCondition', $item->id, 'date');
    expect($component->get("editRestrictions.{$item->id}.conditions"))->toHaveCount(1);

    // Cancel vide le tampon (unset).
    $component->call('cancelItemRestrictions', $item->id);
    expect($component->get("editRestrictions.{$item->id}"))->toBeNull();

    // CARACTÉRISATION : rien n'a changé en DB (payload sans clé access_restrictions).
    $payload = $item->fresh()->payload ?? [];
    expect($payload)->not->toHaveKey('access_restrictions');
});

test('RC5 : addRestrictionCondition type=completion — forme vierge attendue', function (): void {
    // CARACTÉRISATION : completion → {type, item_id: 0, hide: false}
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id)
        ->call('addRestrictionCondition', $item->id, 'completion');

    $conditions = $component->get("editRestrictions.{$item->id}.conditions");
    expect($conditions)->toHaveCount(1);

    $blank = $conditions[0];
    expect($blank['type'])->toBe('completion');
    expect($blank['item_id'])->toBe(0);
    expect($blank['hide'])->toBeFalse();
});

test('RC6 : addRestrictionCondition type=date — forme vierge attendue', function (): void {
    // CARACTÉRISATION : date → {type, from: '', until: '', hide: false}
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id)
        ->call('addRestrictionCondition', $item->id, 'date');

    $blank = $component->get("editRestrictions.{$item->id}.conditions.0");
    expect($blank['type'])->toBe('date');
    expect($blank['from'])->toBe('');
    expect($blank['until'])->toBe('');
    expect($blank['hide'])->toBeFalse();
});

test('RC7 : addRestrictionCondition type=grade — forme vierge attendue', function (): void {
    // CARACTÉRISATION : grade → {type, item_id: 0, min_percent: 50, hide: false}
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id)
        ->call('addRestrictionCondition', $item->id, 'grade');

    $blank = $component->get("editRestrictions.{$item->id}.conditions.0");
    expect($blank['type'])->toBe('grade');
    expect($blank['item_id'])->toBe(0);
    expect($blank['min_percent'])->toBe(50);
    expect($blank['hide'])->toBeFalse();
});

test('RC8 : addRestrictionCondition type=group — forme vierge attendue', function (): void {
    // CARACTÉRISATION : group → {type, group_id: 0, hide: false}
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id)
        ->call('addRestrictionCondition', $item->id, 'group');

    $blank = $component->get("editRestrictions.{$item->id}.conditions.0");
    expect($blank['type'])->toBe('group');
    expect($blank['group_id'])->toBe(0);
    expect($blank['hide'])->toBeFalse();
});

test('RC9 : addRestrictionCondition type invalide → repli sur type=completion', function (): void {
    // CARACTÉRISATION : `if (! in_array($type, AccessRestrictionService::TYPES, true))`
    // → $type = 'completion' ; le match/default retourne aussi completion.
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id)
        ->call('addRestrictionCondition', $item->id, 'type_inexistant');

    $blank = $component->get("editRestrictions.{$item->id}.conditions.0");
    expect($blank['type'])->toBe('completion');
    expect($blank['item_id'])->toBe(0);
    expect($blank['hide'])->toBeFalse();
});

test('RC10 : addRestrictionCondition sans loadItemRestrictions préalable → silencieux', function (): void {
    // CARACTÉRISATION : `if (! isset($this->editRestrictions[$itemId])) { return; }`
    // → no-op complet ; aucune exception, le tampon reste absent.
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('addRestrictionCondition', $item->id, 'completion')
        ->assertHasNoErrors();

    // Le tampon reste absent (null / non initialisé).
    expect($component->get("editRestrictions.{$item->id}"))->toBeNull();
});

test('RC11 : removeRestrictionCondition — retire l\'index donné et réindexe le tableau', function (): void {
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id)
        ->call('addRestrictionCondition', $item->id, 'date')
        ->call('addRestrictionCondition', $item->id, 'completion')
        ->call('addRestrictionCondition', $item->id, 'grade');

    // 3 conditions : date (0), completion (1), grade (2).
    expect($component->get("editRestrictions.{$item->id}.conditions"))->toHaveCount(3);

    // Retire la condition à l'index 1 (completion).
    $component->call('removeRestrictionCondition', $item->id, 1);

    $conditions = $component->get("editRestrictions.{$item->id}.conditions");
    // CARACTÉRISATION : 2 conditions restantes, réindexées 0 et 1.
    expect($conditions)->toHaveCount(2);
    expect(array_keys($conditions))->toBe([0, 1]);
    expect($conditions[0]['type'])->toBe('date');
    expect($conditions[1]['type'])->toBe('grade'); // ancienne index 2 → maintenant index 1
});

test('RC12 : removeRestrictionCondition — index inexistant → silencieux', function (): void {
    // CARACTÉRISATION : `if (! isset($this->editRestrictions[$itemId]['conditions'][$index])) { return; }`
    // → aucune exception, aucun changement au tampon.
    $item = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $item->id)
        ->call('addRestrictionCondition', $item->id, 'date')
        ->call('removeRestrictionCondition', $item->id, 999) // index inexistant
        ->assertHasNoErrors();

    // La condition date (index 0) est toujours présente.
    expect($component->get("editRestrictions.{$item->id}.conditions"))->toHaveCount(1);
    expect($component->get("editRestrictions.{$item->id}.conditions.0.type"))->toBe('date');
});

test('RC13 : saveItemRestrictions — conditions valides → payload persisté avec match et conditions', function (): void {
    // CARACTÉRISATION : une condition de type completion avec item_id d'un item du même
    // cours passe la sanitize et est écrite dans payload['access_restrictions'].
    $lesson2 = gmCERS_addLesson($this->course, 'Leçon 2 CERS', 2);
    $ref     = gmCERS_addItem($this->lesson, 'document', 1);
    $target  = gmCERS_addItem($lesson2, 'document', 1);

    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $target->id)
        ->set("editRestrictions.{$target->id}.match", 'any')
        ->set("editRestrictions.{$target->id}.conditions.0", [
            'type'    => 'completion',
            'item_id' => $ref->id,
            'hide'    => false,
        ])
        ->call('saveItemRestrictions', $target->id)
        ->assertHasNoErrors();

    $payload = $target->fresh()->payload;
    expect($payload['access_restrictions']['match'])->toBe('any');
    expect($payload['access_restrictions']['conditions'])->toHaveCount(1);
    expect($payload['access_restrictions']['conditions'][0]['type'])->toBe('completion');
    expect($payload['access_restrictions']['conditions'][0]['item_id'])->toBe($ref->id);
    expect($payload['access_restrictions']['conditions'][0]['hide'])->toBeFalse();
});

test('RC14 : saveItemRestrictions — conditions vides → retire la clé access_restrictions du payload', function (): void {
    // CARACTÉRISATION : `count($cleanConds) === 0 → unset($payload['access_restrictions'])`.
    // Un item sans la clé = toujours accessible (rétrocompat stricte).
    $target = gmCERS_addItem($this->lesson);

    // Le tampon après load est {match: 'all', conditions: []} car aucune restriction existante.
    Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $target->id)
        ->call('saveItemRestrictions', $target->id)
        ->assertHasNoErrors();

    $payload = $target->fresh()->payload ?? [];
    // CARACTÉRISATION : la clé 'access_restrictions' est absente du payload.
    expect($payload)->not->toHaveKey('access_restrictions');
});

test('RC15 : saveItemRestrictions — reflète l\'état sanitisé dans le tampon Livewire', function (): void {
    // CARACTÉRISATION : après save, le tampon est mis à jour avec $cleanConds
    // (les conditions rejetées par sanitize disparaissent du tampon — visible en UI).
    $target = gmCERS_addItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadItemRestrictions', $target->id)
        ->set("editRestrictions.{$target->id}.conditions.0", [
            'type'    => 'completion',
            'item_id' => 99999, // item_id invalide (hors du cours) → rejeté par sanitize
            'hide'    => false,
        ])
        ->call('saveItemRestrictions', $target->id);

    // Le tampon est mis à jour avec les conditions sanitisées = vide.
    $tampon = $component->get("editRestrictions.{$target->id}");
    expect($tampon)->not->toBeNull();
    expect($tampon['conditions'])->toBeEmpty();
});

test('RC16 : restrictionRefItems — exclut l\'item courant et liste les autres items du cours', function (): void {
    // CARACTÉRISATION : restrictionRefItems($currentItemId) charge tous les items de
    // tous les chapitres/leçons du cours et exclut $currentItemId. La structure
    // retournée est [{id, title, type}, …].
    $lesson2 = gmCERS_addLesson($this->course, 'Leçon 2 CERS', 2);
    $itemA   = gmCERS_addItem($this->lesson, 'document', 1);
    $itemB   = gmCERS_addItem($lesson2, 'quiz',     1);   // item courant (exclu)
    $itemC   = gmCERS_addItem($lesson2, 'document', 2);

    $lw = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course]);

    // Appel direct via instance() : la méthode retourne un tableau (pas de mutation d'état).
    $refs = $lw->instance()->restrictionRefItems($itemB->id);

    $ids = array_column($refs, 'id');
    expect($ids)->toContain($itemA->id);
    expect($ids)->toContain($itemC->id);
    // CARACTÉRISATION : l'item courant est exclu de la liste.
    expect($ids)->not->toContain($itemB->id);

    // Forme attendue de chaque entrée.
    expect($refs[0])->toHaveKeys(['id', 'title', 'type']);
    expect($refs[0]['type'])->toBeString();
    expect($refs[0]['title'])->toBeString();
});

// ─────────────────────────────────────────────────────────────────────────────
// OF. FEEDBACK GLOBAL PAR TRANCHE DE SCORE — tampon Livewire (V1-a)
// ─────────────────────────────────────────────────────────────────────────────

test('OF1 : loadOverallFeedback — charge les bornes existantes depuis le payload', function (): void {
    // CARACTÉRISATION : chaque entrée valide de payload['overall_feedback'] est chargée
    // en convertissant min_percent en int (cast explicite) et message en string.
    $item = gmCERS_quizItem($this->lesson);
    $item->update(['payload' => array_merge($item->payload, [
        'overall_feedback' => [
            ['min_percent' => 80, 'message' => 'Excellent'],
            ['min_percent' => 50, 'message' => 'Passable'],
        ],
    ])]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadOverallFeedback', $item->id);

    $rows = $component->get("overallFeedback.{$item->id}");
    expect($rows)->toHaveCount(2);
    expect($rows[0]['min_percent'])->toBe(80);
    expect($rows[0]['message'])->toBe('Excellent');
    expect($rows[1]['min_percent'])->toBe(50);
    expect($rows[1]['message'])->toBe('Passable');
});

test('OF2 : loadOverallFeedback — payload sans bornes → 1 ligne vierge {min_percent:80, message:\'\'}', function (): void {
    // CARACTÉRISATION : si payload['overall_feedback'] est absent ou vide,
    // une ligne vierge {min_percent: 80, message: ''} est ajoutée pour que l'UI
    // propose toujours un point de départ sans rester entièrement vide.
    $item = gmCERS_quizItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadOverallFeedback', $item->id);

    $rows = $component->get("overallFeedback.{$item->id}");
    expect($rows)->toHaveCount(1);
    expect($rows[0]['min_percent'])->toBe(80);
    expect($rows[0]['message'])->toBe('');
});

test('OF3 : addOverallBoundary — auto-initialise le tampon si absent, ajoute {min_percent:0, message:\'\'}', function (): void {
    // CARACTÉRISATION BIZARRERIE : addOverallBoundary peut être appelée sans load préalable.
    // Elle initialise le tampon à [] puis pousse {min_percent: 0, message: ''}.
    // C'est différent de addRestrictionCondition qui est un no-op sans load.
    $item = gmCERS_quizItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('addOverallBoundary', $item->id); // SANS load préalable

    $rows = $component->get("overallFeedback.{$item->id}");
    expect($rows)->toHaveCount(1);
    expect($rows[0]['min_percent'])->toBe(0);
    expect($rows[0]['message'])->toBe('');
});

test('OF4 : addOverallBoundary — garde-fou MAX_BOUNDARIES (pas de dépassement)', function (): void {
    // CARACTÉRISATION : la garde `count >= MAX_BOUNDARIES → return` bloque l'ajout
    // de la (MAX_BOUNDARIES + 1)e borne. La liste reste exactement à MAX_BOUNDARIES.
    $item = gmCERS_quizItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course]);

    $extra = QuizFeedbackService::MAX_BOUNDARIES + 1;
    for ($i = 0; $i < $extra; $i++) {
        $component->call('addOverallBoundary', $item->id);
    }

    $rows = $component->get("overallFeedback.{$item->id}");
    expect($rows)->toHaveCount(QuizFeedbackService::MAX_BOUNDARIES);
});

test('OF5 : removeOverallBoundary — retire l\'index donné et réindexe le tableau', function (): void {
    $item = gmCERS_quizItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadOverallFeedback', $item->id); // 1 ligne vierge {80, ''}

    // Ajouter 2 bornes supplémentaires → 3 lignes totales.
    $component->call('addOverallBoundary', $item->id); // {0, ''} index 1
    $component->call('addOverallBoundary', $item->id); // {0, ''} index 2

    // Donner des messages distincts pour identifier chaque ligne.
    $component->set("overallFeedback.{$item->id}.0.message", 'Alpha');
    $component->set("overallFeedback.{$item->id}.1.message", 'Beta');
    $component->set("overallFeedback.{$item->id}.2.message", 'Gamma');

    // Retire la ligne à l'index 1 (message 'Beta').
    $component->call('removeOverallBoundary', $item->id, 1);

    $rows = $component->get("overallFeedback.{$item->id}");
    // CARACTÉRISATION : 2 lignes restantes, réindexées via array_values().
    expect($rows)->toHaveCount(2);
    expect(array_keys($rows))->toBe([0, 1]);
    expect($rows[0]['message'])->toBe('Alpha');
    expect($rows[1]['message'])->toBe('Gamma'); // ancienne index 2 → maintenant index 1
});

test('OF6 : removeOverallBoundary — index inexistant → silencieux', function (): void {
    // CARACTÉRISATION : `if (! isset($this->overallFeedback[$itemId][$index])) { return; }`
    // → aucune exception, le tampon est inchangé.
    $item = gmCERS_quizItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadOverallFeedback', $item->id)
        ->call('removeOverallBoundary', $item->id, 999) // index inexistant
        ->assertHasNoErrors();

    // La ligne vierge initiale reste intacte.
    expect($component->get("overallFeedback.{$item->id}"))->toHaveCount(1);
    expect($component->get("overallFeedback.{$item->id}.0.min_percent"))->toBe(80);
});

test('OF7 : saveOverallFeedback — reflète l\'état NORMALISÉ dans le tampon Livewire', function (): void {
    // CARACTÉRISATION : après save, le tampon $overallFeedback[$itemId] est mis à jour
    // avec $clean (bornes normalisées et triées DESC), pas les valeurs raw saisies.
    // L'UI voit le résultat normalisé immédiatement, sans rechargement de la page.
    $item = gmCERS_quizItem($this->lesson);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadOverallFeedback', $item->id)
        ->set("overallFeedback.{$item->id}", [
            ['min_percent' => 30, 'message' => 'Insuffisant'], // ordre non trié
            ['min_percent' => 80, 'message' => 'Excellent'],
            ['min_percent' => 50, 'message' => 'Passable'],
        ])
        ->call('saveOverallFeedback', $item->id)
        ->assertHasNoErrors();

    $rows = $component->get("overallFeedback.{$item->id}");
    // CARACTÉRISATION : le tampon reflète le tri DESC après normalisation (80, 50, 30).
    expect(array_column($rows, 'min_percent'))->toBe([80, 50, 30]);
    expect($rows[0]['message'])->toBe('Excellent');
    expect($rows[2]['message'])->toBe('Insuffisant');
});

test('OF8 : saveOverallFeedback — liste vide → retire la clé du payload, tampon = 1 ligne vierge', function (): void {
    // CARACTÉRISATION : normalizeBoundaries ignore les entrées à message vide.
    // Si $clean = [] : payload → unset('overall_feedback') ET tampon → [{80, ''}].
    // Le tampon ne reste jamais vide pour ne pas laisser l'UI sans point de départ.
    $item = gmCERS_quizItem($this->lesson);

    // Pose une borne existante en DB.
    $item->update(['payload' => array_merge($item->payload, [
        'overall_feedback' => [['min_percent' => 80, 'message' => 'Bien']],
    ])]);

    $component = Livewire::actingAs($this->admin)
        ->test(CourseEditor::class, ['course' => $this->course])
        ->call('loadOverallFeedback', $item->id)
        // Vider le message de la seule borne (message vide → ignoré par normalizeBoundaries).
        ->set("overallFeedback.{$item->id}.0.message", '')
        ->call('saveOverallFeedback', $item->id)
        ->assertHasNoErrors();

    // DB : la clé overall_feedback est retirée du payload.
    expect($item->fresh()->payload)->not->toHaveKey('overall_feedback');

    // Tampon Livewire : rétabli à 1 ligne vierge {min_percent: 80, message: ''}.
    $rows = $component->get("overallFeedback.{$item->id}");
    expect($rows)->toHaveCount(1);
    expect($rows[0]['min_percent'])->toBe(80);
    expect($rows[0]['message'])->toBe('');
});
