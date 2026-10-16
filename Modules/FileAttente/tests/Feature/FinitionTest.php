<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FileAttente\Models\Board;
use Modules\FileAttente\Models\Participant;
use Modules\FileAttente\Services\BoardService;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('fr');
    config(['file-attente.under_construction' => false]);
    $this->faObLevel = ob_get_level();
});

afterEach(function (): void {
    while (ob_get_level() > $this->faObLevel) {
        ob_end_clean();
    }
});

function fa_fin_board(array $opts = []): array
{
    return app(BoardService::class)->createBoard(['Marie Tremblay', 'Jean Roy'], $opts);
}

function fa_super_admin(): User
{
    test()->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    // isSuperAdmin() exige le rôle ET l'email configuré (même montage que les tests du module Decido).
    $u = User::factory()->create(['email' => config('app.superadmin_email')]);
    $u->assignRole('super_admin');

    return $u;
}

it('en construction : le public reçoit 503 partout (création, gestion, affichage, scan, états JSON) et rien ne s écrit', function (): void {
    $r = fa_fin_board();
    config(['file-attente.under_construction' => true]);
    $tok = $r['participants'][0]['token'];

    $this->get('/outils/file-attente')->assertStatus(503)->assertSee('Bientôt disponible');
    $this->post('/outils/file-attente', ['names' => 'Zoé', 'mask_mode' => 'initial', 'scan_mode' => 'borne'])->assertStatus(503);
    $this->get('/outils/file-attente/g/'.$r['manage_token'])->assertStatus(503);
    $this->get('/outils/file-attente/a/'.$r['display_token'])->assertStatus(503);
    $this->get('/f/'.$tok)->assertStatus(503);
    $this->post('/f/'.$tok.'/join')->assertStatus(503);
    $this->get('/g/'.$r['manage_token'].'/etat')->assertStatus(503);

    expect(Board::count())->toBe(1)
        ->and($r['participants'][0]['participant']->refresh()->status)->toBe(Participant::PENDING);
});

it('en construction : la page 503 garde l entête de marque et les en-têtes de vie privée', function (): void {
    config(['file-attente.under_construction' => true, 'file-attente.brand.name' => 'Acme Atelier']);
    $this->get('/outils/file-attente')->assertStatus(503)
        ->assertSee('Acme Atelier')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertSee('noindex', false);
});

it('en construction : la carte /outils est absente pour le public, présente pour le super-admin', function (): void {
    config(['file-attente.under_construction' => true]);
    $this->get('/outils')->assertOk()->assertDontSee('File d’attente par code QR');

    $this->actingAs(fa_super_admin())->get('/outils')->assertOk()->assertSee('File d’attente par code QR');
});

it('en construction : le super-admin utilise tout le module', function (): void {
    $r = fa_fin_board();
    config(['file-attente.under_construction' => true]);
    $this->actingAs(fa_super_admin());

    $this->get('/outils/file-attente')->assertOk();
    $this->get('/f/'.$r['participants'][0]['token'])->assertOk();
    $this->get('/outils/file-attente/g/'.$r['manage_token'])->assertOk();
});

it('entête de marque : pilotée par la config sur toutes les pages, jamais codée en dur', function (): void {
    config(['file-attente.brand.name' => 'Acme Atelier', 'file-attente.brand.url' => 'https://acme.test', 'file-attente.brand.logo' => '/x/acme.svg', 'file-attente.brand.logo_light' => '/x/acme.svg']);
    $r = fa_fin_board();

    $pages = [
        $this->get('/outils/file-attente'),
        $this->get('/outils/file-attente/g/'.$r['manage_token']),
        $this->get('/outils/file-attente/a/'.$r['display_token']),
        $this->get('/f/'.$r['participants'][0]['token']),
        $this->post('/outils/file-attente', ['names' => 'Zoé Roy', 'mask_mode' => 'initial', 'scan_mode' => 'borne']),
    ];
    foreach ($pages as $res) {
        $res->assertOk()->assertSee('Acme Atelier')->assertSee('https://acme.test', false)->assertSee('/x/acme.svg', false);
        expect($res->getContent())->not->toContain('logo-eye')->and($res->getContent())->toContain('noindex');
    }
});

