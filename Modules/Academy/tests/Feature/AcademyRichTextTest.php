<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - B4 : rendu markdown SÛR du document + aperçu éditeur + états vides.
 *
 * SÉCURITÉ (anti-XSS stockée) : LessonItem::renderRichText() interprète le markdown
 * avec html_input=strip → tout HTML brut embarqué (script/onerror/iframe…) est RETIRÉ
 * avant rendu. Le HTML produit ne contient donc jamais de vecteur exécutable issu de
 * l'utilisateur ; seul le balisage généré par CommonMark depuis le markdown subsiste.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\LessonItem;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// 1. SÉCURITÉ : le HTML brut malveillant est strippé (html_input=strip)
// ─────────────────────────────────────────────────────────────────────────────

test('renderRichText strippe les balises script (anti-XSS stockée)', function (): void {
    $raw  = "Avant <script>alert(1)</script> après";
    $html = LessonItem::renderRichText($raw);

    expect($html)->not->toContain('<script')
        ->and($html)->not->toContain('alert(1)</script')
        ->and($html)->not->toContain('</script>');
});

test('renderRichText strippe les attributs/balises onerror (anti-XSS stockée)', function (): void {
    $raw  = '<img src=x onerror="alert(document.cookie)">';
    $html = LessonItem::renderRichText($raw);

    expect($html)->not->toContain('onerror')
        ->and($html)->not->toContain('<img');
});

test('renderRichText neutralise les liens markdown javascript: (allow_unsafe_links=false)', function (): void {
    $raw  = '[clique-moi](javascript:alert(1))';
    $html = LessonItem::renderRichText($raw);

    // Le href dangereux ne doit pas survivre tel quel.
    expect($html)->not->toContain('href="javascript:');
});

test('renderRichText sur une valeur vide ou nulle retourne une chaîne vide', function (): void {
    expect(LessonItem::renderRichText(null))->toBe('')
        ->and(LessonItem::renderRichText(''))->toBe('');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. MARKDOWN BASIQUE : gras / italique / titres / listes / liens
// ─────────────────────────────────────────────────────────────────────────────

test('renderRichText convertit le markdown basique (gras, italique, liste, titre)', function (): void {
    $raw = "## Mon titre\n\nUn **mot gras** et un *mot italique*.\n\n- premier\n- second";
    $html = LessonItem::renderRichText($raw);

    expect($html)->toContain('<strong>mot gras</strong>')
        ->and($html)->toContain('<em>mot italique</em>')
        ->and($html)->toContain('<li>')
        ->and($html)->toContain('<h2>');
});

test('renderRichText rend un lien markdown sûr en ancre HTML', function (): void {
    $html = LessonItem::renderRichText('[La veille](https://laveille.ai)');

    expect($html)->toContain('<a href="https://laveille.ai"')
        ->and($html)->toContain('La veille</a>');
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. APERÇU ÉDITEUR : previewRichText() passe par le MÊME helper sûr
// ─────────────────────────────────────────────────────────────────────────────

test('previewRichText de l\'éditeur produit le même rendu sûr que le lecteur', function (): void {
    $course = makeRtCourse();
    $admin  = makeRtAdmin();

    $raw = "**gras** <script>alert(1)</script>";

    $component = Livewire::actingAs($admin)
        ->test(CourseEditor::class, ['course' => $course]);

    $preview = $component->instance()->previewRichText($raw);

    expect($preview)->toBe(LessonItem::renderRichText($raw))
        ->and($preview)->toContain('<strong>gras</strong>')
        ->and($preview)->not->toContain('<script');
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ÉTATS VIDES / ONBOARDING : un cours sans chapitre affiche l'accueil + l'empty state
// ─────────────────────────────────────────────────────────────────────────────

test('l\'éditeur affiche l\'onboarding et l\'état vide pour un cours sans chapitre', function (): void {
    $course = makeRtCourse();
    $admin  = makeRtAdmin();

    Livewire::actingAs($admin)
        ->test(CourseEditor::class, ['course' => $course])
        ->assertSee('Bienvenue', false)
        ->assertSee('Votre formation est vide', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function makeRtCourse(string $slug = 'cours-rt', string $title = 'Cours RichText'): Course
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

function makeRtAdmin(): User
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
