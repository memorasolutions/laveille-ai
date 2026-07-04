<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - IMPORT SCORM (1.2 prioritaire, 2004 basique, single-SCO).
 *
 * Couvre :
 *  - ScormPackageService : extraction d'un paquet valide, rejets propres (zip
 *    invalide, manifeste manquant, zip-slip, extension exécutable filtrée,
 *    taille compressée/décompressée, launch introuvable/non sûr), détection
 *    de version, resolveAssetPath() anti-traversal, delete() anti-traversal ;
 *  - CourseEditor : addScormItem (création) + replaceScormPackage (remplacement)
 *    gardés par autorisation SERVEUR + anti-IDOR + restriction admin (JS tiers) ;
 *  - ScormPlayerController : 200 + CSP dédiée pour un inscrit, 403/404 sinon ;
 *  - ScormAssetController : sert un asset protégé pour un inscrit, 403/404
 *    sinon, anti-IDOR (item d'un autre cours), anti-traversal sur {path} ;
 *  - ScormCommitController : persiste ScormRegistration + branche la complétion
 *    EXISTANTE (CompletionService/ProgressService), score borné, rejet propre
 *    d'un corps invalide ;
 *  - drapeau academy.scorm_enabled OFF (défaut) : 404 sur TOUTES les routes +
 *    aucun item ne peut être ajouté.
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Progress;
use Modules\Academy\Models\ScormRegistration;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\ScormPackageService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    // Défaut EXPLICITE (comme LTI) : chaque test active le drapeau lui-même si besoin.
    config()->set('academy.scorm_enabled', false);
    Storage::fake('local');
    Storage::fake('public');

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers scorm (préfixés, autonomes - aucune redéclaration d'un autre fichier)
// ─────────────────────────────────────────────────────────────────────────────

/** Manifeste imsmanifest.xml minimal valide (SCORM 1.2 par défaut, single-SCO). */
function scormManifestXml(string $version = '1.2', string $launchHref = 'index.html'): string
{
    if ($version === '2004') {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="TEST-COURS-2004" version="1"
  xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
  xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_v1p3">
  <organizations default="ORG-1">
    <organization identifier="ORG-1">
      <title>Mon cours SCORM 2004</title>
      <item identifier="ITEM-1" identifierref="RES-1">
        <title>Lecon 1</title>
      </item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="RES-1" type="webcontent" adlcp:scormType="sco" href="{$launchHref}">
      <file href="{$launchHref}"/>
    </resource>
  </resources>
</manifest>
XML;
    }

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="TEST-COURS-12" version="1.2"
  xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2"
  xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2">
  <organizations default="ORG-1">
    <organization identifier="ORG-1">
      <title>Mon cours SCORM</title>
      <item identifier="ITEM-1" identifierref="RES-1">
        <title>Lecon 1</title>
      </item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="RES-1" type="webcontent" adlcp:scormtype="sco" href="{$launchHref}">
      <file href="{$launchHref}"/>
      <file href="css/style.css"/>
    </resource>
  </resources>
</manifest>
XML;
}

/** Contenu d'un paquet SCORM valide minimal (manifeste + page de lancement + asset). */
function scormValidFiles(string $version = '1.2', string $launchHref = 'index.html'): array
{
    return [
        'imsmanifest.xml' => scormManifestXml($version, $launchHref),
        $launchHref       => '<html><body><script>window.parent.API.LMSInitialize("");</script>SCO</body></html>',
        'css/style.css'   => 'body { color: #000; }',
    ];
}

/** Construit un fichier .zip SCORM temporaire à partir d'un tableau nom => contenu. */
function scormZipFile(array $files): string
{
    $path = tempnam(sys_get_temp_dir(), 'scormtest').'.zip';
    $zip  = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($files as $name => $content) {
        $zip->addFromString($name, (string) $content);
    }
    $zip->close();

    return $path;
}

/** UploadedFile en mode test (appel SERVICE direct). */
function scormUpload(string $path, string $name = 'contenu.zip', string $mime = 'application/zip'): UploadedFile
{
    return new UploadedFile($path, $name, $mime, null, true);
}

/** Fichier .zip « fake » à passer à Livewire ->set(). */
function scormFake(array $files, string $name = 'contenu.zip'): \Illuminate\Http\Testing\File
{
    return UploadedFile::fake()->createWithContent($name, (string) file_get_contents(scormZipFile($files)));
}

/** Fichier .zip « fake » au contenu brut arbitraire (ex. « pas un zip »). */
function scormFakeRaw(string $content, string $name = 'contenu.zip'): \Illuminate\Http\Testing\File
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

function scormCourse(string $slug = 'cours-scorm', string $status = 'published'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours SCORM',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => $status,
        'currency'    => 'CAD',
    ]);
}

