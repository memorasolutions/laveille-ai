<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
    while (ob_get_level() > $this->faObLevel) {
        ob_end_clean();
    }
});

function fa_post_create(object $t, array $over = [])
{
    return $t->post('/outils/file-attente', $over + ['names' => "Marie Tremblay\nJean Roy", 'mask_mode' => 'initial', 'scan_mode' => 'borne']);
}

it('quota : la Ne création passe, la (N+1)e est refusée proprement, par session', function (): void {
    config(['file-attente.boards_per_hour' => 3]);
    for ($i = 0; $i < 3; $i++) {
        fa_post_create($this)->assertOk();
    }
    expect(Board::count())->toBe(3);

    fa_post_create($this)->assertRedirect()->assertSessionHasErrors('names');
    expect(Board::count())->toBe(3)
        ->and(session('errors')->first('names'))->toContain('3');
});

it('quota : jamais par IP, une autre session de la MÊME adresse crée librement', function (): void {
    config(['file-attente.boards_per_hour' => 1]);
    $ip = ['REMOTE_ADDR' => '203.0.113.7'];

    $this->withServerVariables($ip);
    fa_post_create($this)->assertOk();
    fa_post_create($this)->assertSessionHasErrors('names');

    // Nouvelle session (autre poste derrière la même IP, ex. une classe).
    $this->flushSession();
    $this->app['session']->driver()->flush();
    $this->withServerVariables($ip);
    fa_post_create($this)->assertOk();
    expect(Board::count())->toBe(2);
});

it('quota : les horodatages de plus d une heure ne comptent plus', function (): void {
    config(['file-attente.boards_per_hour' => 1]);
    $this->withSession(['fa.created_at' => [time() - 3700]]);
    fa_post_create($this)->assertOk();
    expect(Board::count())->toBe(1);
});

it('purge : supprime le tableau expiré avec ses participants, garde le frais, idempotente', function (): void {
    $svc = app(BoardService::class);
    $old = $svc->createBoard(['Marie Tremblay', 'Jean Roy']);
    $fresh = $svc->createBoard(['Luc Gagnon']);
    Board::whereKey($old['board']->id)->update(['purge_after' => now()->subMinute()]);
    // L'activité récente ne prolonge pas la vie du tableau : seul purge_after compte.
    Board::whereKey($old['board']->id)->update(['last_activity_at' => now()]);

    expect(Artisan::call('fileattente:purge'))->toBe(0);

    expect(Board::whereKey($old['board']->id)->exists())->toBeFalse()
        ->and(Participant::where('board_id', $old['board']->id)->count())->toBe(0)
        ->and(Board::whereKey($fresh['board']->id)->exists())->toBeTrue()
        ->and(Participant::where('board_id', $fresh['board']->id)->count())->toBe(1)
        ->and(Cache::has($svc->cacheKey($old['board'])))->toBeFalse();

    // Idempotence : un second passage ne change rien et ne casse rien.
    expect(Artisan::call('fileattente:purge'))->toBe(0)
        ->and(Board::count())->toBe(1);
});

it('purge : retire aussi les PDF temporaires périmés, garde les récents', function (): void {
    $dir = storage_path('app/fileattente/tmp');
    @mkdir($dir, 0775, true);
    $oldFile = $dir.'/test-old-'.uniqid().'.pdf';
    $newFile = $dir.'/test-new-'.uniqid().'.pdf';
    file_put_contents($oldFile, '%PDF');
    file_put_contents($newFile, '%PDF');
    touch($oldFile, time() - 48 * 3600);

    Artisan::call('fileattente:purge');

    expect(file_exists($oldFile))->toBeFalse()->and(file_exists($newFile))->toBeTrue();
    @unlink($newFile);
});

it('purge : planifiée toutes les heures par le module', function (): void {
    $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
        ->filter(fn ($e) => str_contains((string) $e->command, 'fileattente:purge'));
    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 * * * *');
});

it('/outils : la carte est présente quand le module est actif, absente via l interrupteur de rendu', function (): void {
    $this->get('/outils')->assertOk()
        ->assertSee('File d’attente par code QR')
        ->assertSee('file-attente');

    // Interrupteur d'affichage : même chemin de rendu, carte retirée sans toucher aux routes.
    config(['file-attente.show_in_tools' => false]);
    $this->get('/outils')->assertOk()->assertDontSee('File d’attente par code QR');
});

it('i18n : fr, en et fr_CA ont exactement les mêmes clés, sans valeur vide', function (): void {
    $flat = function (array $a, string $p = '') use (&$flat): array {
        $out = [];
        foreach ($a as $k => $v) {
            is_array($v) ? $out += $flat($v, $p.$k.'.') : $out[$p.$k] = $v;
        }

        return $out;
    };
    $base = module_path('FileAttente', 'resources/lang');
    $fr = $flat(require $base.'/fr/ui.php');
    $en = $flat(require $base.'/en/ui.php');
    $ca = $flat(require $base.'/fr_CA/ui.php');

    expect(array_keys($en))->toEqualCanonicalizing(array_keys($fr))
        ->and(array_keys($ca))->toEqualCanonicalizing(array_keys($fr));
    foreach ([$fr, $en] as $set) {
        foreach ($set as $key => $val) {
            expect(trim((string) $val))->not->toBe('', $key);
        }
    }
    // Typographie française : pas d'espace ordinaire avant : ; ! ? (hors marqueurs :nom) et pas de tiret cadratin.
    foreach ($fr as $key => $val) {
        expect($val)->not->toMatch('/ (?:[;!?]|:(?![a-z]))/u', $key)->and($val)->not->toContain('—');
    }
});

it('i18n : la page de création s affiche en anglais quand la langue est en', function (): void {
    app()->setLocale('en');
    $this->get('/outils/file-attente')->assertOk()->assertSee('Create the board')->assertDontSee('Créer le tableau');
});
