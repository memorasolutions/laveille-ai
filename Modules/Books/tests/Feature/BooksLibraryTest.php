<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Gate « EN CONSTRUCTION » et rendu public du module Books (/livres).
 *
 * Comportement attendu tant que config('books.under_construction') === true :
 *   - Visiteur (guest) sur /livres et /livres/{slug} → page « en construction » (503).
 *   - Superadmin sur /livres → 200 + catalogue des 5 livres (2 essais + trilogie Nexus Neural).
 *   - Superadmin sur /livres/{slug} → 200 + JSON-LD Schema.org (@type Book).
 *   - Fiche inexistante → 404 (pas 503, pas 500), même pour le superadmin.
 *
 * Pattern calqué sur Modules\Academy\Tests\Feature\AcademyUnderConstructionTest.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Superadmin : isSuperAdmin() exige email === config('app.superadmin_email') ET rôle super_admin.
    $this->superadmin = User::factory()->create([
        'email' => config('app.superadmin_email'),
    ]);
    $this->superadmin->assignRole('super_admin');

    config()->set('books.under_construction', true);
});

// ── 1. Guest bloqué sur l'index ────────────────────────────────────────────

test('guest sur /livres reçoit la page en construction (503)', function (): void {
    $response = $this->get('/livres');

    $response->assertStatus(503);
    $response->assertSee('Bibliothèque en construction', false);
});

// ── 2. Guest bloqué sur une fiche livre ────────────────────────────────────

test('guest sur /livres/ia-sans-se-faire-poursuivre reçoit aussi la page en construction (503)', function (): void {
    $response = $this->get('/livres/ia-sans-se-faire-poursuivre');

    $response->assertStatus(503);
    $response->assertSee('Bibliothèque en construction', false);
});

// ── 3. Superadmin voit le catalogue des 5 livres ───────────────────────────

test('superadmin sur /livres voit le catalogue des 5 livres', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres');

    $response->assertOk();
    // Apostrophe échappée en HTML entity (L&#039;IA...) par Blade : on cherche le fragment sans apostrophe.
    $response->assertSee('IA sans se faire poursuivre', false);
    $response->assertSee('IA pour les parents', false);
    $response->assertSee('Nexus Neural : Tome 1', false);
    $response->assertSee('Nexus Neural : Tome 2', false);
    $response->assertSee('Nexus Neural : Tome 3', false);
});

// ── 4. Le catalogue regroupe visuellement les 3 tomes Nexus Neural ────────

test('le catalogue regroupe les 3 tomes sous la bannière Trilogie Nexus Neural', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres');

    $response->assertOk();
    $response->assertSee('Trilogie Nexus Neural', false);
});

// ── 5. Fiche livre : 200 + JSON-LD Book ─────────────────────────────────────

test('superadmin sur une fiche livre reçoit un 200 avec le JSON-LD Book', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres/ia-sans-se-faire-poursuivre');

    $response->assertOk();
    $response->assertSee('application/ld+json', false);
    $response->assertSee('"@type": "Book"', false);
});

// ── 6. Fiche inexistante → 404 (pas 503, pas 500) ──────────────────────────

test('une fiche livre inexistante retourne 404 pour un superadmin', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres/slug-inexistant-xyz');

    $response->assertStatus(404);
});

// ── 7. Extrait feuilletable (flip-reader) ───────────────────────────────────
// #flip-reader-extrait-2026-07-09 : bouton « Feuilleter » présent sur les 5
// fiches ayant un dossier public/images/livres-extraits/{slug}/ ; nombre de
// pages annoncé = compte réel scanné (Book::excerptPages(), pas de valeur en dur).

test('la fiche ia-sans-se-faire-poursuivre affiche le bouton feuilleter avec 26 pages', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres/ia-sans-se-faire-poursuivre');

    $response->assertOk();
    $response->assertSee('Feuilleter les 26 premières pages', false);
});

test('la fiche nexus-neural-tome-1 affiche le bouton feuilleter avec 18 pages', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres/nexus-neural-tome-1');

    $response->assertOk();
    $response->assertSee('Feuilleter les 18 premières pages', false);
});

test('Book::excerptPages retourne un tableau vide pour un slug sans dossier extrait', function (): void {
    $book = new \Modules\Books\Models\Book(['slug' => 'slug-sans-dossier-extrait-xyz', 'title' => 'Test']);

    expect($book->excerptPages())->toBe([]);
});

// #flip-reader-lqip-2026-07-09 : chaque page porte désormais une clé 'lqip'
// (blur-up pendant le chargement, x-fronttheme::flip-reader) - présente et
// pointant vers le fichier page-NN-lqip.jpg généré via ImageMagick quand il
// existe, jamais de valeur en dur (Book::excerptPages scanne le disque).

test('Book::excerptPages inclut la clé lqip pointant vers le fichier -lqip.jpg généré', function (): void {
    $book = new \Modules\Books\Models\Book(['slug' => 'ia-sans-se-faire-poursuivre', 'title' => 'Test']);

    $pages = $book->excerptPages();

    expect($pages)->not->toBeEmpty();

    foreach ($pages as $page) {
        expect($page)->toHaveKeys(['image', 'lqip', 'alt', 'width', 'height']);
        expect($page['lqip'])->not->toBeNull();
        expect($page['lqip'])->toEndWith('-lqip.jpg');
    }
});

test('Book::excerptPages ne confond pas les fichiers -lqip.jpg avec des pages', function (): void {
    $book = new \Modules\Books\Models\Book(['slug' => 'ia-sans-se-faire-poursuivre', 'title' => 'Test']);

    // 26 pages annoncées par le test « feuilleter » ci-dessus : le glob page-*.jpg
    // matche aussi page-NN-lqip.jpg s'il n'est pas explicitement filtré - régression
    // à surveiller (aurait doublé le compte de pages).
    expect($book->excerptPages())->toHaveCount(26);
});

test('Book::excerptPages retourne lqip=null si le fichier LQIP est absent', function (): void {
    // Slug avec dossier + une page mais sans LQIP généré : simule le cas défensif
    // (squelette shimmer seul, sans blur-up) via un dossier de test jetable.
    $dir = public_path('images/livres-extraits/test-sans-lqip-tmp');
    @mkdir($dir, 0755, true);
    copy(
        public_path('images/livres-extraits/ia-sans-se-faire-poursuivre/page-01.jpg'),
        $dir.'/page-01.jpg'
    );

    $book = new \Modules\Books\Models\Book(['slug' => 'test-sans-lqip-tmp', 'title' => 'Test']);
    $pages = $book->excerptPages();

    @unlink($dir.'/page-01.jpg');
    @rmdir($dir);

    expect($pages)->toHaveCount(1);
    expect($pages[0]['lqip'])->toBeNull();
});
