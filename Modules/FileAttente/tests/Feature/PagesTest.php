<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
    // Les rendus d'erreur (404 via abort) et le PDF laissent un tampon ouvert : PHPUnit marquait
    // alors le test « risky ». On referme uniquement ce que le test a ouvert (même motif que
    // Modules/Tools/tests/Feature/PublicCrosswordControllerTest.php).
    while (ob_get_level() > $this->faObLevel) {
        ob_end_clean();
    }
});

function fa_page_board(array $opts = []): array
{
    return app(BoardService::class)->createBoard(['Marie Tremblay', 'Jean Roy', 'Luc Gagnon'], $opts);
}

it('GET /f/{token} ne mute RIEN (6.1)', function (): void {
    $r = fa_page_board();
    $tok = $r['participants'][0]['token'];
    $p = $r['participants'][0]['participant'];
    $v = $r['board']->version;

    $this->get('/f/'.$tok)->assertOk()
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertSee('noindex', false);
    $this->get('/f/'.$tok);

    expect($p->refresh()->status)->toBe(Participant::PENDING)
        ->and($r['board']->refresh()->version)->toBe($v);
});

it('POST join ajoute et reste idempotent', function (): void {
    $r = fa_page_board();
    $tok = $r['participants'][0]['token'];
    $p = $r['participants'][0]['participant'];

    $this->post('/f/'.$tok.'/join')->assertRedirect();
    expect($p->refresh()->status)->toBe(Participant::IN_LIST);
    $v = $r['board']->refresh()->version;

    $this->post('/f/'.$tok.'/join')->assertRedirect();
    expect(Participant::where('board_id', $r['board']->id)->where('status', 'in_list')->count())->toBe(1)
        ->and($r['board']->refresh()->version)->toBe($v);
});

it('POST leave retire si allow_self_leave, sinon refusé et sans effet', function (): void {
    $r = fa_page_board(['allow_self_leave' => true]);
    $tok = $r['participants'][0]['token'];
    $this->post('/f/'.$tok.'/join');
    $this->post('/f/'.$tok.'/leave')->assertRedirect();
    expect($r['participants'][0]['participant']->refresh()->status)->toBe(Participant::REMOVED);

    $n = fa_page_board(['allow_self_leave' => false]);
    $t2 = $n['participants'][0]['token'];
    $this->post('/f/'.$t2.'/join');
    $this->post('/f/'.$t2.'/leave')->assertForbidden();
    expect($n['participants'][0]['participant']->refresh()->status)->toBe(Participant::IN_LIST);
});

it('reorder avec expectedVersion périmé = 409 et ordre intact', function (): void {
    $r = fa_page_board();
    foreach ($r['participants'] as $x) {
        $this->post('/f/'.$x['token'].'/join');
    }
    $board = $r['board']->refresh();
    $manage = $r['manage_token'];
    $third = $r['participants'][2]['participant']->refresh();
    $before = Participant::orderBy('sort_key')->pluck('id')->all();

    $this->postJson('/g/'.$manage.'/reorder', [
        'id' => $third->id, 'afterSortKey' => null, 'expectedVersion' => (int) $board->version - 1,
    ])->assertStatus(409);
    expect(Participant::orderBy('sort_key')->pluck('id')->all())->toBe($before);

    $this->postJson('/g/'.$manage.'/reorder', [
        'id' => $third->id, 'afterSortKey' => null, 'expectedVersion' => (int) $board->version,
    ])->assertOk();
    expect(Participant::orderBy('sort_key')->first()->id)->toBe($third->id);
});

it('etat public : 204 si inchangé, JSON plus récent après mutation, jamais full_name (6.5)', function (): void {
    $r = fa_page_board();
    $d = $r['display_token'];
    $v = (int) $r['board']->version;

    $this->get("/a/$d/etat?v=$v")->assertNoContent();

    $this->post('/f/'.$r['participants'][0]['token'].'/join');
    $res = $this->get("/a/$d/etat?v=$v")->assertOk();
    expect($res->json('version'))->toBeGreaterThan($v);

    $raw = $this->get("/a/$d/etat")->assertOk()->getContent();
    expect($raw)->not->toContain('full_name')
        ->and($raw)->not->toContain('Tremblay')
        ->and($raw)->not->toContain('Marie Tremblay');
    expect($this->get("/outils/file-attente/a/$d")->getContent())->not->toContain('Tremblay');
});

