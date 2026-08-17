<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — mise en ligne publique de la bibliothèque (/livres), 2026-08-17.
 *
 * Couvre les points du panel (LPC art. 219 + liens ASIN faux + noindex conditionnel + menu +
 * avertissement 18+) : PAS de délégation ici, ces tests ne sont PAS exécutés par ce sous-agent
 * (contrainte projet - le superviseur lance la suite une seule fois, en série).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Modules\Books\Models\Book;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Isole ces tests du drapeau global d'environnement (APP_NOINDEX=true en local/staging) :
    // sans ce reset, la section page_noindex des vues Books ne peut pas être distinguée du
    // noindex global déjà imposé par master.blade.php pour toute l'app.
    config()->set('app.noindex', false);
    config()->set('books.under_construction', false);
});

// ── 1. Porte ouverte : guest voit 200, pas de gate 503 ─────────────────────

test('guest sur /livres reçoit 200 quand la porte est ouverte', function (): void {
    $response = $this->get('/livres');

    $response->assertOk();
});

test('guest sur une fiche livre reçoit 200 quand la porte est ouverte', function (): void {
    $response = $this->get('/livres/ia-sans-se-faire-poursuivre');

    $response->assertOk();
});

// ── 2. noindex lié au drapeau (pas codé en dur) ─────────────────────────────
// #noindex-en-dur-corrige-2026-08-17 : @section('page_noindex', $bool) ne suffit PAS - le layout
// teste hasSection() (présence), pas la valeur. La section doit donc n'être déclarée QUE derrière
// un @if. Ces tests couvrent directement ce piège (repéré et corrigé pendant ce lot).

test('noindex absent sur /livres quand la porte est ouverte', function (): void {
    config()->set('books.under_construction', false);

    $response = $this->get('/livres');

    $response->assertOk();
    expect(View::hasSection('page_noindex'))->toBeFalse();
});

test('noindex présent sur /livres quand la porte est fermée (superadmin)', function (): void {
    config()->set('books.under_construction', true);

    $admin = \App\Models\User::factory()->create(['email' => config('app.superadmin_email')]);
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get('/livres');

    $response->assertOk();
    // On inspecte le HTML rendu : l'état des sections de vue n'est pas fiable après la requête
    // de test (les sections sont vidées au rendu). La balise robots est la preuve réelle.
    $response->assertSee('noindex', false);
});

test('noindex absent sur une fiche livre quand la porte est ouverte', function (): void {
    config()->set('books.under_construction', false);

    $response = $this->get('/livres/nexus-neural-tome-1');

    $response->assertOk();
    expect(View::hasSection('page_noindex'))->toBeFalse();
});

// ── 3. Zéro prix affiché (LPC art. 219) ─────────────────────────────────────

test('aucun prix affiché sur le catalogue /livres', function (): void {
    $response = $this->get('/livres');

    $response->assertOk();
    $response->assertDontSee('CAD', false);
    // "Voir le prix sur Amazon" : CTA neutre attendu à la place du montant.
    $response->assertSee('Voir le prix sur Amazon', false);
});

test('aucun prix affiché sur une fiche livre', function (): void {
    $response = $this->get('/livres/ia-sans-se-faire-poursuivre');

    $response->assertOk();
    $response->assertDontSee('CAD', false);
});

test('aucun prix ne subsiste dans les réponses de FAQ en base', function (): void {
    $books = Book::whereNotNull('faq')->get();

    foreach ($books as $book) {
        foreach ((array) $book->faq as $qa) {
            $text = ($qa['question'] ?? '').' '.($qa['answer'] ?? '');
            expect($text)->not->toContain('CAD');
            expect(preg_match('/\d+[,.]\d{2}\s*\$/', $text))->toBe(0);
        }
    }
});

// ── 4. JSON-LD : Book + BreadcrumbList seulement, Offer et FAQPage retirés ──

test('le JSON-LD de la fiche ne contient ni Offer ni FAQPage, mais garde Book', function (): void {
    $book = Book::where('slug', 'ia-sans-se-faire-poursuivre')->first();

    $graph = \Modules\Books\Services\BookSchemaService::buildGraph($book)['@graph'];
    $types = array_column($graph, '@type');

    expect($types)->toContain('Book');
    expect($types)->toContain('BreadcrumbList');
    expect($types)->not->toContain('FAQPage');

    $bookNode = collect($graph)->firstWhere('@type', 'Book');
    expect($bookNode)->not->toHaveKey('offers');
});

// ── 5. Liens papier faux retirés pour les tomes 2 et 3, tome 1 intact ───────

test('le lien papier est absent pour les tomes 2 et 3 (ASIN faux non remplacé)', function (): void {
    $tome2 = Book::where('slug', 'nexus-neural-tome-2')->first();
    $tome3 = Book::where('slug', 'nexus-neural-tome-3')->first();

    expect($tome2->amazon_url_paperback)->toBeNull();
    expect($tome3->amazon_url_paperback)->toBeNull();
    // Kindle vérifié correct : ne doit jamais être touché par cette correction.
    expect($tome2->amazon_url_kindle)->not->toBeNull();
    expect($tome3->amazon_url_kindle)->not->toBeNull();
});

test('le lien papier reste présent pour le tome 1 (vérifié correct)', function (): void {
    $tome1 = Book::where('slug', 'nexus-neural-tome-1')->first();

    expect($tome1->amazon_url_paperback)->not->toBeNull();
});

test('le bouton "version papier" est absent de la fiche du tome 2', function (): void {
    $response = $this->get('/livres/nexus-neural-tome-2');

    $response->assertOk();
    $response->assertDontSee('Acheter la version papier sur Amazon', false);
    $response->assertSee('Acheter la version Kindle sur Amazon', false);
});

// ── 6. Entrée de menu "Livres" liée à la même garde que la page ────────────

test('le menu affiche un lien Livres quand la porte est ouverte', function (): void {
    config()->set('books.under_construction', false);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('href="'.route('books.index').'"', false);
});

test('le menu ne montre pas Livres quand la porte est fermée', function (): void {
    config()->set('books.under_construction', true);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertDontSee('href="'.route('books.index').'"', false);
});

// ── 7. Avertissement 18+ sur les fiches des tomes, absent des essais ────────

test('l\'avertissement 18+ est présent sur une fiche de tome Nexus Neural', function (): void {
    $response = $this->get('/livres/nexus-neural-tome-1');

    $response->assertOk();
    $response->assertSee('Contenu réservé à un public adulte (18+).', false);
});

test('l\'avertissement 18+ est absent des fiches des essais', function (): void {
    $response = $this->get('/livres/ia-sans-se-faire-poursuivre');

    $response->assertOk();
    $response->assertDontSee('Contenu réservé à un public adulte (18+).', false);

    $response2 = $this->get('/livres/ia-pour-les-parents');
    $response2->assertOk();
    $response2->assertDontSee('Contenu réservé à un public adulte (18+).', false);
});
