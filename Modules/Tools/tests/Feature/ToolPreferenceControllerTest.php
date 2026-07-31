<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('blocks guests from tool-preferences endpoints', function () {
    $this->get('/api/tool-preferences/minuteur-visuel')->assertUnauthorized();
    $this->postJson('/api/tool-preferences/minuteur-visuel', ['key' => 'custom_colors', 'value' => ['#991B1B']])
        ->assertUnauthorized();
});

it('returns an empty array when the authenticated user has no saved preferences yet', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');

    $response->assertOk();
    $response->assertJson(['preferences' => []]);
});

it('saves and retrieves recent custom colors for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'custom_colors',
        'value' => ['#ABCDEF', '#123456'],
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['custom_colors' => ['#ABCDEF', '#123456']]]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');
    $show->assertOk();
    $show->assertJson(['preferences' => ['custom_colors' => ['#ABCDEF', '#123456']]]);
});

it('caps custom_colors at 5 entries and filters invalid hex values', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'custom_colors',
        'value' => ['#111111', 'not-a-color', '#222222', '#333333', '#444444', '#555555', '#666666'],
    ]);

    $response->assertOk();
    $colors = $response->json('preferences.custom_colors');
    expect($colors)->toHaveCount(5);
    expect($colors)->not->toContain('not-a-color');
});

it('saves and retrieves custom_durations for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'custom_durations',
        'value' => [32, 90],
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['custom_durations' => [32, 90]]]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');
    $show->assertJson(['preferences' => ['custom_durations' => [32, 90]]]);
});

it('caps custom_durations at 2 entries and filters out-of-range values', function () {
    $user = User::factory()->create();

    // #843-846 : bornes en SECONDES désormais (1 à 10859, soit 180 min 59 s) - 999 était
    // hors-plage quand la borne était en minutes (180), mais reste valide en secondes.
    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'custom_durations',
        'value' => [32, 0, 99999, 90, 45],
    ]);

    $response->assertOk();
    $durations = $response->json('preferences.custom_durations');
    expect($durations)->toHaveCount(2);
    expect($durations)->not->toContain(0);
    expect($durations)->not->toContain(99999);
});

it('saves and retrieves favorite_colors for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'favorite_colors',
        'value' => ['#E8A9AE', '#DCC3A0'],
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['favorite_colors' => ['#E8A9AE', '#DCC3A0']]]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');
    $show->assertJson(['preferences' => ['favorite_colors' => ['#E8A9AE', '#DCC3A0']]]);
});

it('caps favorite_colors at 2 entries and filters invalid hex values', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'favorite_colors',
        'value' => ['#111111', 'not-a-color', '#222222', '#333333'],
    ]);

    $response->assertOk();
    $colors = $response->json('preferences.favorite_colors');
    expect($colors)->toHaveCount(2);
    expect($colors)->not->toContain('not-a-color');
});

it('saves and retrieves traffic_thresholds for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'traffic_thresholds',
        'value' => ['green' => 70, 'yellow' => 40],
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['traffic_thresholds' => ['green' => 70, 'yellow' => 40]]]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');
    $show->assertJson(['preferences' => ['traffic_thresholds' => ['green' => 70, 'yellow' => 40]]]);
});

it('rejects invalid traffic_thresholds (yellow must be less than green)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'traffic_thresholds',
        'value' => ['green' => 20, 'yellow' => 50],
    ]);

    $response->assertStatus(422);
});

it('rejects traffic_thresholds missing required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'traffic_thresholds',
        'value' => ['green' => 70],
    ]);

    $response->assertStatus(422);
});

it('saves and retrieves default_color for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'default_color',
        'value' => '#6B21A8',
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['default_color' => '#6B21A8']]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/minuteur-visuel');
    $show->assertJson(['preferences' => ['default_color' => '#6B21A8']]);
});

it('rejects an invalid default_color value', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'default_color',
        'value' => 'not-a-color',
    ]);

    $response->assertStatus(422);
});

it('rejects an unknown preference key format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/minuteur-visuel', [
        'key' => 'Invalid Key!',
        'value' => ['#111111'],
    ]);

    $response->assertStatus(422);
});

it('saves and retrieves custom_cards for the authenticated user', function () {
    $user = User::factory()->create();

    $store = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => [
            ['id' => 'custom_abc123', 'title' => 'Corriger un texte', 'icon' => '🩹', 'query_template' => 'Corrige les fautes de ce texte', 'hidden' => false],
        ],
    ]);
    $store->assertOk();
    $store->assertJson(['preferences' => ['custom_cards' => [
        ['id' => 'custom_abc123', 'title' => 'Corriger un texte', 'icon' => '🩹', 'query_template' => 'Corrige les fautes de ce texte', 'hidden' => false],
    ]]]);

    $show = $this->actingAs($user)->get('/api/tool-preferences/constructeur-prompts');
    $show->assertJson(['preferences' => ['custom_cards' => [
        ['id' => 'custom_abc123', 'title' => 'Corriger un texte', 'icon' => '🩹', 'query_template' => 'Corrige les fautes de ce texte', 'hidden' => false],
    ]]]);
});