function scormLesson(Course $course): Lesson
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre', 'position' => 1]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-scorm-'.$chapter->id,
        'position'   => 1,
    ]);
}

function scormAdmin(): User
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

function scormStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function scormEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

/** Crée un item scorm « prêt » avec un dossier extrait simulé sur le disque PRIVÉ local. */
function scormReadyItem(Lesson $lesson, string $launch = 'index.html', bool $isRequired = true): LessonItem
{
    $path = ScormPackageService::BASE_DIR.'/'.Str::uuid();
    Storage::disk('local')->put($path.'/imsmanifest.xml', scormManifestXml('1.2', $launch));
    Storage::disk('local')->put($path.'/'.$launch, '<html><body>SCO prêt</body></html>');
    Storage::disk('local')->put($path.'/css/style.css', 'body{color:#000}');

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'scorm',
        'title'       => 'Contenu SCORM',
        'position'    => 1,
        'is_required' => $isRequired,
        'payload'     => [
            'scorm_path'       => $path,
            'scorm_launch_url' => $launch,
            'scorm_version'    => '1.2',
            'scorm_title'      => 'Contenu SCORM',
        ],
    ]);
}

function scormShowUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE - extraction + rejets sûrs (jamais de 500)
// ─────────────────────────────────────────────────────────────────────────────

test('service : un paquet SCORM valide est extrait (fichiers requis présents + titre + version + launch)', function (): void {
    $result = (new ScormPackageService())->extract(scormUpload(scormZipFile(scormValidFiles())));

    expect($result['path'])->toStartWith(ScormPackageService::BASE_DIR.'/');
    expect($result['title'])->toBe('Mon cours SCORM');
    expect($result['version'])->toBe('1.2');
    expect($result['launch_url'])->toBe('index.html');
    expect(Storage::disk('local')->exists($result['path'].'/imsmanifest.xml'))->toBeTrue();
    expect(Storage::disk('local')->exists($result['path'].'/index.html'))->toBeTrue();
    expect(Storage::disk('local')->exists($result['path'].'/css/style.css'))->toBeTrue();
    // Jamais sur le disque PUBLIC (contrairement à H5P) : disque privé strictement.
    expect(Storage::disk('public')->exists($result['path'].'/imsmanifest.xml'))->toBeFalse();
});

test('service : un fichier qui n\'est pas un zip est rejeté proprement', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'notzip').'.zip';
    file_put_contents($path, 'ceci n\'est pas un zip');

    expect(fn () => (new ScormPackageService())->extract(scormUpload($path)))
        ->toThrow(\RuntimeException::class);
});

test('service : un zip sans imsmanifest.xml est rejeté (structure invalide)', function (): void {
    $path = scormZipFile(['readme.txt' => 'rien d\'utile']);

    expect(fn () => (new ScormPackageService())->extract(scormUpload($path)))
        ->toThrow(\RuntimeException::class);
});

test('service : une entrée zip-slip (..) est rejetée sans écrire hors du dossier', function (): void {
    $files = scormValidFiles();
    $files['../evil.txt'] = 'charge utile malveillante';
    $path = scormZipFile($files);

    expect(fn () => (new ScormPackageService())->extract(scormUpload($path)))
        ->toThrow(\RuntimeException::class);

    expect(Storage::disk('local')->exists('evil.txt'))->toBeFalse();
});

test('service : un fichier exécutable (.php) du paquet n\'est jamais extrait', function (): void {
    $files = scormValidFiles();
    $files['scripts/shell.php'] = '<?php echo 1;';
    $result = (new ScormPackageService())->extract(scormUpload(scormZipFile($files)));

    expect(Storage::disk('local')->exists($result['path'].'/imsmanifest.xml'))->toBeTrue();
    expect(Storage::disk('local')->exists($result['path'].'/scripts/shell.php'))->toBeFalse();
});

