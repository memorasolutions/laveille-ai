<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2436, volet observabilité. Le bilan de l'audit quotidien ne partait QUE par
 * courriel, et le cas « zéro non conforme » sortait de la commande AVANT tout envoi :
 * « tout va bien » était donc indiscernable de « la commande n'a jamais tourné ».
 *
 * Le premier test est le plus important : il porte précisément sur le chemin qui
 * n'écrivait rien.
 */

use Illuminate\Support\Facades\File;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->racine = sys_get_temp_dir().'/lv-audit-bilan-'.uniqid();

    File::ensureDirectoryExists($this->racine.'/app');

    $this->app->useStoragePath($this->racine);
});

afterEach(function (): void {
    File::deleteDirectory($this->racine);
});

it('écrit un bilan sur disque même quand aucun tutoriel n\'est non conforme', function (): void {
    $this->artisan('tools:audit-tutorials')->assertSuccessful();

    $fichiers = File::glob(storage_path('app/audit-tutorials-*.json'));

    expect($fichiers)->toHaveCount(1);

    $bilan = json_decode(File::get($fichiers[0]), true);

    expect($bilan)->toHaveKeys(['horodatage', 'approuves', 'non_conformes', 'corriges', 'exemples'])
        ->and($bilan['non_conformes'])->toBe(0)
        ->and($bilan['corriges'])->toBe(0);
});
