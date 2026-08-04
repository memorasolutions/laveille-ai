<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Round 1541 (simulation E2E, vague ADMIN, 2026-08-03) : faille RBAC reproduite en direct — le
 * rôle editor (view_admin_panel=true mais aucune permission moderate_tools/approve_tools/
 * reject_tools) pouvait supprimer/modifier n'importe quelle fiche de l'annuaire via
 * admin/directory/*, faute de contrôle de permission plus fin que EnsureIsAdmin sur ce groupe
 * de routes. Fix : Modules/Directory/routes/web.php:89, ajout de 'can:moderate_tools' au
 * middleware du groupe (permission déjà correctement assignée à admin/super_admin/
 * directory_moderator par DirectoryModeratorRoleSeeder, jamais à editor).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Directory\Database\Seeders\DirectoryModeratorRoleSeeder::class);
});

function makeRbacTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Test RBAC '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-direct.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

test('editor role cannot delete directory tool (RBAC regression)', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    $tool = makeRbacTestTool('test-tool-editor-delete');

    $response = $this->actingAs($user)->delete(route('admin.directory.destroy', $tool));

    $response->assertStatus(403);
    expect(Tool::find($tool->id))->not()->toBeNull();
});

test('admin role can delete directory tool', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $tool = makeRbacTestTool('test-tool-admin-delete');

    $response = $this->actingAs($user)->delete(route('admin.directory.destroy', $tool));

    expect($response->status())->toBeIn([200, 302]);
    expect(Tool::find($tool->id))->toBeNull();
});

test('directory_moderator role can access admin directory index', function () {
    $user = User::factory()->create();
    $user->assignRole('directory_moderator');

    $response = $this->actingAs($user)->get(route('admin.directory.index'));

    $response->assertStatus(200);
});

test('editor role cannot access admin directory index', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    $response = $this->actingAs($user)->get(route('admin.directory.index'));

    $response->assertStatus(403);
});