test('service : un paquet trop lourd (déclaré) est rejeté', function (): void {
    $path = scormZipFile(scormValidFiles());
    $big  = new class($path, 'gros.zip', 'application/zip', null, true) extends UploadedFile
    {
        public function getSize(): int
        {
            return ScormPackageService::MAX_BYTES + 1;
        }
    };

    expect(fn () => (new ScormPackageService())->extract($big))
        ->toThrow(\RuntimeException::class);
});

test('service : ANTI ZIP-BOMB - un contenu décompressé au-delà du seuil est rejeté sans 500 ni dossier orphelin', function (): void {
    config()->set('academy.scorm.max_extract_kb', 5);

    $files = scormValidFiles();
    $files['css/big.css'] = str_repeat('a', 50 * 1024);
    $path = scormZipFile($files);

    expect(fn () => (new ScormPackageService())->extract(scormUpload($path)))
        ->toThrow(\RuntimeException::class);

    expect(Storage::disk('local')->allFiles(ScormPackageService::BASE_DIR))->toBe([]);
});

test('service : un manifeste référençant un fichier de lancement ABSENT du zip est rejeté', function (): void {
    $files = scormValidFiles();
    unset($files['index.html']); // le manifeste pointe sur un fichier qui n'existe pas
    $path = scormZipFile($files);

    expect(fn () => (new ScormPackageService())->extract(scormUpload($path)))
        ->toThrow(\RuntimeException::class);
});

test('service : un launch URL absolu (http://) dans le manifeste est rejeté (non sûr)', function (): void {
    $files = scormValidFiles('1.2', 'index.html');
    $files['imsmanifest.xml'] = str_replace('href="index.html"', 'href="http://evil.test/x.html"', $files['imsmanifest.xml']);
    $path = scormZipFile($files);

    expect(fn () => (new ScormPackageService())->extract(scormUpload($path)))
        ->toThrow(\RuntimeException::class);
});

