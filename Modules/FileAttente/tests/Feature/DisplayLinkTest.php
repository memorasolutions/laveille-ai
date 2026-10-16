<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FileAttente\Models\Board;
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

function fa_dl_board(): array
{
    return app(BoardService::class)->createBoard(['Marie Tremblay', 'Jean Roy']);
}

it('displayPlainToken redonne un jeton dont le SHA-256 égale display_token_hash', function (): void {
    $r = fa_dl_board();
    $board = Board::findOrFail($r['board']->id);
    $plain = app(BoardService::class)->displayPlainToken($board);

    expect($plain)->toBe($r['display_token'])
        ->and(hash('sha256', $plain))->toBe($board->display_token_hash);
});

it('la page de gestion contient le lien d\'affichage et le bouton copier', function (): void {
    $r = fa_dl_board();
    $this->get('/outils/file-attente/g/'.$r['manage_token'])->assertOk()
        ->assertSee('/a/'.$r['display_token'], false)
        ->assertSee('id="fa-copy-display"', false);
});

it('le jeton d\'affichage et son chiffré restent absents des réponses publiques', function (): void {
    $r = fa_dl_board();
    $d = $r['display_token'];
    $tok = $r['participants'][0]['token'];
    $cipher = (string) Board::findOrFail($r['board']->id)->display_token_cipher;
    expect($cipher)->not->toBe('');

    $responses = [
        'affichage' => $this->get('/outils/file-attente/a/'.$d)->assertOk(),
        'etat' => $this->get('/a/'.$d.'/etat')->assertOk(),
        'scan' => $this->get('/f/'.$tok)->assertOk(),
        'scan-etat' => $this->get('/f/'.$tok.'/etat')->assertOk(),
    ];
    foreach ($responses as $name => $res) {
        $body = $res->getContent();
        // La page d'affichage connaît son propre jeton (son URL) : on vérifie qu'elle ne le recopie pas en lien « afficher ».
        if ($name !== 'affichage') {
            expect($body)->not->toContain($d, "$name expose le jeton d'affichage");
        }
        expect($body)->not->toContain($cipher, "$name expose display_token_cipher")
            ->and($body)->not->toContain('display_token_cipher');
    }

    expect(json_encode(Board::findOrFail($r['board']->id)->toArray()))->not->toContain('display_token');
});

it('un tableau antérieur à la migration (cipher null) lève proprement et la gestion dégrade sans 500', function (): void {
    $r = fa_dl_board();
    Board::where('id', $r['board']->id)->update(['display_token_cipher' => null]);
    $board = Board::findOrFail($r['board']->id);

    expect(fn () => app(BoardService::class)->displayPlainToken($board))->toThrow(RuntimeException::class);

    $this->get('/outils/file-attente/g/'.$r['manage_token'])->assertOk()
        ->assertSee('Lien d’affichage indisponible, recrée le tableau.', false)
        ->assertDontSee('id="fa-copy-display"', false);
});

it('un chiffré illisible (clé changée) lève aussi RuntimeException', function (): void {
    $r = fa_dl_board();
    Board::where('id', $r['board']->id)->update(['display_token_cipher' => 'pas-un-chiffre-valide']);

    expect(fn () => app(BoardService::class)->displayPlainToken(Board::findOrFail($r['board']->id)))
        ->toThrow(RuntimeException::class);
});
