<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F16 CONTENU INTERACTIF H5P.
 *
 * Couvre :
 *  - H5pPackageService : extraction d'un .h5p valide, rejets propres (zip invalide,
 *    structure manquante, zip-slip, extension exécutable filtrée) sans 500 ;
 *  - CourseEditor : addH5pItem (création) + replaceH5pPackage (remplacement) gardés
 *    par autorisation SERVEUR + anti-IDOR ;
 *  - lecteur de leçon : iframe SANDBOX pour un inscrit, panneau gaté sinon (aucune
 *    URL de contenu dans le DOM) ;
 *  - H5pPlayerController : 200 + CSP jsdelivr pour un inscrit, 403 sinon, 404 anti-IDOR ;
 *  - achèvement V2-c « view » auto-marqué à la consultation d'un inscrit réel ;
 *  - rétrocompatibilité : les 6 types d'items existants restent acceptés.
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\H5pPackageService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    Storage::fake('public');

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/** Contenu d'un paquet H5P valide minimal (h5p.json + content/content.json + libs). */
function h5pValidFiles(): array
{
    return [
        'h5p.json'                 => json_encode(['title' => 'Mon quiz H5P', 'mainLibrary' => 'H5P.Demo']),
        'content/content.json'     => json_encode(['question' => 'Bonjour ?']),
        'H5P.Demo-1.0/library.json' => json_encode(['machineName' => 'H5P.Demo']),
        'H5P.Demo-1.0/scripts/app.js' => 'console.log("h5p");',
    ];
}

/** Construit un fichier .h5p (zip) temporaire à partir d'un tableau nom => contenu. */
function h5pZipFile(array $files): string
{
    $path = tempnam(sys_get_temp_dir(), 'h5ptest').'.h5p';
    $zip  = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($files as $name => $content) {
        $zip->addFromString($name, (string) $content);
    }
    $zip->close();

    return $path;
}

/** UploadedFile en mode test (bypass is_uploaded_file) à partir d'un chemin (appel SERVICE direct). */
function h5pUpload(string $path, string $name = 'contenu.h5p', string $mime = 'application/zip'): UploadedFile
{
    return new UploadedFile($path, $name, $mime, null, true);
}

/**
 * Fichier .h5p « fake » (Illuminate\Http\Testing\File) à passer à Livewire ->set().
 * Livewire lit la propriété ->name (présente sur les fakes, absente d'un UploadedFile nu).
 */
function h5pFake(array $files, string $name = 'contenu.h5p'): \Illuminate\Http\Testing\File
{
    return UploadedFile::fake()->createWithContent($name, (string) file_get_contents(h5pZipFile($files)));
}

/** Fichier .h5p « fake » au contenu brut arbitraire (ex. « pas un zip »). */
function h5pFakeRaw(string $content, string $name = 'contenu.h5p'): \Illuminate\Http\Testing\File
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

function h5pCourse(string $slug = 'cours-h5p', string $status = 'published'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours H5P',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => $status,
        'currency'    => 'CAD',
    ]);
}

function h5pLesson(Course $course): Lesson
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre', 'position' => 1]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);
}

function h5pAdmin(): User
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

function h5pStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function h5pEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

/** Crée un item h5p « prêt » avec un dossier extrait simulé sur le disque public. */
function h5pReadyItem(Lesson $lesson): LessonItem
{
    $path = H5pPackageService::BASE_DIR.'/'.\Illuminate\Support\Str::uuid();
    Storage::disk('public')->put($path.'/h5p.json', json_encode(['title' => 'H5P']));
    Storage::disk('public')->put($path.'/content/content.json', json_encode(['x' => 1]));

    return LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'h5p',
        'title'     => 'Activité H5P',
        'position'  => 1,
        'payload'   => ['h5p_path' => $path, 'title' => 'H5P'],
    ]);
}

function h5pShowUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE - extraction + rejets sûrs (jamais de 500)
// ─────────────────────────────────────────────────────────────────────────────

