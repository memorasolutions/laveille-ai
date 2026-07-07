<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\AI\Models\AiConversation;
use Spatie\Permission\Models\Permission;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

it('manage_ai permission exists after seeding', function () {
    expect(Permission::where('name', 'manage_ai')->exists())->toBeTrue();
});

it('super_admin has manage_ai permission', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    expect($user->can('manage_ai'))->toBeTrue();
});

it('admin has manage_ai permission', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->can('manage_ai'))->toBeTrue();
});

it('editor does not have manage_ai permission', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    expect($user->can('manage_ai'))->toBeFalse();
});

it('conversations index requires authentication', function () {
    $this->get(route('admin.ai.conversations.index'))
        ->assertRedirect(route('login'));
});

it('conversations index requires manage_ai permission', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.ai.conversations.index'))
        ->assertForbidden();
});

it('conversations index accessible to admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    AiConversation::create(['title' => 'Test 1', 'status' => 'ai_active', 'user_id' => $admin->id]);
    AiConversation::create(['title' => 'Test 2', 'status' => 'ai_active', 'user_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.ai.conversations.index'))
        ->assertOk()
        ->assertViewHas('conversations');
});

it('conversations show displays conversation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $conversation = AiConversation::create([
        'title' => 'Test Conversation Title',
        'status' => 'ai_active',
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.ai.conversations.show', $conversation))
        ->assertOk()
        ->assertSee('Test Conversation Title');
});

it('conversations destroy closes conversation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $conversation = AiConversation::create([
        'title' => 'Test Conversation',
        'status' => 'ai_active',
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.ai.conversations.destroy', $conversation))
        ->assertRedirect();

    $this->assertDatabaseHas('ai_conversations', [
        'id' => $conversation->id,
        'status' => 'closed',
    ]);
});

// NOTE (2026-07): le commit 41fc317b (« refactor(nav): restructure sidebar from 14 to 7
// categories ») a fusionné la catégorie « Support IA » dans la catégorie « Outils »
// (IA + Roadmap), restructuration délibérée et vérifiée visuellement (voir message de
// commit). Le libellé « Support IA » n'apparaît donc plus dans le HTML rendu ; on
// vérifie désormais la présence du lien « Conversations » (sous menu.ia, gate view_ai),
// symétrique à l'assertion déjà utilisée par le test « hides AI section for editor »
// ci-dessous.
it('sidebar contains AI section for admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.ai.conversations.index'), false);
});

it('sidebar hides AI section for editor', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.ai.conversations.index'));
});
