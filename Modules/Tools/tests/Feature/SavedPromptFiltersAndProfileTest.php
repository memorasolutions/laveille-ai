<?php
declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Tests\TestCase;
uses(Tests\TestCase::class, RefreshDatabase::class);

test('can filter prompts by search term in name or prompt_text', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Rédiger un courriel',
        'prompt_text' => 'Bonjour client',
        'public_id' => 'abc123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => [],
    ]);

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Plan de cours',
        'prompt_text' => 'Objectifs pédagogiques',
        'public_id' => 'def456',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => [],
    ]);

    $response = $this->getJson('/api/prompts?search=courriel');
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('Rédiger un courriel');
});

test('can filter prompts by tag', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Email marketing',
        'prompt_text' => 'Écrire un email',
        'public_id' => 'abc123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => ['marketing', 'email'],
    ]);

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Cours de maths',
        'prompt_text' => 'Créer un plan',
        'public_id' => 'def456',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => ['pedagogie'],
    ]);

    $response = $this->getJson('/api/prompts?tag=marketing');
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('Email marketing');

    $response = $this->getJson('/api/prompts?tag=inexistant');
    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});

test('can filter prompts by favorite status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Favori',
        'prompt_text' => 'Texte favori',
        'public_id' => 'abc123',
        'is_favorite' => true,
        'is_public' => false,
        'tags' => [],
    ]);

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Non favori',
        'prompt_text' => 'Texte normal',
        'public_id' => 'def456',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => [],
    ]);

    $response = $this->getJson('/api/prompts?favorite=1');
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('Favori');
});

test('available_tags includes all user tags regardless of active filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Email',
        'prompt_text' => 'Écrire un email',
        'public_id' => 'abc123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => ['marketing', 'email'],
    ]);

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Cours',
        'prompt_text' => 'Créer un plan',
        'public_id' => 'def456',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => ['pedagogie'],
    ]);

    $response = $this->getJson('/api/prompts?tag=marketing');
    $response->assertOk();

    $availableTags = $response->json('available_tags');
    expect($availableTags)->toBe(['email', 'marketing', 'pedagogie']);
});

test('available_tags does not leak tags from other users', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt A',
        'prompt_text' => 'Texte A',
        'public_id' => 'abc123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => ['tagA'],
    ]);

    $this->actingAs($userB);
    $response = $this->getJson('/api/prompts');
    $response->assertOk();

    $availableTags = $response->json('available_tags');
    expect($availableTags)->toBeEmpty();
});

test('available_tags does not silently drop a literal "0" tag (round 44, 2026-07-27: filter() sans callback appliquait la sémantique array_filter par défaut, qui retire aussi "0" - un tag littéral pourtant valide et pleinement filtrable via ?tag=0, mais absent des chips affichées à l\'utilisateur)', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt zéro',
        'prompt_text' => 'Texte',
        'public_id' => 'abc123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => ['0', 'marketing'],
    ]);

    $response = $this->getJson('/api/prompts');
    $response->assertOk();

    $availableTags = $response->json('available_tags');
    expect($availableTags)->toContain('0');
    expect($availableTags)->toContain('marketing');

    // Le tag "0" doit aussi rester réellement filtrable (déjà correct avant round 44, non-régression).
    $filtered = $this->getJson('/api/prompts?tag=0');
    $filtered->assertOk();
    expect($filtered->json('data'))->toHaveCount(1);
});

test('post prompts validates tags array size and content', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Trop de tags
    $response = $this->postJson('/api/prompts', [
        'name' => 'Test',
        'prompt_text' => 'Texte',
        'tags' => array_fill(0, 6, 'tag'),
        'is_favorite' => false,
        'is_public' => false,
    ]);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['tags']);

    // Tag trop long
    $response = $this->postJson('/api/prompts', [
        'name' => 'Test',
        'prompt_text' => 'Texte',
        'tags' => [str_repeat('a', 31)],
        'is_favorite' => false,
        'is_public' => false,
    ]);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['tags.0']);

    // Valide
    $response = $this->postJson('/api/prompts', [
        'name' => 'Test',
        'prompt_text' => 'Texte',
        'tags' => ['seo', 'ia'],
        'is_favorite' => true,
        'is_public' => false,
    ]);
    $response->assertCreated();
    expect($response->json('tags'))->toBe(['seo', 'ia']);
    expect($response->json('is_favorite'))->toBeTrue();
});