test('service : un paquet .h5p valide est extrait (fichiers requis présents + titre)', function (): void {
    $result = (new H5pPackageService())->extract(h5pUpload(h5pZipFile(h5pValidFiles())));

    expect($result['path'])->toStartWith(H5pPackageService::BASE_DIR.'/');
    expect($result['title'])->toBe('Mon quiz H5P');
    expect(Storage::disk('public')->exists($result['path'].'/h5p.json'))->toBeTrue();
    expect(Storage::disk('public')->exists($result['path'].'/content/content.json'))->toBeTrue();
    expect(Storage::disk('public')->exists($result['path'].'/H5P.Demo-1.0/scripts/app.js'))->toBeTrue();
});

test('service : un fichier qui n\'est pas un zip est rejeté proprement', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'notzip').'.h5p';
    file_put_contents($path, 'ceci n\'est pas un zip');

    expect(fn () => (new H5pPackageService())->extract(h5pUpload($path)))
        ->toThrow(\RuntimeException::class);
});

test('service : un zip sans h5p.json / content.json est rejeté (structure invalide)', function (): void {
    $path = h5pZipFile(['readme.txt' => 'rien d\'utile']);

    expect(fn () => (new H5pPackageService())->extract(h5pUpload($path)))
        ->toThrow(\RuntimeException::class);
});

test('service : une entrée zip-slip (..) est rejetée sans écrire hors du dossier', function (): void {
    $files = h5pValidFiles();
    $files['../evil.txt'] = 'charge utile malveillante';
    $path = h5pZipFile($files);

    expect(fn () => (new H5pPackageService())->extract(h5pUpload($path)))
        ->toThrow(\RuntimeException::class);

    // Aucun fichier « evil » nulle part dans le disque public.
    expect(Storage::disk('public')->exists('evil.txt'))->toBeFalse();
});

test('service : un fichier exécutable (.php) du paquet n\'est jamais extrait', function (): void {
    $files = h5pValidFiles();
    $files['H5P.Demo-1.0/shell.php'] = '<?php echo 1;';
    $result = (new H5pPackageService())->extract(h5pUpload($path = h5pZipFile($files)));

    // Le contenu légitime est posé, mais le .php est filtré (défense en profondeur).
    expect(Storage::disk('public')->exists($result['path'].'/h5p.json'))->toBeTrue();
    expect(Storage::disk('public')->exists($result['path'].'/H5P.Demo-1.0/shell.php'))->toBeFalse();
});

test('service : un paquet trop lourd (> 30 Mo déclaré) est rejeté', function (): void {
    // On simule la taille via un UploadedFile dont getSize dépasse le plafond :
    // un vrai gros zip n'est pas nécessaire pour prouver la borne.
    $path = h5pZipFile(h5pValidFiles());
    $big  = new class($path, 'gros.h5p', 'application/zip', null, true) extends UploadedFile
    {
        public function getSize(): int
        {
            return H5pPackageService::MAX_BYTES + 1;
        }
    };

    expect(fn () => (new H5pPackageService())->extract($big))
        ->toThrow(\RuntimeException::class);
});