test('service : détecte la version SCORM 1.2 vs 2004 depuis le manifeste', function (): void {
    $r12 = (new ScormPackageService())->extract(scormUpload(scormZipFile(scormValidFiles('1.2'))));
    expect($r12['version'])->toBe('1.2');

    $r2004 = (new ScormPackageService())->extract(scormUpload(scormZipFile(scormValidFiles('2004'))));
    expect($r2004['version'])->toBe('2004');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. SERVICE - delete() / resolveAssetPath() bornés (anti-traversal)
// ─────────────────────────────────────────────────────────────────────────────

test('service : delete() ne touche qu\'aux chemins academy-scorm/ (anti-traversal)', function (): void {
    $service = new ScormPackageService();
    Storage::disk('local')->put('autre/important.txt', 'à conserver');

    $service->delete('autre');
    $service->delete('../../etc/passwd');
    expect(Storage::disk('local')->exists('autre/important.txt'))->toBeTrue();

    $rel = ScormPackageService::BASE_DIR.'/abc';
    Storage::disk('local')->put($rel.'/imsmanifest.xml', '<manifest/>');
    $service->delete($rel);
    expect(Storage::disk('local')->exists($rel.'/imsmanifest.xml'))->toBeFalse();
});

test('service : resolveAssetPath() résout un asset RÉEL, rejette un chemin non sûr ou inexistant', function (): void {
    $service = new ScormPackageService();
    $rel     = ScormPackageService::BASE_DIR.'/pkg-1';
    Storage::disk('local')->put($rel.'/index.html', '<html></html>');
    Storage::disk('local')->put($rel.'/css/style.css', 'body{}');

    expect($service->resolveAssetPath($rel, 'index.html'))->toBe($rel.'/index.html');
    expect($service->resolveAssetPath($rel, 'css/style.css'))->toBe($rel.'/css/style.css');
    // Chemin non sûr : jamais résolu, quel que soit son existence réelle ailleurs.
    expect($service->resolveAssetPath($rel, '../../../../etc/passwd'))->toBeNull();
    // Fichier inexistant dans CE paquet : jamais résolu.
    expect($service->resolveAssetPath($rel, 'inexistant.js'))->toBeNull();
    // Dossier de base hors périmètre : jamais résolu.
    expect($service->resolveAssetPath('autre/dossier', 'index.html'))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ÉDITEUR - création / remplacement gardés serveur + anti-IDOR + restriction admin
// ─────────────────────────────────────────────────────────────────────────────

test('éditeur : un gérant ajoute un item scorm à partir d\'un paquet valide (item + payload)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-edit', 'draft');
    $lesson = scormLesson($course);

    Livewire::actingAs(scormAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Module SCORM')
        ->set("newScorm.{$lesson->id}", scormFake(scormValidFiles()))
        ->call('addScormItem', $lesson->id)
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'scorm')->first();
    expect($item)->not->toBeNull();
    expect($item->title)->toBe('Module SCORM');
    expect($item->payload['scorm_path'])->toStartWith(ScormPackageService::BASE_DIR.'/');
    expect($item->payload['scorm_launch_url'])->toBe('index.html');
    expect($item->payload['scorm_version'])->toBe('1.2');
    expect(Storage::disk('local')->exists($item->payload['scorm_path'].'/index.html'))->toBeTrue();
});

test('éditeur : drapeau scorm_enabled OFF (défaut) - addScormItem répond 404, aucun item créé', function (): void {
    $course = scormCourse('cours-flag-off', 'draft');
    $lesson = scormLesson($course);

    Livewire::actingAs(scormAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Ne devrait pas être créé')
        ->set("newScorm.{$lesson->id}", scormFake(scormValidFiles()))
        ->call('addScormItem', $lesson->id)
        ->assertStatus(404);

    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'scorm')->count())->toBe(0);
});

test('éditeur : un paquet invalide est refusé proprement, aucun item créé (pas de 500)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-edit2', 'draft');
    $lesson = scormLesson($course);

    Livewire::actingAs(scormAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Devrait échouer')
        ->set("newScorm.{$lesson->id}", scormFakeRaw('pas un zip'))
        ->call('addScormItem', $lesson->id)
        ->assertHasErrors("newScorm.{$lesson->id}");

    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'scorm')->count())->toBe(0);
});

test('éditeur : un paquet zip-slip est rejeté en erreur de champ, aucun item créé', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-slip', 'draft');
    $lesson = scormLesson($course);

    $files = scormValidFiles();
    $files['../evil.txt'] = 'x';

    Livewire::actingAs(scormAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Slip')
        ->set("newScorm.{$lesson->id}", scormFake($files))
        ->call('addScormItem', $lesson->id)
        ->assertHasErrors("newScorm.{$lesson->id}");

    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'scorm')->count())->toBe(0);
});

test('éditeur : remplacement du paquet d\'un item scorm (nouveau chemin, ancien dossier supprimé)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-rep', 'draft');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $oldPath = $item->payload['scorm_path'];

    Livewire::actingAs(scormAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set("itemScorm.{$item->id}", scormFake(scormValidFiles()))
        ->call('replaceScormPackage', $item->id)
        ->assertHasNoErrors();

    $fresh = $item->fresh();
    expect($fresh->payload['scorm_path'])->not->toBe($oldPath);
    expect(Storage::disk('local')->exists($fresh->payload['scorm_path'].'/imsmanifest.xml'))->toBeTrue();
    expect(Storage::disk('local')->exists($oldPath.'/imsmanifest.xml'))->toBeFalse();
});

test('éditeur : suppression d\'un item scorm nettoie le dossier extrait', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-del', 'draft');
    $lesson = scormLesson($course);
    $item   = scormReadyItem($lesson);
    $path   = $item->payload['scorm_path'];

    Livewire::actingAs(scormAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->call('deleteItem', $item->id)
        ->assertHasNoErrors();

    expect(LessonItem::find($item->id))->toBeNull();
    expect(Storage::disk('local')->exists($path.'/imsmanifest.xml'))->toBeFalse();
});

