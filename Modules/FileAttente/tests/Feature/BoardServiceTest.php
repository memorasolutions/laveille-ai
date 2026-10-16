<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\FileAttente\Exceptions\StaleBoardVersionException;
use Modules\FileAttente\Models\Participant;
use Modules\FileAttente\Services\BoardService;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function fa_make(array $names = ['Marie Tremblay', 'Jean Roy', 'Luc Gagnon'], array $opts = []): array
{
    return app(BoardService::class)->createBoard($names, $opts);
}

it('a) join deux fois = une seule entrée in_list', function (): void {
    $svc = app(BoardService::class);
    $r = fa_make();
    $tok = $r['participants'][0]['token'];
    $svc->join($r['board'], $tok);
    $v = $r['board']->refresh()->version;
    $svc->join($r['board'], $tok);
    expect(Participant::where('board_id', $r['board']->id)->where('status', 'in_list')->count())->toBe(1)
        ->and($r['board']->refresh()->version)->toBe($v);
});

it('b) leave est idempotent et la réinscription est possible', function (): void {
    $svc = app(BoardService::class);
    $r = fa_make();
    $tok = $r['participants'][0]['token'];
    $svc->join($r['board'], $tok);
    $svc->leave($r['board'], $tok);
    $svc->leave($r['board'], $tok);
    expect(Participant::where('status', 'removed')->count())->toBe(1);
    $p = $svc->join($r['board'], $tok);
    expect($p->status)->toBe('in_list')
        ->and(Participant::where('board_id', $r['board']->id)->where('status', 'in_list')->count())->toBe(1);
});

it('c) reorder est rejeté si la version est périmée', function (): void {
    $svc = app(BoardService::class);
    $r = fa_make();
    foreach ($r['participants'] as $x) {
        $svc->join($r['board'], $x['token']);
    }
    $stale = $r['board']->version - 1;
    $svc->reorder($r['board'], $r['participants'][2]['participant'], null, $stale);
})->throws(StaleBoardVersionException::class);

it('d) espacement sort_key, milieu entre voisins et renumérotation à la collision', function (): void {
    $svc = app(BoardService::class);
    $r = fa_make(['A A', 'B B', 'C C', 'D D']);
    foreach ($r['participants'] as $x) {
        $svc->join($r['board'], $x['token']);
    }
    [$a, $b, $c, $d] = array_map(fn ($x) => $x['participant'], $r['participants']);
    expect($a->refresh()->sort_key)->toBe(1024)->and($b->refresh()->sort_key)->toBe(2048);

    // D entre A et B : milieu
    $svc->reorder($r['board'], $d, 1024, $r['board']->refresh()->version);
    expect($d->refresh()->sort_key)->toBe(1536);

    // Écart épuisé : on force A=10, B=11 puis on insère C entre les deux -> renumérotation espacée
    Participant::where('id', $a->id)->update(['sort_key' => 10]);
    Participant::where('id', $b->id)->update(['sort_key' => 11]);
    Participant::where('id', $d->id)->update(['sort_key' => 12]);
    Participant::where('id', $c->id)->update(['sort_key' => 13]);
    $svc->reorder($r['board'], $c, 10, $r['board']->refresh()->version);
    $order = Participant::where('board_id', $r['board']->id)->orderBy('sort_key')->pluck('id')->all();
    expect($order)->toBe([$a->id, $c->id, $b->id, $d->id])
        ->and(Participant::whereIn('id', $order)->orderBy('sort_key')->pluck('sort_key')->all())->toBe([1024, 2048, 3072, 4096]);
});

it('e) joined_seq est strictement croissant', function (): void {
    $svc = app(BoardService::class);
    $r = fa_make(['A A', 'B B', 'C C']);
    $svc->animatorAdd($r['board'], 'D D');
    $seqs = Participant::where('board_id', $r['board']->id)->orderBy('id')->pluck('joined_seq')->all();
    expect($seqs)->toBe([1, 2, 3, 4]);
});

it('f) publicState ne contient jamais full_name', function (): void {
    $svc = app(BoardService::class);
    $r = fa_make();
    foreach ($r['participants'] as $x) {
        $svc->join($r['board'], $x['token']);
    }
    $state = $svc->publicState($r['board']);
    $json = json_encode($state, JSON_UNESCAPED_UNICODE);
    expect($state['items'])->toHaveCount(3)
        ->and($json)->not->toContain('full_name')->not->toContain('Tremblay')->not->toContain('Gagnon')
        ->and(array_keys($state['items'][0]))->toBe(['position', 'label', 'status'])
        ->and(json_encode($r['participants'][0]['participant']->toArray()))->not->toContain('Tremblay');
});

it('g) mask_mode produit le bon display_label', function (): void {
    $i = fa_make(['Marie Tremblay', 'Jean'], ['mask_mode' => 'initial'])['participants'];
    expect($i[0]['participant']->display_label)->toBe('Marie T.')->and($i[1]['participant']->display_label)->toBe('Jean');
    $p = fa_make(['Marie Tremblay', 'Jean'], ['mask_mode' => 'pseudo'])['participants'];
    expect($p[0]['participant']->display_label)->toBe('Castor')->and($p[1]['participant']->display_label)->toBe('Renard');
    $n = fa_make(['Marie Tremblay', 'Jean'], ['mask_mode' => 'numero'])['participants'];
    expect($n[0]['participant']->display_label)->toBe('N° 1')->and($n[1]['participant']->display_label)->toBe('N° 2');
});

it('jetons hachés, callNext, version en cache et plafond', function (): void {
    $svc = app(BoardService::class);
    $r = fa_make();
    expect($r['board']->manage_token_hash)->toBe(hash('sha256', $r['manage_token']))
        ->and($r['board']->manage_token_hash)->not->toBe($r['manage_token']);
    foreach ($r['participants'] as $x) {
        $svc->join($r['board'], $x['token']);
    }
    $called = $svc->callNext($r['board']);
    expect($called->status)->toBe('called')
        ->and(Cache::get("fa.board.{$r['board']->id}.v"))->toBe($r['board']->refresh()->version);
    config(['file-attente.max_participants' => 2]);
    expect(fn () => fa_make())->toThrow(InvalidArgumentException::class);
});