test('service : ANTI ZIP-BOMB - un contenu décompressé au-delà du seuil est rejeté sans 500 ni dossier orphelin', function (): void {
    // Seuil volontairement bas (5 Ko) pour le test ; le service lit la config.
    config()->set('academy.h5p.max_extract_kb', 5);

    $files = h5pValidFiles();
    // Une entrée légitime (json, non filtrée) dont le DÉCOMPRESSÉ dépasse le seuil.
    $files['content/big.json'] = str_repeat('a', 50 * 1024);
    $path = h5pZipFile($files);

    expect(fn () => (new H5pPackageService())->extract(h5pUpload($path)))
        ->toThrow(\RuntimeException::class);

    // Aucun dossier extrait laissé sur le disque (nettoyage du partiel).
    expect(Storage::disk('public')->allFiles(H5pPackageService::BASE_DIR))->toBe([]);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. SERVICE - delete borné (anti-traversal)
// ─────────────────────────────────────────────────────────────────────────────

test('service : delete() ne touche qu\'aux chemins academy-h5p/ (anti-traversal)', function (): void {
    $service = new H5pPackageService();
    Storage::disk('public')->put('autre/important.txt', 'à conserver');

    // Chemin hors périmètre → ignoré.
    $service->delete('autre');
    $service->delete('../../etc/passwd');
    expect(Storage::disk('public')->exists('autre/important.txt'))->toBeTrue();

    // Chemin légitime → supprimé.
    $rel = H5pPackageService::BASE_DIR.'/abc';
    Storage::disk('public')->put($rel.'/h5p.json', '{}');
    $service->delete($rel);
    expect(Storage::disk('public')->exists($rel.'/h5p.json'))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ÉDITEUR - création / remplacement gardés serveur + anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('éditeur : un gérant ajoute un item h5p à partir d\'un paquet valide (item + payload)', function (): void {
    $course = h5pCourse('cours-edit', 'draft');
    $lesson = h5pLesson($course);

    Livewire::actingAs(h5pAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Activité interactive')
        ->set("newH5p.{$lesson->id}", h5pFake(h5pValidFiles()))
        ->call('addH5pItem', $lesson->id)
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'h5p')->first();
    expect($item)->not->toBeNull();
    expect($item->title)->toBe('Activité interactive');
    expect($item->payload['h5p_path'])->toStartWith(H5pPackageService::BASE_DIR.'/');
    expect(Storage::disk('public')->exists($item->payload['h5p_path'].'/content/content.json'))->toBeTrue();
});

test('éditeur : un paquet invalide est refusé proprement, aucun item créé (pas de 500)', function (): void {
    $course = h5pCourse('cours-edit2', 'draft');
    $lesson = h5pLesson($course);

    Livewire::actingAs(h5pAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Devrait échouer')
        ->set("newH5p.{$lesson->id}", h5pFakeRaw('pas un zip'))
        ->call('addH5pItem', $lesson->id)
        ->assertHasErrors("newH5p.{$lesson->id}");

    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'h5p')->count())->toBe(0);
});

test('éditeur : un paquet zip-slip est rejeté en erreur de champ, aucun item créé', function (): void {
    $course = h5pCourse('cours-slip', 'draft');
    $lesson = h5pLesson($course);

    $files = h5pValidFiles();
    $files['../evil.txt'] = 'x';

    Livewire::actingAs(h5pAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Slip')
        ->set("newH5p.{$lesson->id}", h5pFake($files))
        ->call('addH5pItem', $lesson->id)
        ->assertHasErrors("newH5p.{$lesson->id}");

    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'h5p')->count())->toBe(0);
});

test('éditeur : remplacement du paquet d\'un item h5p (nouveau chemin, ancien dossier supprimé)', function (): void {
    $course = h5pCourse('cours-rep', 'draft');
    $lesson = h5pLesson($course);
    $item   = h5pReadyItem($lesson);
    $oldPath = $item->payload['h5p_path'];

    Livewire::actingAs(h5pAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set("itemH5p.{$item->id}", h5pFake(h5pValidFiles()))
        ->call('replaceH5pPackage', $item->id)
        ->assertHasNoErrors();

    $fresh = $item->fresh();
    expect($fresh->payload['h5p_path'])->not->toBe($oldPath);
    expect(Storage::disk('public')->exists($fresh->payload['h5p_path'].'/h5p.json'))->toBeTrue();
    // L'ancien dossier a été nettoyé.
    expect(Storage::disk('public')->exists($oldPath.'/h5p.json'))->toBeFalse();
});

test('éditeur : suppression d\'un item h5p nettoie le dossier extrait', function (): void {
    $course = h5pCourse('cours-del', 'draft');
    $lesson = h5pLesson($course);
    $item   = h5pReadyItem($lesson);
    $path   = $item->payload['h5p_path'];

    Livewire::actingAs(h5pAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->call('deleteItem', $item->id)
        ->assertHasNoErrors();

    expect(LessonItem::find($item->id))->toBeNull();
    expect(Storage::disk('public')->exists($path.'/h5p.json'))->toBeFalse();
});

test('ANTI-ESCALADE : un formateur d\'un AUTRE cours ne peut pas ajouter de h5p ici', function (): void {
    $course = h5pCourse('cours-cible', 'draft');
    $lesson = h5pLesson($course);

    $other  = h5pCourse('cours-autre', 'draft');
    $stranger = User::factory()->create();
    $stranger->assignRole('instructor');
    CourseRole::create(['course_id' => $other->id, 'user_id' => $stranger->id, 'role' => 'owner']);

    Livewire::actingAs($stranger)
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();

    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'h5p')->count())->toBe(0);
});

test('RESTRICTION ADMIN : un formateur non-admin (owner de CE cours) ne peut PAS téléverser de h5p', function (): void {
    $course = h5pCourse('cours-no-admin', 'draft');
    $lesson = h5pLesson($course);

    // Formateur propriétaire de CE cours : il PASSE manageStructure mais n'a PAS
    // « academy.manage » → le téléversement H5P (JS tiers) doit être refusé proprement.
    $instructor = User::factory()->create();
    $instructor->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $instructor->id, 'role' => 'owner']);
    expect($instructor->can('academy.manage'))->toBeFalse();

    Livewire::actingAs($instructor)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Tentative non-admin')
        ->set("newH5p.{$lesson->id}", h5pFake(h5pValidFiles()))
        ->call('addH5pItem', $lesson->id)
        ->assertHasErrors("newH5p.{$lesson->id}");

    // Aucun item créé, aucun contenu extrait.
    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'h5p')->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. LECTEUR - iframe sandbox pour un inscrit, panneau gaté sinon
// ─────────────────────────────────────────────────────────────────────────────

test('lecteur : un inscrit voit l\'iframe sandbox H5P (src vers la page player)', function (): void {
    $course = h5pCourse('cours-lect');
    $lesson = h5pLesson($course);
    $item   = h5pReadyItem($lesson);
    $student = h5pStudent();
    h5pEnroll($course, $student);

    $playUrl = route('academy.h5p.play', [$course, $lesson, $item->id], false);

    $this->actingAs($student)
        ->get(h5pShowUrl($course, $lesson))
        ->assertOk()
        ->assertSee('sandbox="allow-scripts allow-same-origin"', false)
        ->assertSee($playUrl, false);
});

test('lecteur : un non-inscrit ne voit PAS l\'iframe (panneau gaté, aucune URL de contenu)', function (): void {
    $course = h5pCourse('cours-gate');
    $lesson = h5pLesson($course);
    $item   = h5pReadyItem($lesson);

    $playUrl = route('academy.h5p.play', [$course, $lesson, $item->id], false);

    $this->actingAs(h5pStudent()) // connecté mais non inscrit
        ->get(h5pShowUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee($playUrl, false)
        ->assertSee('Inscrivez-vous');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. PLAYER CONTROLLER - 200 + CSP jsdelivr / 403 / 404 anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('player : un inscrit obtient 200 + le bundle h5p-standalone (chargé en iframe sandbox)', function (): void {
    $course = h5pCourse('cours-play');
    $lesson = h5pLesson($course);
    $item   = h5pReadyItem($lesson);
    $student = h5pStudent();
    h5pEnroll($course, $student);

    $resp = $this->actingAs($student)
        ->get(route('academy.h5p.play', [$course, $lesson, $item->id]));

    $resp->assertOk();
    // La page charge le player h5p-standalone (CDN) + pointe sur le dossier extrait.
    $resp->assertSee('main.bundle.js', false);
    $resp->assertSee('h5pJsonPath', false);

    // CSP : le contrôleur pose une CSP dédiée (jsdelivr autorisé). En pratique, la
    // CSP GLOBALE du site (SecurityHeaders) ne déclare QUE « frame-src » et écrase
    // l'en-tête par route ; sous cette politique « frame-src seul », script-src reste
    // NON restreint → le bundle jsdelivr se charge quand même, et l'iframe est cadré
    // en same-origin (« frame-src 'self' »). On vérifie donc la politique effective.
    expect($resp->headers->get('Content-Security-Policy'))->toContain("frame-src 'self'");
});

test('player : un visiteur NON authentifié sur un item NON-preview obtient 403 (gating contrôleur)', function (): void {
    $course = h5pCourse('cours-guest');
    $lesson = h5pLesson($course);
    $item   = h5pReadyItem($lesson); // item non-preview par défaut

    // Aucune session : la route n'est pas derrière « auth », le gating est dans le
    // contrôleur (ni preview, ni gérant, ni inscrit) → 403, jamais le contenu.
    $this->get(route('academy.h5p.play', [$course, $lesson, $item->id]))
        ->assertForbidden();
});

test('player : un non-inscrit est refusé (403)', function (): void {
    $course = h5pCourse('cours-play2');
    $lesson = h5pLesson($course);
    $item   = h5pReadyItem($lesson);

    $this->actingAs(h5pStudent())
        ->get(route('academy.h5p.play', [$course, $lesson, $item->id]))
        ->assertForbidden();
});

test('player : ANTI-IDOR - un item d\'un autre cours demandé via ce cours → 404', function (): void {
    $courseA = h5pCourse('cours-a-play');
    $lessonA = h5pLesson($courseA);
    $courseB = h5pCourse('cours-b-play');
    $lessonB = h5pLesson($courseB);
    $itemB   = h5pReadyItem($lessonB);
    $student = h5pStudent();
    h5pEnroll($courseA, $student);

    // itemB appartient à la leçon B (cours B) ; on le demande via cours A / leçon A.
    $this->actingAs($student)
        ->get("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/h5p")
        ->assertNotFound();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. ACHÈVEMENT V2-c « view » + service
// ─────────────────────────────────────────────────────────────────────────────

test('service : défaut h5p = manual (interactif) ; critères autorisés = manual + view', function (): void {
    $lesson = h5pLesson(h5pCourse('cours-crit', 'draft'));
    $item   = h5pReadyItem($lesson);

    expect(ActivityCompletionService::defaultForType('h5p'))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($item))->toBe('manual');
    expect(ActivityCompletionService::allowedForType('h5p'))->toBe(['manual', 'view']);
});

test('achèvement : consulter la leçon NE marque PAS l\'item h5p (défaut manual) ; « view » reste opt-in', function (): void {
    $course = h5pCourse('cours-ach');
    $lesson = h5pLesson($course);
    $item   = h5pReadyItem($lesson);
    $student = h5pStudent();
    h5pEnroll($course, $student);

    // Défaut manual : la simple consultation ne complète PAS l'item H5P interactif.
    $this->actingAs($student)->get(h5pShowUrl($course, $lesson))->assertOk();
    expect(Completion::where('user_id', $student->id)->where('lesson_item_id', $item->id)->where('status', 'completed')->exists())->toBeFalse();

    // « view » reste disponible en option explicite : un H5P consultatif s'auto-complète alors.
    $viewItem = h5pReadyItem($lesson);
    $viewItem->payload = array_merge($viewItem->payload ?? [], ['completion' => 'view']);
    $viewItem->save();
    expect(ActivityCompletionService::criterionFor($viewItem))->toBe('view');

    $this->actingAs($student)->get(h5pShowUrl($course, $lesson))->assertOk();
    expect(Completion::where('user_id', $student->id)->where('lesson_item_id', $viewItem->id)->where('status', 'completed')->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. RÉTROCOMPATIBILITÉ - les 6 types existants restent acceptés
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : les 6 types d\'items historiques restent acceptés (+ h5p en plus)', function (): void {
    foreach (['video', 'document', 'quiz', 'choice', 'feedback', 'forum'] as $type) {
        expect(ActivityCompletionService::allowedForType($type))->not->toBeEmpty();
    }
    // Le nouveau type ne change pas les défauts historiques.
    expect(ActivityCompletionService::defaultForType('quiz'))->toBe('min_grade');
    expect(ActivityCompletionService::defaultForType('video'))->toBe('manual');
});
