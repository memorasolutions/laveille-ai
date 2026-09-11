<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Éprouve Modules\Core\Traits\RotatesCommandBackups, la règle UNIQUE de suppression des
 * sauvegardes écrites par les commandes Artisan (ticket #2434). Le deuxième test est le
 * plus important du fichier : il prouve que la borne porte sur le MOTIF du nom, jamais
 * sur le dossier, donc qu'un fichier voisin ne peut pas être emporté.
 */

use Illuminate\Support\Facades\File;

uses(Tests\TestCase::class);

function rotateurDeSauvegardes(): object
{
    return new class
    {
        use \Modules\Core\Traits\RotatesCommandBackups;

        public function rotate(string $motif, int $garde = 14): int
        {
            return $this->rotateCommandBackups($motif, $garde);
        }
    };
}

function semerDesSauvegardes(string $dossier, int $nombre): void
{
    for ($jour = 1; $jour <= $nombre; $jour++) {
        $date = '202609'.str_pad((string) $jour, 2, '0', STR_PAD_LEFT);

        File::put($dossier.'/backup-'.$date.'-120000.json', '{}');
    }
}

beforeEach(function (): void {
    $this->dir = storage_path('app/testing-rotation-'.uniqid());

    File::ensureDirectoryExists($this->dir);
});

afterEach(function (): void {
    File::deleteDirectory($this->dir);
});

it('la rotation ne garde que les N sauvegardes les plus récentes', function (): void {
    semerDesSauvegardes($this->dir, 20);

    $supprimes = rotateurDeSauvegardes()->rotate($this->dir.'/backup-*.json', 14);

    expect($supprimes)->toBe(6)
        ->and(File::files($this->dir))->toHaveCount(14)
        ->and(File::exists($this->dir.'/backup-20260920-120000.json'))->toBeTrue()
        ->and(File::exists($this->dir.'/backup-20260901-120000.json'))->toBeFalse();
});

it('un fichier voisin qui ne correspond pas au motif reste intact', function (): void {
    semerDesSauvegardes($this->dir, 20);

    $voisin = $this->dir.'/ne-touche-pas-a-ca.json';
    $contenu = '{"conserver":true}';

    File::put($voisin, $contenu);

    $supprimes = rotateurDeSauvegardes()->rotate($this->dir.'/backup-*.json', 14);

    expect($supprimes)->toBe(6)
        ->and(File::glob($this->dir.'/backup-*.json'))->toHaveCount(14)
        ->and(File::files($this->dir))->toHaveCount(15)
        ->and(File::exists($voisin))->toBeTrue()
        ->and(File::get($voisin))->toBe($contenu);
});

it("sous le seuil, rien n'est supprimé", function (): void {
    semerDesSauvegardes($this->dir, 5);

    $supprimes = rotateurDeSauvegardes()->rotate($this->dir.'/backup-*.json', 14);

    expect($supprimes)->toBe(0)
        ->and(File::files($this->dir))->toHaveCount(5);
});

it('une borne inférieure à 1 est refusée', function (): void {
    expect(fn () => rotateurDeSauvegardes()->rotate($this->dir.'/backup-*.json', 0))
        ->toThrow(InvalidArgumentException::class);
});

it('un motif sans aucune correspondance ne fait rien', function (): void {
    $supprimes = rotateurDeSauvegardes()->rotate($this->dir.'/inexistant-*.json', 14);

    expect($supprimes)->toBe(0)
        ->and(File::files($this->dir))->toHaveCount(0);
});
