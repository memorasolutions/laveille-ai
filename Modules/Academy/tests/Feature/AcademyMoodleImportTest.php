<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - IMPORT DE SAUVEGARDES MOODLE (.mbz) VERS UN COURS ACADEMY.
 *
 * Couvre :
 *  - drapeau academy.moodle_import_enabled OFF (défaut) : 404 sur l'écran d'import ;
 *  - autorisation SERVEUR : seul un admin (academy.manage) OU un formateur
 *    (create() de CoursePolicy) peut importer - un étudiant est refusé (403) ;
 *  - MoodleBackupImportService : import d'un .mbz minimal valide (2 sections,
 *    activités page/resource/label + 1 activité ignorée « quiz ») crée bien le
 *    cours (brouillon, jamais publié), le chapitre unique, les leçons (une par
 *    section) et les items (un par activité simple), avec le bon résumé de
 *    comptage (importées/ignorées par type - jamais de perte silencieuse) ;
 *  - rejets propres (jamais de 500) : fichier pas un zip, zip sans
 *    moodle_backup.xml, fichier trop volumineux (déclaré), entrée zip-slip/
 *    traversal (chemin d'activité malveillant) ignorée sans planter ;
 *  - CourseMoodleImport (Livewire) : gate OFF -> 404 ; utilisateur non autorisé
 *    -> 403 ; import valide -> redirection vers l'éditeur + résumé en flash.
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Academy\Exceptions\InvalidCourseBackupException;
use Modules\Academy\Livewire\CourseMoodleImport;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\MoodleBackupImportService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    // Défaut EXPLICITE (comme SCORM) : chaque test active le drapeau lui-même si besoin.
    config()->set('academy.moodle_import_enabled', false);
    Storage::fake('public');

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers .mbz (préfixés « mbz », autonomes - aucune redéclaration d'un autre fichier)
// ─────────────────────────────────────────────────────────────────────────────

/** Construit le moodle_backup.xml (point d'entrée autoritatif : contents/sections+activities). */
function mbzManifestXml(array $sections, array $activities, string $courseTitle = 'Cours démo Moodle'): string
{
    $sectionsXml = '';
    foreach ($sections as $s) {
        $sectionsXml .= '<section><sectionid>'.$s['sectionid'].'</sectionid><title><![CDATA['.$s['title'].']]></title></section>';
    }

    $activitiesXml = '';
    foreach ($activities as $a) {
        $activitiesXml .= '<activity>'
            .'<moduleid>'.$a['moduleid'].'</moduleid>'
            .'<sectionid>'.$a['sectionid'].'</sectionid>'
            .'<modulename>'.$a['modulename'].'</modulename>'
            .'<title><![CDATA['.$a['title'].']]></title>'
            .'<directory>'.$a['directory'].'</directory>'
            .'</activity>';
    }

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<moodle_backup>
  <information>
    <name>backup-moodle2-course-test-20260704-1200.mbz</name>
    <moodle_version>2026060600</moodle_version>
    <original_course_id>2</original_course_id>
    <original_course_fullname><![CDATA[{$courseTitle}]]></original_course_fullname>
    <original_course_shortname>demo</original_course_shortname>
    <contents>
      <course>
        <courseid>2</courseid>
        <title><![CDATA[{$courseTitle}]]></title>
        <directory>course</directory>
      </course>
      <sections>{$sectionsXml}</sections>
      <activities>{$activitiesXml}</activities>
    </contents>
  </information>
</moodle_backup>
XML;
}

function mbzPageXml(string $name, string $content): string
{
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="101" modulename="page">
  <page>
    <name><![CDATA[{$name}]]></name>
    <intro></intro>
    <content><![CDATA[{$content}]]></content>
  </page>
</activity>
XML;
}

function mbzLabelXml(string $name, string $intro): string
{
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="104" modulename="label">
  <label>
    <name><![CDATA[{$name}]]></name>
    <intro><![CDATA[{$intro}]]></intro>
  </label>
</activity>
XML;
}

function mbzResourceXml(string $name, string $intro): string
{
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="102" modulename="resource">
  <resource>
    <name><![CDATA[{$name}]]></name>
    <intro><![CDATA[{$intro}]]></intro>
    <display>0</display>
  </resource>
</activity>
XML;
}

/** Structure MINIMALE valide : 2 sections, page + resource + label + 1 quiz (ignoré). */
function mbzMinimalValidFiles(): array
{
    $sections = [
        ['sectionid' => 10, 'title' => 'Introduction'],
        ['sectionid' => 11, 'title' => 'Chapitre 2'],
    ];

    $activities = [
        ['moduleid' => 201, 'sectionid' => 10, 'modulename' => 'page', 'title' => 'Page de bienvenue', 'directory' => 'activities/page_201'],
        ['moduleid' => 202, 'sectionid' => 10, 'modulename' => 'resource', 'title' => 'Document utile', 'directory' => 'activities/resource_202'],
        ['moduleid' => 203, 'sectionid' => 10, 'modulename' => 'label', 'title' => 'Étiquette', 'directory' => 'activities/label_203'],
        ['moduleid' => 204, 'sectionid' => 11, 'modulename' => 'quiz', 'title' => 'Quiz final', 'directory' => 'activities/quiz_204'],
    ];

    return [
        'moodle_backup.xml' => mbzManifestXml($sections, $activities, 'Cours démo Moodle'),
        'activities/page_201/page.xml'     => mbzPageXml('Page de bienvenue', '<p>Contenu <strong>principal</strong> de la page.</p>'),
        'activities/resource_202/resource.xml' => mbzResourceXml('Document utile', 'Un document important à consulter.'),
        'activities/label_203/label.xml'   => mbzLabelXml('Étiquette', '<p>Texte d\'étiquette.</p>'),
    ];
}

/** Construit un fichier .mbz (ZIP standard) temporaire à partir d'un tableau nom => contenu. */
function mbzZipFile(array $files): string
{
    $path = tempnam(sys_get_temp_dir(), 'mbztest').'.mbz';
    $zip  = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($files as $name => $content) {
        $zip->addFromString($name, (string) $content);
    }
    $zip->close();

    return $path;
}

/** UploadedFile en mode test (appel SERVICE direct). */
function mbzUpload(string $path, string $name = 'cours.mbz', string $mime = 'application/zip'): UploadedFile
{
    return new UploadedFile($path, $name, $mime, null, true);
}

/** Fichier .mbz « fake » à passer à Livewire ->set(). */
function mbzFake(array $files, string $name = 'cours.mbz'): \Illuminate\Http\Testing\File
{
    return UploadedFile::fake()->createWithContent($name, (string) file_get_contents(mbzZipFile($files)));
}

/** Fichier « fake » au contenu brut arbitraire (ex. « pas un zip »). */
function mbzFakeRaw(string $content, string $name = 'cours.mbz'): \Illuminate\Http\Testing\File
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

function mbzAdmin(): User
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

function mbzInstructor(): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');

    return $u;
}

function mbzStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE - import complet (structure + résumé de comptage)
// ─────────────────────────────────────────────────────────────────────────────

test('service : un .mbz minimal valide crée le cours (brouillon), le chapitre, les leçons (par section) et les items (activités simples)', function (): void {
    $owner  = mbzInstructor();
    $result = (new MoodleBackupImportService())->import(mbzUpload(mbzZipFile(mbzMinimalValidFiles())), $owner);

    $course = $result['course'];
    expect($course)->toBeInstanceOf(Course::class);
    expect($course->title)->toBe('Cours démo Moodle');
    // JAMAIS publié automatiquement, toujours gratuit.
    expect($course->status)->toBe('draft');
    expect($course->access_type)->toBe('free');
    expect($course->published_at)->toBeNull();

    // Owner = importateur (course_roles).
    expect(CourseRole::where('course_id', $course->id)->where('user_id', $owner->id)->where('role', 'owner')->exists())->toBeTrue();

    // UN chapitre unique regroupe l'import.
    expect(Chapter::where('course_id', $course->id)->count())->toBe(1);

    // Une leçon par section (2), dans l'ordre.
    $lessons = Lesson::whereHas('chapter', fn ($q) => $q->where('course_id', $course->id))->orderBy('position')->get();
    expect($lessons)->toHaveCount(2);
    expect($lessons[0]->title)->toBe('Introduction');
    expect($lessons[1]->title)->toBe('Chapitre 2');

    // Items : page + resource + label dans la 1re leçon (quiz ignoré dans la 2e).
    $items = LessonItem::where('lesson_id', $lessons[0]->id)->orderBy('position')->get();
    expect($items)->toHaveCount(3);
    expect($items->pluck('title')->all())->toBe(['Page de bienvenue', 'Document utile', 'Étiquette']);
    expect($items->pluck('type')->unique()->all())->toBe(['document']);
    expect($items[0]->payload['rich_text'])->toContain('Contenu');
    expect($items[0]->payload['rich_text'])->toContain('principal');

    expect(LessonItem::where('lesson_id', $lessons[1]->id)->count())->toBe(0);

    // Résumé de comptage - JAMAIS de perte silencieuse.
    expect($result['sections_imported'])->toBe(2);
    expect($result['items_imported'])->toBe(3);
    expect($result['items_ignored'])->toBe(['quiz' => 1]);
});

test('service : le contenu HTML des activités est converti en texte sûr (jamais de balise brute)', function (): void {
    $result = (new MoodleBackupImportService())->import(mbzUpload(mbzZipFile(mbzMinimalValidFiles())), mbzInstructor());

    $lesson = Lesson::whereHas('chapter', fn ($q) => $q->where('course_id', $result['course']->id))->orderBy('position')->first();
    $page   = LessonItem::where('lesson_id', $lesson->id)->where('title', 'Page de bienvenue')->first();

    expect($page->payload['rich_text'])->not->toContain('<p>');
    expect($page->payload['rich_text'])->not->toContain('<strong>');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. SERVICE - rejets propres (jamais de 500)
// ─────────────────────────────────────────────────────────────────────────────

test('service : un fichier qui n\'est pas un zip est rejeté proprement', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'notzip').'.mbz';
    file_put_contents($path, 'ceci n\'est pas une archive');

    expect(fn () => (new MoodleBackupImportService())->import(mbzUpload($path), mbzInstructor()))
        ->toThrow(InvalidCourseBackupException::class);
});

test('service : un zip SANS moodle_backup.xml est rejeté (format non reconnu)', function (): void {
    $path = mbzZipFile(['readme.txt' => 'rien d\'utile ici']);

    expect(fn () => (new MoodleBackupImportService())->import(mbzUpload($path), mbzInstructor()))
        ->toThrow(InvalidCourseBackupException::class);

    expect(Course::count())->toBe(0);
});

test('service : un moodle_backup.xml sans élément racine <moodle_backup> reconnu est rejeté', function (): void {
    $path = mbzZipFile(['moodle_backup.xml' => '<?xml version="1.0"?><autre_chose><information/></autre_chose>']);

    expect(fn () => (new MoodleBackupImportService())->import(mbzUpload($path), mbzInstructor()))
        ->toThrow(InvalidCourseBackupException::class);
});

test('service : un fichier .mbz trop volumineux (déclaré) est rejeté', function (): void {
    $path = mbzZipFile(mbzMinimalValidFiles());
    $big  = new class($path, 'gros.mbz', 'application/zip', null, true) extends UploadedFile
    {
        public function getSize(): int
        {
            return MoodleBackupImportService::MAX_BYTES + 1;
        }
    };

    expect(fn () => (new MoodleBackupImportService())->import($big, mbzInstructor()))
        ->toThrow(InvalidCourseBackupException::class);

    expect(Course::count())->toBe(0);
});

test('service : ANTI ZIP-SLIP - un répertoire d\'activité malveillant (../..) est ignoré sans planter, aucune fuite hors périmètre', function (): void {
    $sections = [['sectionid' => 10, 'title' => 'Introduction']];
    $activities = [
        ['moduleid' => 301, 'sectionid' => 10, 'modulename' => 'page', 'title' => 'Page piégée', 'directory' => '../../../etc/page_301'],
        ['moduleid' => 302, 'sectionid' => 10, 'modulename' => 'page', 'title' => 'Page saine', 'directory' => 'activities/page_302'],
    ];

    $files = [
        'moodle_backup.xml' => mbzManifestXml($sections, $activities, 'Cours zip-slip'),
        'activities/page_302/page.xml' => mbzPageXml('Page saine', '<p>Contenu correct</p>'),
    ];

    $result = (new MoodleBackupImportService())->import(mbzUpload(mbzZipFile($files)), mbzInstructor());

    // La page « piégée » n'est JAMAIS importée (chemin non sûr) mais rapportée comme ignorée.
    expect($result['items_imported'])->toBe(1);
    expect($result['items_ignored'])->toBe(['page' => 1]);

    $lesson = Lesson::whereHas('chapter', fn ($q) => $q->where('course_id', $result['course']->id))->first();
    expect(LessonItem::where('lesson_id', $lesson->id)->pluck('title')->all())->toBe(['Page saine']);
});

test('service : une activité individuelle corrompue (XML invalide) est ignorée sans faire échouer tout l\'import', function (): void {
    $sections   = [['sectionid' => 10, 'title' => 'Introduction']];
    $activities = [
        ['moduleid' => 401, 'sectionid' => 10, 'modulename' => 'page', 'title' => 'Page corrompue', 'directory' => 'activities/page_401'],
        ['moduleid' => 402, 'sectionid' => 10, 'modulename' => 'label', 'title' => 'Étiquette saine', 'directory' => 'activities/label_402'],
    ];

    $files = [
        'moodle_backup.xml' => mbzManifestXml($sections, $activities, 'Cours partiel'),
        'activities/page_401/page.xml' => '<?xml not valid xml at all <<<',
        'activities/label_402/label.xml' => mbzLabelXml('Étiquette saine', '<p>ok</p>'),
    ];

    $result = (new MoodleBackupImportService())->import(mbzUpload(mbzZipFile($files)), mbzInstructor());

    expect($result['items_imported'])->toBe(1);
    expect($result['items_ignored'])->toBe(['page' => 1]);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. LIVEWIRE - CourseMoodleImport : gate, autorisation, parcours complet
// ─────────────────────────────────────────────────────────────────────────────

test('éditeur : drapeau moodle_import_enabled OFF (défaut) - l\'écran d\'import répond 404', function (): void {
    Livewire::actingAs(mbzInstructor())
        ->test(CourseMoodleImport::class)
        ->assertStatus(404);
});

test('éditeur : un étudiant (non autorisé à créer un cours) est refusé (403)', function (): void {
    config()->set('academy.moodle_import_enabled', true);

    Livewire::actingAs(mbzStudent())
        ->test(CourseMoodleImport::class)
        ->assertForbidden();
});

test('éditeur : un formateur importe un .mbz valide - cours créé + redirection vers l\'éditeur + résumé en flash', function (): void {
    config()->set('academy.moodle_import_enabled', true);
    $instructor = mbzInstructor();

    Livewire::actingAs($instructor)
        ->test(CourseMoodleImport::class)
        ->set('mbzFile', mbzFake(mbzMinimalValidFiles()))
        ->call('import')
        ->assertHasNoErrors();

    $course = Course::where('title', 'Cours démo Moodle')->first();
    expect($course)->not->toBeNull();
    expect($course->status)->toBe('draft');
    expect(CourseRole::where('course_id', $course->id)->where('user_id', $instructor->id)->where('role', 'owner')->exists())->toBeTrue();

    $flash = session('academy_editor_status');
    expect($flash)->toContain('2 section(s)');
    expect($flash)->toContain('3 contenu(s) importé(s)');
    expect($flash)->toContain('quiz (1)');
});

test('éditeur : un fichier invalide est refusé en erreur de champ, aucun cours créé', function (): void {
    config()->set('academy.moodle_import_enabled', true);

    Livewire::actingAs(mbzInstructor())
        ->test(CourseMoodleImport::class)
        ->set('mbzFile', mbzFakeRaw('pas une archive'))
        ->call('import')
        ->assertSet('importError', 'Le fichier n\'est pas une sauvegarde Moodle (.mbz) valide : ce n\'est pas une archive ZIP.');

    expect(Course::count())->toBe(0);
});

test('éditeur : un fichier trop volumineux est rejeté par la validation Livewire (max Ko)', function (): void {
    config()->set('academy.moodle_import_enabled', true);
    config()->set('academy.moodle_import.max_kb', 1); // 1 Ko - le fixture minimal dépasse largement

    Livewire::actingAs(mbzInstructor())
        ->test(CourseMoodleImport::class)
        ->set('mbzFile', mbzFake(mbzMinimalValidFiles()))
        ->call('import')
        ->assertHasErrors('mbzFile');

    expect(Course::count())->toBe(0);
});

test('éditeur : un admin (academy.manage) peut aussi importer', function (): void {
    config()->set('academy.moodle_import_enabled', true);

    Livewire::actingAs(mbzAdmin())
        ->test(CourseMoodleImport::class)
        ->set('mbzFile', mbzFake(mbzMinimalValidFiles()))
        ->call('import')
        ->assertHasNoErrors();

    expect(Course::where('title', 'Cours démo Moodle')->exists())->toBeTrue();
});