test('RESTRICTION ADMIN : un formateur non-admin (owner de CE cours) ne peut PAS téléverser de scorm', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-no-admin', 'draft');
    $lesson = scormLesson($course);

    $instructor = User::factory()->create();
    $instructor->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $instructor->id, 'role' => 'owner']);
    expect($instructor->can('academy.manage'))->toBeFalse();

    Livewire::actingAs($instructor)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Tentative non-admin')
        ->set("newScorm.{$lesson->id}", scormFake(scormValidFiles()))
        ->call('addScormItem', $lesson->id)
        ->assertHasErrors("newScorm.{$lesson->id}");

    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'scorm')->count())->toBe(0);
});

test('ANTI-ESCALADE : un formateur d\'un AUTRE cours ne peut pas gérer ce cours', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-cible', 'draft');
    scormLesson($course);

    $other    = scormCourse('cours-autre', 'draft');
    $stranger = User::factory()->create();
    $stranger->assignRole('instructor');
    CourseRole::create(['course_id' => $other->id, 'user_id' => $stranger->id, 'role' => 'owner']);

    Livewire::actingAs($stranger)
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. LECTEUR - iframe sandbox pour un inscrit, panneau gaté sinon
// ─────────────────────────────────────────────────────────────────────────────

test('lecteur : un inscrit voit l\'iframe sandbox SCORM (src vers la page player)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-lect');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $playUrl = route('academy.scorm.play', [$course, $lesson, $item->id], false);

    $this->actingAs($student)
        ->get(scormShowUrl($course, $lesson))
        ->assertOk()
        ->assertSee('sandbox="allow-scripts allow-same-origin"', false)
        ->assertSee($playUrl, false);
});

test('lecteur : drapeau OFF - le type scorm n\'affiche pas l\'iframe (rendu défensif générique)', function (): void {
    $course  = scormCourse('cours-lect-off');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $this->actingAs($student)
        ->get(scormShowUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee('sandbox="allow-scripts allow-same-origin"', false);
});

test('lecteur : un non-inscrit ne voit PAS l\'iframe (panneau gaté, aucune URL de contenu)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-gate');
    $lesson = scormLesson($course);
    $item   = scormReadyItem($lesson);

    $playUrl = route('academy.scorm.play', [$course, $lesson, $item->id], false);

    $this->actingAs(scormStudent())
        ->get(scormShowUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee($playUrl, false)
        ->assertSee('Inscrivez-vous');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. PLAYER + ASSET CONTROLLER - 200 protégé / 403 / 404 anti-IDOR / anti-traversal
// ─────────────────────────────────────────────────────────────────────────────

test('player : un inscrit obtient 200 + le pont API SCORM 1.2/2004', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-play');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $resp = $this->actingAs($student)
        ->get(route('academy.scorm.play', [$course, $lesson, $item->id]));

    $resp->assertOk();
    $resp->assertSee('window.API', false);
    $resp->assertSee('window.API_1484_11', false);

    // CSP : le contrôleur pose une CSP dédiée (script-src self + nonce). En pratique,
    // la CSP GLOBALE du site (SecurityHeaders) ne déclare QUE « frame-src » et écrase
    // l'en-tête par route (même comportement documenté/testé pour H5pPlayerController) ;
    // on vérifie donc la politique EFFECTIVEMENT appliquée.
    expect($resp->headers->get('Content-Security-Policy'))->toContain("frame-src 'self'");
});

test('player : drapeau scorm_enabled OFF (défaut) → 404', function (): void {
    $course = scormCourse('cours-play-off');
    $lesson = scormLesson($course);
    $item   = scormReadyItem($lesson);

    $this->get(route('academy.scorm.play', [$course, $lesson, $item->id]))
        ->assertNotFound();
});

test('player : un non-inscrit est refusé (403)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-play2');
    $lesson = scormLesson($course);
    $item   = scormReadyItem($lesson);

    $this->actingAs(scormStudent())
        ->get(route('academy.scorm.play', [$course, $lesson, $item->id]))
        ->assertForbidden();
});