it('entête de marque : nom par défaut = app.name, logo clair sur l affichage sombre', function (): void {
    config(['app.name' => 'Mon Site', 'file-attente.brand.name' => null]);
    $r = fa_fin_board();
    $this->get('/outils/file-attente')->assertOk()->assertSee('Mon Site')->assertSee('/images/logo-eye.svg', false);
    $this->get('/outils/file-attente/a/'.$r['display_token'])->assertOk()->assertSee('/images/logo-eye-white.svg', false);
});

it('page créée : bandeau d info (pas rouge) et un bouton Copier par lien', function (): void {
    $res = $this->post('/outils/file-attente', ['names' => "Marie Tremblay\nJean Roy", 'mask_mode' => 'initial', 'scan_mode' => 'borne'])->assertOk();
    $html = $res->getContent();
    expect(substr_count($html, 'data-copy='))->toBe(2)
        ->and($html)->toContain('class="info"')
        ->and($html)->not->toContain('class="err"');
});

it('scan : le rappel « ne change rien » disparaît une fois la personne dans la file', function (): void {
    $r = fa_fin_board();
    $tok = $r['participants'][0]['token'];
    $this->get('/f/'.$tok)->assertSee('ne change rien');
    $this->post('/f/'.$tok.'/join');
    $this->get('/f/'.$tok)->assertOk()->assertDontSee('ne change rien');
});

it('animateur : « Arrivé » fait entrer en fin de file, « Retirer » écarte une personne pas encore arrivée', function (): void {
    $r = fa_fin_board();
    $m = $r['manage_token'];
    [$a, $b] = [$r['participants'][0]['participant'], $r['participants'][1]['participant']];
    $v = (int) $r['board']->version;

    $this->postJson('/g/'.$m.'/participants/'.$a->id.'/arrive')->assertOk();
    expect($a->refresh()->status)->toBe(Participant::IN_LIST)
        ->and((int) $r['board']->refresh()->version)->toBeGreaterThan($v);

    $v2 = (int) $r['board']->version;
    $this->postJson('/g/'.$m.'/participants/'.$a->id.'/arrive')->assertOk(); // idempotent
    expect((int) $r['board']->refresh()->version)->toBe($v2);

    $this->postJson('/g/'.$m.'/participants/'.$b->id.'/remove')->assertOk();
    expect($b->refresh()->status)->toBe(Participant::REMOVED);

    $state = $this->get('/g/'.$m.'/etat')->assertOk()->json();
    expect($state['items'])->toHaveCount(1)->and($state['pending'])->toHaveCount(0);
});

it('animateur : l état versionné annonce une arrivée par scan sans rechargement (polling)', function (): void {
    $r = fa_fin_board();
    $m = $r['manage_token'];
    $v = (int) $r['board']->version;
    $this->get("/g/$m/etat?v=$v")->assertNoContent();

    $this->post('/f/'.$r['participants'][1]['token'].'/join');
    $res = $this->get("/g/$m/etat?v=$v")->assertOk();
    expect($res->json('version'))->toBeGreaterThan($v)
        ->and($res->json('items.0.full_name'))->toBe('Jean Roy')
        ->and($res->json('pending'))->toHaveCount(1);
});

it('écran de projection : compteur, appelé en grand, libellé masqué seulement', function (): void {
    $r = fa_fin_board();
    $d = $r['display_token'];
    $html = $this->get("/outils/file-attente/a/$d")->assertOk()->getContent();
    expect($html)->toContain('fa-called-name')->toContain('id="fa-count"')->not->toContain('Tremblay');
});
