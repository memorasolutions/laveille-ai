<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Audit AdSense 2026-08-20 (« contenu de faible valeur ») : /roadmap passe noindex tant qu'aucune
 * proposition publique réelle n'existe (compte réel sur les tableaux publics, hors board
 * glossaire-communautaire - même filtre que PublicBoardController::index()). Même mécanisme que
 * les autres corrections de la spec (page_noindex, réutilisé tel quel). PAS de délégation ici, ce
 * test n'est PAS exécuté par ce sous-agent (contrainte projet - le superviseur lance la suite une
 * seule fois, en série).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Roadmap\Models\Board;
use Modules\Roadmap\Models\Idea;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.noindex', false);
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('noindex sur /roadmap quand aucune proposition publique n\'existe', function () {
    Board::factory()->create(['is_public' => true]);

    $response = $this->get(route('roadmap.boards.index'));

    $response->assertOk();
    $response->assertSee('noindex, follow', false);
});

test('pas de noindex sur /roadmap quand au moins une proposition publique existe', function () {
    $board = Board::factory()->create(['is_public' => true]);
    Idea::factory()->create(['board_id' => $board->id]);

    $response = $this->get(route('roadmap.boards.index'));

    $response->assertOk();
    $response->assertDontSee('noindex', false);
});

test('noindex sur /roadmap si des propositions n\'existent que sur un board privé', function () {
    $privateBoard = Board::factory()->create(['is_public' => false]);
    Idea::factory()->create(['board_id' => $privateBoard->id]);
    Board::factory()->create(['is_public' => true]);

    $response = $this->get(route('roadmap.boards.index'));

    $response->assertOk();
    $response->assertSee('noindex, follow', false);
});