test('player : ANTI-IDOR - un item d\'un autre cours demandé via ce cours → 404', function (): void {
    config()->set('academy.scorm_enabled', true);
    $courseA = scormCourse('cours-a-play');
    $lessonA = scormLesson($courseA);
    $courseB = scormCourse('cours-b-play');
    $lessonB = scormLesson($courseB);
    $itemB   = scormReadyItem($lessonB);
    $student = scormStudent();
    scormEnroll($courseA, $student);

    $this->actingAs($student)
        ->get("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/scorm")
        ->assertNotFound();
});

test('asset : un inscrit récupère le fichier de lancement ET une ressource relative (css)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-asset');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $response = $this->actingAs($student)
        ->get(route('academy.scorm.asset', [$course, $lesson, $item->id, 'path' => 'index.html']))
        ->assertOk();
    expect($response->streamedContent())->toContain('SCO prêt');

    $this->actingAs($student)
        ->get(route('academy.scorm.asset', [$course, $lesson, $item->id, 'path' => 'css/style.css']))
        ->assertOk();
});

test('asset : un non-inscrit reçoit 403 (aucun octet du contenu ne fuite)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-asset2');
    $lesson = scormLesson($course);
    $item   = scormReadyItem($lesson);

    $this->actingAs(scormStudent())
        ->get(route('academy.scorm.asset', [$course, $lesson, $item->id, 'path' => 'index.html']))
        ->assertForbidden();
});

test('asset : ANTI-IDOR - un item d\'un autre cours demandé via ce cours → 404', function (): void {
    config()->set('academy.scorm_enabled', true);
    $courseA = scormCourse('cours-a-asset');
    $lessonA = scormLesson($courseA);
    $courseB = scormCourse('cours-b-asset');
    $lessonB = scormLesson($courseB);
    $itemB   = scormReadyItem($lessonB);
    $student = scormStudent();
    scormEnroll($courseA, $student);

    $this->actingAs($student)
        ->get("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/scorm/asset/index.html")
        ->assertNotFound();
});

test('asset : ANTI-TRAVERSAL - une requête « ../ » sur {path} ne sert jamais un fichier hors du paquet', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-asset3');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $this->actingAs($student)
        ->get(route('academy.scorm.asset', [$course, $lesson, $item->id, 'path' => '../../../../etc/passwd']))
        ->assertNotFound();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. COMMIT RUNTIME - persistance CMI + bridge complétion EXISTANTE
// ─────────────────────────────────────────────────────────────────────────────

test('commit : un statut « completed » persiste ScormRegistration ET complète l\'item + recalcule la progression', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-commit');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson); // seul item requis du cours
    $student = scormStudent();
    scormEnroll($course, $student);

    $this->actingAs($student)
        ->postJson(route('academy.scorm.commit', [$course, $lesson, $item->id]), [
            'cmi.core.lesson_status' => 'completed',
            'cmi.core.score.raw'     => '87',
            'cmi.suspend_data'       => 'etat-sauvegarde',
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $reg = ScormRegistration::where('user_id', $student->id)->where('lesson_item_id', $item->id)->first();
    expect($reg)->not->toBeNull();
    expect($reg->lesson_status)->toBe('completed');
    expect($reg->score_raw)->toBe(87);
    expect($reg->suspend_data)->toBe('etat-sauvegarde');

    expect(Completion::where('user_id', $student->id)->where('lesson_item_id', $item->id)->where('status', 'completed')->exists())->toBeTrue();

    $progress = Progress::where('user_id', $student->id)->where('course_id', $course->id)->first();
    expect($progress)->not->toBeNull();
    expect($progress->percent)->toBe(100);
});

test('commit : un statut « incomplete » NE complète PAS l\'item (l\'apprenant peut reprendre)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-commit2');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $this->actingAs($student)
        ->postJson(route('academy.scorm.commit', [$course, $lesson, $item->id]), [
            'cmi.core.lesson_status' => 'incomplete',
        ])
        ->assertOk();

    expect(Completion::where('user_id', $student->id)->where('lesson_item_id', $item->id)->where('status', 'completed')->exists())->toBeFalse();

    // Une seconde tentative avec « passed » complète bel et bien l'item (idempotent, reprise).
    $this->actingAs($student)
        ->postJson(route('academy.scorm.commit', [$course, $lesson, $item->id]), [
            'cmi.core.lesson_status' => 'passed',
        ])
        ->assertOk();

    expect(Completion::where('user_id', $student->id)->where('lesson_item_id', $item->id)->where('status', 'completed')->exists())->toBeTrue();
});

