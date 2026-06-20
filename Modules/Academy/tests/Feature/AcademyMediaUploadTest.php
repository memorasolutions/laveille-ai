<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Téléversement de média dans l'éditeur de cours (PHASE A / A3).
 *
 * Couvre les 3 surfaces d'upload, chacune gardée par une autorisation SERVEUR :
 *  - image de couverture du cours       → saveCover()    / authorize('update')
 *  - affiche d'un item vidéo            → uploadItemPoster() / authorize('manageStructure')
 *  - pièce jointe d'un item document    → uploadItemAttachment() / authorize('manageStructure')
 *
 * Prouve : persistance (média Spatie + référence colonne / payload), validation
 * mime + taille côté serveur, et refus d'un non-gestionnaire (OWASP A01).
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
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

/**
 * Contenu PDF minimal valide : commence par « %PDF- » pour que la détection de
 * type MIME par contenu (finfo, utilisée par Spatie) renvoie application/pdf.
 * Indispensable car UploadedFile::fake()->create() produit un fichier vide
 * (mime application/x-empty), rejeté par la collection.
 */
function fakePdf(string $name, int $padBytes = 1024): UploadedFile
{
    $content = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n".str_repeat(' ', max(0, $padBytes))."\n%%EOF";

    return UploadedFile::fake()->createWithContent($name, $content);
}

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    Storage::fake('public');

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->mediaCourse = Course::create([
        'slug'        => 'cours-media',
        'title'       => 'Cours média',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);
});

/** Admin academy.manage. */
function makeMediaAdmin(): User
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

/** Crée un item (vidéo ou document) dans un cours, en montant chapitre + leçon. */
function makeMediaItem(Course $course, string $type): LessonItem
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chap', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'Leçon', 'slug' => 'lecon-'.$type, 'position' => 1]);

    return LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => $type,
        'title'     => 'Élément '.$type,
        'position'  => 1,
        'payload'   => [],
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. IMAGE DE COUVERTURE
// ─────────────────────────────────────────────────────────────────────────────

test('admin peut téléverser une image de couverture (persistance + référence colonne)', function (): void {
    Livewire::actingAs(makeMediaAdmin())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->set('cover', UploadedFile::fake()->image('cover.jpg', 1200, 675))
        ->call('saveCover')
        ->assertHasNoErrors();

    $fresh = $this->mediaCourse->fresh();
    expect($fresh->getFirstMedia('cover'))->not->toBeNull();
    expect($fresh->image_media_id)->not->toBeNull();
    expect($fresh->coverUrl())->not->toBeNull();
});

test('couverture : un fichier non-image est rejeté côté serveur', function (): void {
    Livewire::actingAs(makeMediaAdmin())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->set('cover', UploadedFile::fake()->create('virus.php', 10, 'application/x-php'))
        ->call('saveCover')
        ->assertHasErrors(['cover']);

    expect($this->mediaCourse->fresh()->getFirstMedia('cover'))->toBeNull();
});

test('couverture : une image trop lourde (> 4 Mo) est rejetée côté serveur', function (): void {
    Livewire::actingAs(makeMediaAdmin())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->set('cover', UploadedFile::fake()->image('huge.jpg')->size(5000))
        ->call('saveCover')
        ->assertHasErrors(['cover']);

    expect($this->mediaCourse->fresh()->getFirstMedia('cover'))->toBeNull();
});

test('couverture : un utilisateur sans rôle ne peut même pas ouvrir l\'éditeur', function (): void {
    Livewire::actingAs(User::factory()->create())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->assertForbidden();
});