it('caps custom_cards at 10 entries and preserves order', function () {
    $user = User::factory()->create();

    $value = [];
    for ($i = 1; $i <= 13; $i++) {
        $value[] = ['title' => 'Carte '.$i, 'icon' => '⭐', 'query_template' => ''];
    }

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => $value,
    ]);

    $response->assertOk();
    $cards = $response->json('preferences.custom_cards');
    expect($cards)->toHaveCount(10);
    expect($cards[0]['title'])->toBe('Carte 1');
    expect($cards[9]['title'])->toBe('Carte 10');
});

it('filters out custom_cards entries without a title instead of rejecting the whole request', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => [
            ['title' => '', 'icon' => '⭐', 'query_template' => 'sans titre'],
            ['title' => '  ', 'icon' => '⭐', 'query_template' => 'espaces seulement'],
            ['title' => 'Carte valide', 'icon' => '💡', 'query_template' => 'Un gabarit'],
            'pas un tableau',
        ],
    ]);

    $response->assertOk();
    $cards = $response->json('preferences.custom_cards');
    expect($cards)->toHaveCount(1);
    expect($cards[0]['title'])->toBe('Carte valide');
});

it('generates a safe id and default icon for custom_cards when missing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => [
            ['title' => 'Ma carte', 'id' => 'ID invalide !!', 'icon' => '', 'query_template' => ''],
        ],
    ]);

    $response->assertOk();
    $card = $response->json('preferences.custom_cards.0');
    expect($card['id'])->toMatch('/^custom_[A-Za-z0-9]{10}$/');
    expect($card['icon'])->toBe('⭐');
    expect($card['hidden'])->toBeFalse();
});

it('truncates long custom_cards title and query_template rather than rejecting', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => [
            ['title' => str_repeat('a', 200), 'query_template' => str_repeat('b', 900)],
        ],
    ]);

    $response->assertOk();
    $card = $response->json('preferences.custom_cards.0');
    expect(strlen($card['title']))->toBe(60);
    expect(strlen($card['query_template']))->toBe(500);
});

it('rejects a non-array value for custom_cards', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => 'not-an-array',
    ]);

    $response->assertStatus(422);
});

it('accepts an empty array for custom_cards (deleting the last remaining card)', function () {
    // Régression : 'value' => ['required'] rejetait un tableau vide (422), empêchant la
    // suppression de la dernière carte de persister côté serveur - corrigé en 'present'.
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'custom_cards',
        'value' => [],
    ]);

    $response->assertOk();
    $response->assertJson(['preferences' => ['custom_cards' => []]]);
});

it('does not lose a concurrent write to another key made by another request during the same auth session (round 40, 2026-07-27: update() lisait auth()->user()->tool_preferences - une propriété déjà chargée/mise en cache par le middleware d\'authentification au début de la requête - au lieu de relire la ligne fraîche en base ; une 2e requête ayant écrit une AUTRE clé entre-temps (ex. un autre onglet, un autre outil partageant la même colonne JSON) voyait sa mutation silencieusement écrasée dès que la 1re requête terminait son propre update(). Corrigé via un verrou de ligne + relecture fraîche dans une transaction.)', function () {
    $user = User::factory()->create([
        'tool_preferences' => ['constructeur-prompts' => ['existing_key' => 'from-before']],
    ]);

    // Simule une 2e requête concurrente (autre onglet/page/outil) qui a déjà écrit sa propre clé
    // en base APRÈS que $user ait été chargé/mis en cache par le middleware d'authentification de
    // CETTE requête-ci (actingAs() fige l'instance utilisée par auth()->user() pour tout l'appel).
    DB::table('users')->where('id', $user->id)->update([
        'tool_preferences' => json_encode([
            'constructeur-prompts' => ['existing_key' => 'from-before', 'concurrent_key' => 'from-another-request'],
        ]),
    ]);

    $response = $this->actingAs($user)->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'prompt_profile',
        'value' => ['profile_role' => 'Enseignant'],
    ]);

    $response->assertOk();
    // Les 3 clés doivent coexister : celle d'avant, celle écrite "concurremment" par l'autre
    // requête, ET celle de cette requête-ci - aucune ne doit être écrasée par une lecture périmée.
    $response->assertJson(['preferences' => [
        'existing_key' => 'from-before',
        'concurrent_key' => 'from-another-request',
        'prompt_profile' => ['profile_role' => 'Enseignant'],
    ]]);
});
