<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2289 (2026-09-05), DEUXIÈME TOUR : au premier tour, la garde avait été posée
 * directement dans les trois commandes d'enrichissement IA. Un recensement a montré une
 * douzaine d'autres appelants qui écrivent les mêmes champs (description, short_description)
 * SANS passer par ces trois commandes - dont l'édition administrative
 * (DirectoryAdminController::update()). La garde a donc été déplacée sur
 * Modules/Directory/app/Observers/ToolObserver::saving() - UN point qui ferme TOUS les chemins.
 *
 * Ce test prouve la couverture ÉLARGIE : un chemin d'écriture qui n'était PAS protégé au
 * premier tour (l'édition administrative, exactement comme demandé) ressort désormais
 * normalisé, sans qu'aucun code n'ait été ajouté dans DirectoryAdminController lui-même.
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Directory\Database\Seeders\DirectoryModeratorRoleSeeder::class);
});

function makeAdminGuardTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Admin Guard Test');
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description initiale saine.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé initial sain.');
    $tool->url = 'https://admin-guard-test.example';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

it('normalise la jonction schéma/séparateur écrite via l\'édition administrative (chemin NON protégé au premier tour)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $tool = makeAdminGuardTestTool('admin-guard-test-normalise');

    $casse = "Voir le site officiel (https\u{00A0}://exemple-admin-guard.com) pour en savoir plus.";

    $response = $this->actingAs($admin)->put(route('admin.directory.update', $tool), [
        'name' => 'Outil Admin Guard Test',
        'description' => $casse,
        'short_description' => 'Résumé mis à jour.',
        'url' => $tool->url,
        'pricing' => 'free',
    ]);

    $response->assertStatus(302);

    $fresh = $tool->fresh();
    $description = $fresh->getTranslation('description', app()->getLocale(), false);

    expect($description)->not->toContain("https\u{00A0}://")
        ->and($description)->toContain('https://exemple-admin-guard.com');
});
