<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FileAttente\Models\Participant;
use Modules\FileAttente\Services\BoardService;
use Modules\FileAttente\Services\QrSheetService;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

it('a) plainToken redonne le jeton dont le SHA-256 == token_hash', function (): void {
    $svc = app(BoardService::class);
    $r = $svc->createBoard(['Marie Tremblay', 'Jean Roy']);
    foreach ($r['participants'] as $row) {
        $p = Participant::findOrFail($row['participant']->id);
        $plain = $svc->plainToken($p);
        expect($plain)->toBe($row['token'])
            ->and(hash('sha256', $plain))->toBe($p->token_hash);
    }
});

it('b) le PDF commence par %PDF pour N participants (multi-pages)', function (): void {
    $names = array_map(fn (int $i): string => "Personne $i Test", range(1, 14));
    $r = app(BoardService::class)->createBoard($names, ['title' => 'Atelier']);
    $pdf = app(QrSheetService::class)->render($r['board']);
    expect(str_starts_with($pdf, '%PDF'))->toBeTrue()->and(strlen($pdf))->toBeGreaterThan(5000)
        ->and(preg_match_all('/\/Type\s*\/Page[^s]/', $pdf))->toBe(2); // 14 personnes = 12 + 2
});

it('c) plainToken non vide, et le clair jamais stocké en base', function (): void {
    $svc = app(BoardService::class);
    $r = $svc->createBoard(['Luc Gagnon']);
    $tok = $r['participants'][0]['token'];
    $raw = DB::table('fa_participants')->where('id', $r['participants'][0]['participant']->id)->first();
    expect($svc->plainToken(Participant::find($raw->id)))->not->toBe('')
        ->and($raw->token_cipher)->not->toBe($tok)
        ->and($raw->token_cipher)->not->toContain($tok)
        ->and($raw->token_hash)->not->toBe($tok);
    expect(fn () => $svc->plainToken(new Participant))->toThrow(RuntimeException::class);
});

it('d) l\'URL encodée suit /f/{jeton}', function (): void {
    expect(app(QrSheetService::class)->scanUrl('abc'))->toEndWith('/f/abc');
});