it('204 de l affichage ne touche pas MySQL une fois le cache chaud', function (): void {
    $r = fa_page_board();
    $d = $r['display_token'];
    $v = (int) $r['board']->version;
    $this->get("/a/$d/etat?v=$v")->assertNoContent();

    \DB::enableQueryLog();
    $this->get("/a/$d/etat?v=$v")->assertNoContent();
    expect(collect(\DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'fa_')))->toHaveCount(0);
});

it('jetons inconnus = 404, et l affichage n a aucun pouvoir d écriture', function (): void {
    $r = fa_page_board();
    $bad = str_repeat('a', 48);
    $this->get('/outils/file-attente/g/'.$bad)->assertNotFound();
    $this->get('/g/'.$bad.'/etat')->assertNotFound();
    $this->get('/f/'.$bad)->assertNotFound();
    $this->get('/a/'.$bad.'/etat')->assertNotFound();

    $d = $r['display_token'];
    $this->post('/g/'.$d.'/call-next')->assertNotFound();
    $this->post('/g/'.$d.'/terminate')->assertNotFound();
    $this->get('/outils/file-attente/g/'.$d)->assertNotFound();
    expect(Board::count())->toBe(1);
});

it('animateur : page, état avec full_name, appel + annulation, terminaison', function (): void {
    $r = fa_page_board();
    $m = $r['manage_token'];
    foreach ($r['participants'] as $x) {
        $this->post('/f/'.$x['token'].'/join');
    }
    $this->get('/outils/file-attente/g/'.$m)->assertOk()->assertSee('Appeler le suivant');
    $state = $this->get('/g/'.$m.'/etat')->assertOk()->json();
    expect($state['items'][0]['full_name'])->toBe('Marie Tremblay');

    $called = $this->postJson('/g/'.$m.'/call-next')->assertOk()->json('called');
    expect(Participant::find($called['id'])->status)->toBe('called');
    $this->postJson('/g/'.$m.'/undo-call/'.$called['id'])->assertOk();
    expect(Participant::find($called['id'])->status)->toBe('in_list');

    $this->post('/g/'.$m.'/participants', ['name' => '<b>Zoé</b> Lavoie'])->assertRedirect();
    expect(Participant::where('full_name', 'Zoé Lavoie')->exists())->toBeTrue();

    $this->post('/g/'.$m.'/terminate')->assertRedirect(route('fileattente.create'));
    expect(Board::count())->toBe(0)->and(Participant::count())->toBe(0);
    $this->get('/a/'.$r['display_token'].'/etat')->assertNotFound();
});

it('création : crée le tableau, montre les liens, valide longueur et plafond', function (): void {
    $this->get('/outils/file-attente')->assertOk();
    $res = $this->post('/outils/file-attente', [
        'names' => "Marie Tremblay\nJean Roy", 'mask_mode' => 'pseudo', 'scan_mode' => 'borne', 'allow_self_leave' => '1',
    ])->assertOk()->assertSee('Exporter le PDF');
    expect(Board::count())->toBe(1)->and($res->getContent())->toContain('/outils/file-attente/g/');

    $this->post('/outils/file-attente', [
        'names' => str_repeat('x', 41), 'mask_mode' => 'initial', 'scan_mode' => 'borne',
    ])->assertSessionHasErrors('names');
    $this->post('/outils/file-attente', [
        'names' => implode("\n", array_map(fn ($i) => "N$i", range(1, 61))), 'mask_mode' => 'initial', 'scan_mode' => 'borne',
    ])->assertSessionHasErrors('names');
    expect(Board::count())->toBe(1);
});

it('le PDF est servi avec le jeton de gestion seulement', function (): void {
    $r = fa_page_board();
    $res = $this->get('/g/'.$r['manage_token'].'/pdf')->assertOk();
    expect($res->headers->get('Content-Type'))->toContain('application/pdf');
    $this->get('/g/'.$r['display_token'].'/pdf')->assertNotFound();
});