test('can update is_favorite without changing other fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Original',
        'prompt_text' => 'Texte original',
        'public_id' => 'abc123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => ['tag'],
    ]);

    $response = $this->putJson("/api/prompts/{$prompt->public_id}", [
        'is_favorite' => true,
    ]);
    $response->assertOk();

    $prompt->refresh();
    expect($prompt->is_favorite)->toBeTrue();
    expect($prompt->name)->toBe('Original');
    expect($prompt->prompt_text)->toBe('Texte original');
    expect($prompt->tags)->toBe(['tag']);
});

test('cannot update favorite status of another user prompt', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt A',
        'prompt_text' => 'Texte A',
        'public_id' => 'abc123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => [],
    ]);

    $this->actingAs($userB);
    $response = $this->putJson("/api/prompts/{$prompt->public_id}", [
        'is_favorite' => true,
    ]);
    $response->assertNotFound();

    $prompt->refresh();
    expect($prompt->is_favorite)->toBeFalse();
});

test('can duplicate prompt with correct attributes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $original = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Original',
        'prompt_text' => 'Texte original',
        'public_id' => 'abc123',
        'is_favorite' => true,
        'is_public' => true,
        'tags' => ['tag1', 'tag2'],
    ]);

    $response = $this->postJson("/api/prompts/{$original->public_id}/duplicate");
    $response->assertCreated();

    $duplicate = SavedPrompt::where('public_id', $response->json('public_id'))->first();
    expect($duplicate)->not->toBeNull();
    expect($duplicate->user_id)->toBe($user->id);
    expect($duplicate->name)->toBe('Copie de Original');
    expect($duplicate->prompt_text)->toBe('Texte original');
    expect($duplicate->tags)->toBe(['tag1', 'tag2']);
    expect($duplicate->is_public)->toBeFalse();
    expect($duplicate->is_favorite)->toBeFalse();
    expect($duplicate->public_id)->not->toBe($original->public_id);
});

test('cannot duplicate another user prompt', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt A',
        'prompt_text' => 'Texte A',
        'public_id' => 'abc123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => [],
    ]);

    $initialCount = SavedPrompt::count();

    $this->actingAs($userB);
    $response = $this->postJson("/api/prompts/{$prompt->public_id}/duplicate");
    $response->assertNotFound();

    expect(SavedPrompt::count())->toBe($initialCount);
});

test('guest cannot duplicate prompt', function () {
    $user = User::factory()->create();
    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt',
        'prompt_text' => 'Texte',
        'public_id' => 'abc123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => [],
    ]);

    $response = $this->postJson("/api/prompts/{$prompt->public_id}/duplicate");
    $response->assertUnauthorized();
});

test('can save and retrieve prompt profile preferences', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $profileData = [
        'profile_role' => 'Enseignant',
        'profile_style' => 'Direct',
        'profile_constraints' => 'Toujours vouvoyer',
    ];

    $response = $this->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'prompt_profile',
        'value' => $profileData,
    ]);
    $response->assertOk();

    $response = $this->getJson('/api/tool-preferences/constructeur-prompts');
    $response->assertOk();

    $preferences = $response->json('preferences');
    expect($preferences['prompt_profile'])->toBe($profileData);
});

test('prompt profile truncates long role field instead of rejecting', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $longRole = str_repeat('a', 200);
    $response = $this->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'prompt_profile',
        'value' => [
            'profile_role' => $longRole,
            'profile_style' => 'Direct',
            'profile_constraints' => 'Vouvoiement',
        ],
    ]);
    $response->assertOk();

    $response = $this->getJson('/api/tool-preferences/constructeur-prompts');
    $storedRole = $response->json('preferences.prompt_profile.profile_role');
    expect(strlen($storedRole))->toBeLessThanOrEqual(80);
});

test('prompt profile rejects non-string fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'prompt_profile',
        'value' => [
            'profile_role' => ['tableau', 'pas', 'une', 'chaine'],
            'profile_style' => 'Direct',
            'profile_constraints' => 'Vouvoiement',
        ],
    ]);
    $response->assertUnprocessable();
    // La sanitisation lève une ValidationException sur la clé 'value' globale
    // (pas une règle Laravel imbriquée par champ) : voir
    // ToolPreferenceController::sanitizePromptProfile().
    $response->assertJsonValidationErrors(['value']);
});

test('prompt profile preferences are user-isolated', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA);
    $this->postJson('/api/tool-preferences/constructeur-prompts', [
        'key' => 'prompt_profile',
        'value' => [
            'profile_role' => 'Enseignant A',
            'profile_style' => 'Direct',
            'profile_constraints' => 'Vouvoiement',
        ],
    ]);

    $this->actingAs($userB);
    $response = $this->getJson('/api/tool-preferences/constructeur-prompts');
    $preferences = $response->json('preferences');
    expect($preferences['prompt_profile'] ?? null)->toBeNull();
});