test('commit : le score brut est borné 0..100 (valeur hors bornes clampée)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-commit3');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $this->actingAs($student)
        ->postJson(route('academy.scorm.commit', [$course, $lesson, $item->id]), [
            'cmi.core.lesson_status' => 'completed',
            'cmi.core.score.raw'     => '150',
        ])
        ->assertOk();

    expect(ScormRegistration::where('user_id', $student->id)->where('lesson_item_id', $item->id)->first()->score_raw)->toBe(100);
});

test('commit : SCORM 2004 (success_status=passed) est bridgé vers l\'achèvement', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-commit4');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $this->actingAs($student)
        ->postJson(route('academy.scorm.commit', [$course, $lesson, $item->id]), [
            'cmi.completion_status' => 'completed',
            'cmi.success_status'    => 'passed',
            'cmi.score.raw'         => '95',
        ])
        ->assertOk();

    expect(Completion::where('user_id', $student->id)->where('lesson_item_id', $item->id)->where('status', 'completed')->exists())->toBeTrue();
});

test('commit : un corps invalide (pas un objet) est rejeté proprement (422, jamais 500)', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course  = scormCourse('cours-commit5');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $tooManyKeys = [];
    for ($i = 0; $i < 400; $i++) {
        $tooManyKeys["cmi.interactions.{$i}.id"] = (string) $i;
    }

    $this->actingAs($student)
        ->postJson(route('academy.scorm.commit', [$course, $lesson, $item->id]), $tooManyKeys)
        ->assertStatus(422);
});

test('commit : un visiteur NON authentifié reçoit 403', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-commit6');
    $lesson = scormLesson($course);
    $item   = scormReadyItem($lesson);

    $this->postJson(route('academy.scorm.commit', [$course, $lesson, $item->id]), [
        'cmi.core.lesson_status' => 'completed',
    ])->assertForbidden();
});

test('commit : un non-inscrit reçoit 403', function (): void {
    config()->set('academy.scorm_enabled', true);
    $course = scormCourse('cours-commit7');
    $lesson = scormLesson($course);
    $item   = scormReadyItem($lesson);

    $this->actingAs(scormStudent())
        ->postJson(route('academy.scorm.commit', [$course, $lesson, $item->id]), [
            'cmi.core.lesson_status' => 'completed',
        ])
        ->assertForbidden();
});

test('commit : drapeau scorm_enabled OFF (défaut) → 404', function (): void {
    $course  = scormCourse('cours-commit8');
    $lesson  = scormLesson($course);
    $item    = scormReadyItem($lesson);
    $student = scormStudent();
    scormEnroll($course, $student);

    $this->actingAs($student)
        ->postJson(route('academy.scorm.commit', [$course, $lesson, $item->id]), [
            'cmi.core.lesson_status' => 'completed',
        ])
        ->assertNotFound();
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. CRITÈRE D'ACHÈVEMENT + RÉTROCOMPATIBILITÉ
// ─────────────────────────────────────────────────────────────────────────────

test('service : le type scorm impose le critère « scorm » (piloté par le runtime, pas manual/view)', function (): void {
    $lesson = scormLesson(scormCourse('cours-crit', 'draft'));
    $item   = scormReadyItem($lesson);

    expect(ActivityCompletionService::defaultForType('scorm'))->toBe('scorm');
    expect(ActivityCompletionService::criterionFor($item))->toBe('scorm');
    expect(ActivityCompletionService::allowedForType('scorm'))->toBe(['scorm']);
});

test('rétrocompat : les types d\'items historiques (dont h5p) restent acceptés, scorm en plus', function (): void {
    foreach (['video', 'document', 'quiz', 'choice', 'feedback', 'forum', 'h5p'] as $type) {
        expect(ActivityCompletionService::allowedForType($type))->not->toBeEmpty();
    }
    expect(ActivityCompletionService::defaultForType('quiz'))->toBe('min_grade');
    expect(ActivityCompletionService::defaultForType('video'))->toBe('manual');
    expect(ActivityCompletionService::defaultForType('h5p'))->toBe('manual');
});