test('couverture : retrait de l\'image (clear collection + colonne null)', function (): void {
    $component = Livewire::actingAs(makeMediaAdmin())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->set('cover', UploadedFile::fake()->image('cover.png', 800, 450))
        ->call('saveCover');

    expect($this->mediaCourse->fresh()->getFirstMedia('cover'))->not->toBeNull();

    $component->call('removeCover')->assertHasNoErrors();

    $fresh = $this->mediaCourse->fresh();
    expect($fresh->getFirstMedia('cover'))->toBeNull();
    expect($fresh->image_media_id)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. AFFICHE (POSTER) D'UN ITEM VIDÉO
// ─────────────────────────────────────────────────────────────────────────────

test('admin peut téléverser une affiche de vidéo (média + poster_media_id + payload)', function (): void {
    $item = makeMediaItem($this->mediaCourse, 'video');

    Livewire::actingAs(makeMediaAdmin())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->set("itemPoster.{$item->id}", UploadedFile::fake()->image('poster.jpg', 1280, 720))
        ->call('uploadItemPoster', $item->id)
        ->assertHasNoErrors();

    $fresh = $item->fresh();
    expect($fresh->getFirstMedia('poster'))->not->toBeNull();
    expect($fresh->poster_media_id)->not->toBeNull();
    expect($fresh->posterUrl())->not->toBeNull();
    expect($fresh->payload['poster'] ?? null)->not->toBeNull();
});

test('affiche : un PDF (non-image) est rejeté côté serveur', function (): void {
    $item = makeMediaItem($this->mediaCourse, 'video');

    Livewire::actingAs(makeMediaAdmin())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->set("itemPoster.{$item->id}", UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))
        ->call('uploadItemPoster', $item->id)
        ->assertHasErrors(["itemPoster.{$item->id}"]);

    expect($item->fresh()->getFirstMedia('poster'))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. PIÈCE JOINTE D'UN ITEM DOCUMENT
// ─────────────────────────────────────────────────────────────────────────────

test('admin peut téléverser une pièce jointe document (payload attachments {name,url})', function (): void {
    $item = makeMediaItem($this->mediaCourse, 'document');

    Livewire::actingAs(makeMediaAdmin())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->set("itemAttachment.{$item->id}", fakePdf('cours.pdf'))
        ->call('uploadItemAttachment', $item->id)
        ->assertHasNoErrors();

    $fresh       = $item->fresh();
    $attachments = $fresh->payload['attachments'] ?? [];
    expect($attachments)->toHaveCount(1);
    expect($attachments[0])->toHaveKeys(['name', 'url', 'media_id']);
    expect($fresh->getMedia('attachments'))->toHaveCount(1);
});

test('pièce jointe : un fichier trop lourd (> 10 Mo) est rejeté côté serveur', function (): void {
    $item = makeMediaItem($this->mediaCourse, 'document');

    // 11 Mo : la règle de taille (max 10 Mo) rejette AVANT d'atteindre Spatie.
    Livewire::actingAs(makeMediaAdmin())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->set("itemAttachment.{$item->id}", UploadedFile::fake()->create('gros.pdf', 11000, 'application/pdf'))
        ->call('uploadItemAttachment', $item->id)
        ->assertHasErrors(["itemAttachment.{$item->id}"]);

    expect($item->fresh()->getMedia('attachments'))->toHaveCount(0);
});

test('pièce jointe : retrait par media_id (anti-IDOR : média lié à CET item)', function (): void {
    $item = makeMediaItem($this->mediaCourse, 'document');

    $component = Livewire::actingAs(makeMediaAdmin())
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->set("itemAttachment.{$item->id}", fakePdf('a.pdf'))
        ->call('uploadItemAttachment', $item->id);

    $mediaId = $item->fresh()->payload['attachments'][0]['media_id'];

    $component->call('removeItemAttachment', $item->id, $mediaId)->assertHasNoErrors();

    $fresh = $item->fresh();
    expect($fresh->getMedia('attachments'))->toHaveCount(0);
    expect($fresh->payload['attachments'] ?? [])->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ANTI-ESCALADE : un formateur étranger ne peut pas téléverser sur ce cours
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-ESCALADE : un formateur owner d\'un AUTRE cours ne peut pas téléverser de couverture ici', function (): void {
    $other = Course::create([
        'slug' => 'autre', 'title' => 'Autre', 'language' => 'fr-CA', 'level' => 'intro',
        'visibility' => 'public', 'access_type' => 'free', 'status' => 'draft', 'currency' => 'CAD',
    ]);
    $stranger = User::factory()->create();
    $stranger->assignRole('instructor');
    CourseRole::create(['course_id' => $other->id, 'user_id' => $stranger->id, 'role' => 'owner']);

    // Il ne peut même pas ouvrir l'éditeur du cours média (authorize('update') au mount).
    Livewire::actingAs($stranger)
        ->test(CourseEditor::class, ['course' => $this->mediaCourse])
        ->assertForbidden();

    expect($this->mediaCourse->fresh()->getFirstMedia('cover'))->toBeNull();
});
