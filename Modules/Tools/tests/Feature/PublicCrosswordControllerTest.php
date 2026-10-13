<?php

declare(strict_types=1);

/**
 * @author MEMORA solutions <info@memora.ca>
 * @project laveille.ai
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedCrosswordPreset;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->crosswordOutputBufferLevel = ob_get_level();
});

afterEach(function () {
    // Fermer les tampons laissés ouverts par le rendu sans toucher à ceux de PHPUnit.
    while (ob_get_level() > $this->crosswordOutputBufferLevel) {
        ob_end_clean();
    }
});

it('shows the public share page for a public crossword', function () {
    $user = User::factory()->create();
    $preset = SavedCrosswordPreset::create([
        'user_id' => $user->id,
        'name' => 'Ma grille publique',
        'config_text' => 'Animal félin / CHAT',
        'is_public' => true,
    ]);

    $response = $this->get('/jeumc/'.$preset->public_id);

    $response->assertOk();
    $response->assertSee('Ma grille publique', escape: false);
});

it('redirects to the crossword tool when the crossword is private', function () {
    $user = User::factory()->create();
    $preset = SavedCrosswordPreset::create([
        'user_id' => $user->id,
        'name' => 'Grille privée',
        'config_text' => 'Animal félin / CHAT',
        'is_public' => false,
    ]);

    $response = $this->get('/jeumc/'.$preset->public_id);

    $response->assertRedirect('/outils/mots-croises');
    $response->assertSessionHas('error');
});

it('redirects to the crossword tool when the crossword does not exist', function () {
    $response = $this->get('/jeumc/doesnotexist12');

    $response->assertRedirect('/outils/mots-croises');
    $response->assertSessionHas('error');
});

it('treats a soft-deleted public crossword as absent', function () {
    $user = User::factory()->create();
    $preset = SavedCrosswordPreset::create([
        'user_id' => $user->id,
        'name' => 'Grille supprimée',
        'config_text' => 'Animal félin / CHAT',
        'is_public' => true,
    ]);
    $publicId = $preset->public_id;
    $preset->delete();
    $this->assertSoftDeleted('saved_crossword_presets', ['public_id' => $publicId]);

    $response = $this->get('/jeumc/'.$publicId);

    $response->assertRedirect('/outils/mots-croises');
    $response->assertSessionHas('error');
});

it('permanently redirects the public identifier to the custom slug', function () {
    $user = User::factory()->create();
    $preset = SavedCrosswordPreset::create([
        'user_id' => $user->id,
        'name' => 'Grille avec lien personnalisé',
        'config_text' => 'Animal félin / CHAT',
        'is_public' => true,
        'custom_slug' => 'ma-grille-publique',
    ]);

    $response = $this->get('/jeumc/'.$preset->public_id);

    $response->assertStatus(301);
    $response->assertRedirect('/jeumc/ma-grille-publique');
    $this->get('/jeumc/ma-grille-publique')->assertOk();
});

it('allows the owner to toggle crossword visibility in both directions', function () {
    $user = User::factory()->create();
    $preset = SavedCrosswordPreset::create([
        'user_id' => $user->id,
        'name' => 'Ma grille',
        'config_text' => 'Animal félin / CHAT',
        'is_public' => false,
    ]);

    foreach ([true, false] as $isPublic) {
        $response = $this->actingAs($user)
            ->postJson('/user/mots-croises/'.$preset->public_id.'/toggle-public');

        $response->assertOk();
        $response->assertJson(['success' => true, 'is_public' => $isPublic]);
        $this->assertDatabaseHas('saved_crossword_presets', [
            'public_id' => $preset->public_id,
            'is_public' => $isPublic,
        ]);
    }
});

it('blocks a non-owner from toggling crossword visibility (IDOR)', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $preset = SavedCrosswordPreset::create([
        'user_id' => $userA->id,
        'name' => 'Grille du propriétaire',
        'config_text' => 'Animal félin / CHAT',
        'is_public' => false,
    ]);

    $response = $this->actingAs($userB)
        ->postJson('/user/mots-croises/'.$preset->public_id.'/toggle-public');

    $response->assertNotFound();
    $this->assertDatabaseHas('saved_crossword_presets', [
        'public_id' => $preset->public_id,
        'is_public' => false,
    ]);
});

it('requires authentication to toggle crossword visibility', function () {
    $user = User::factory()->create();
    $preset = SavedCrosswordPreset::create([
        'user_id' => $user->id,
        'name' => 'Grille protégée',
        'config_text' => 'Animal félin / CHAT',
        'is_public' => false,
    ]);

    // Le middleware auth renvoie 401 pour une requête JSON non authentifiée.
    $response = $this->postJson('/user/mots-croises/'.$preset->public_id.'/toggle-public');

    $response->assertUnauthorized();
    $this->assertDatabaseHas('saved_crossword_presets', [
        'public_id' => $preset->public_id,
        'is_public' => false,
    ]);
});
