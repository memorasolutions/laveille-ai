<?php
declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;
uses(Tests\TestCase::class, RefreshDatabase::class);

it('blocks guests from accessing saved prompts index', function () {
    $this->getJson('/api/prompts')->assertUnauthorized();
});

it('blocks guests from storing a saved prompt', function () {
    $this->postJson('/api/prompts', [
        'name' => 'Test Prompt',
        'prompt_text' => 'Hello world',
    ])->assertUnauthorized();
});

it('blocks guests from updating a saved prompt', function () {
    $publicId = 'abc123def456';
    $this->putJson("/api/prompts/{$publicId}", [
        'name' => 'Updated Prompt',
    ])->assertUnauthorized();
});

it('blocks guests from deleting a saved prompt', function () {
    $publicId = 'abc123def456';
    $this->deleteJson("/api/prompts/{$publicId}")->assertUnauthorized();
});

it('prevents IDOR on update: user B cannot update user A\'s prompt', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt A',
        'prompt_text' => 'Text A',
    ]);

    $originalName = $prompt->name;

    $response = $this->actingAs($userB)->putJson("/api/prompts/{$prompt->public_id}", [
        'name' => 'Hacked Name',
    ]);

    $response->assertNotFound();

    $prompt->refresh();
    expect($prompt->name)->toBe($originalName);
});

it('shows a single prompt by public_id (fix round 5, 2026-07-26: le flux ?edit= ne peut plus rater un prompt au-delà de la page 1 de la bibliothèque)', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Mon prompt',
        'prompt_text' => 'Texte',
        'params' => ['verb' => 'rédiger'],
    ]);

    $response = $this->actingAs($user)->getJson("/api/prompts/{$prompt->public_id}");

    $response->assertOk();
    $response->assertJson(['public_id' => $prompt->public_id, 'name' => 'Mon prompt']);
});

it('prevents IDOR on show: user B cannot fetch user A\'s prompt by public_id', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt A',
        'prompt_text' => 'Text A',
    ]);

    $response = $this->actingAs($userB)->getJson("/api/prompts/{$prompt->public_id}");

    $response->assertNotFound();
});

it('prevents IDOR on destroy: user B cannot delete user A\'s prompt', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt A',
        'prompt_text' => 'Text A',
    ]);

    $publicId = $prompt->public_id;

    $response = $this->actingAs($userB)->deleteJson("/api/prompts/{$publicId}");

    $response->assertNotFound();

    $this->assertDatabaseHas('saved_prompts', [
        'public_id' => $publicId,
        'user_id' => $userA->id,
        'deleted_at' => null,
    ]);
});

// Round 71 (2026-07-27) : passe adversariale - duplicate() n'avait AUCUNE couverture de test
// (ni happy-path, ni guest-block, ni IDOR), contrairement à tous les autres endpoints mutants
// du contrôleur. Relecture manuelle : le code était déjà correctement scopé (public_id + user_id),
// donc pas exploitable en l'état - mais un endpoint mutant vivant sans filet de régression.
it('blocks guests from duplicating a saved prompt', function () {
    $publicId = 'abc123def456';
    $this->postJson("/api/prompts/{$publicId}/duplicate")->assertUnauthorized();
});

it('prevents IDOR on duplicate: user B cannot duplicate user A\'s prompt', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt A',
        'prompt_text' => 'Text A',
    ]);

    $response = $this->actingAs($userB)->postJson("/api/prompts/{$prompt->public_id}/duplicate");

    $response->assertNotFound();
    expect(SavedPrompt::where('user_id', $userB->id)->count())->toBe(0);
});

it('duplicates a prompt with a "Copie de" prefix, never public, never favorite regardless of the original', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Mon prompt',
        'prompt_text' => 'Texte du prompt',
        'params' => ['verb' => 'rédiger'],
        'tags' => ['seo', 'blog'],
        'is_public' => true,
        'is_favorite' => true,
    ]);

    $response = $this->actingAs($user)->postJson("/api/prompts/{$prompt->public_id}/duplicate");

    $response->assertCreated();
    $response->assertJson([
        'name' => 'Copie de Mon prompt',
        'prompt_text' => 'Texte du prompt',
        'tags' => ['seo', 'blog'],
        'is_public' => false,
        'is_favorite' => false,
    ]);
    expect($response->json('public_id'))->not->toBe($prompt->public_id);
    expect(SavedPrompt::where('user_id', $user->id)->count())->toBe(2);
});

it('prevents IDOR on index: user B cannot see user A\'s prompts', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $promptA = SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt A',
        'prompt_text' => 'Text A',
    ]);

    $response = $this->actingAs($userB)->getJson('/api/prompts');

    $response->assertOk();

    $data = $response->json('data');
    $publicIds = array_column($data, 'public_id');
    expect($publicIds)->not->toContain($promptA->public_id);
});

it('validates name is required on store', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'prompt_text' => 'Some text',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['name']);
});

it('validates prompt_text is required on store', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test Prompt',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['prompt_text']);
});

it('validates name max length on store', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => str_repeat('a', 256),
        'prompt_text' => 'Some text',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['name']);
});

it('validates params must be an array if provided on store', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test Prompt',
        'prompt_text' => 'Some text',
        'params' => 'not an array',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['params']);
});

it('rejects an array value for name on store (passe adversariale round 4, 2026-07-26)', function () {
    // Sans la règle 'string', Laravel interprète max:255 sur un tableau comme "au plus 255
    // éléments" au lieu d'une longueur de chaîne, laissant passer un tableau jusqu'à l'insertion SQL.
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => array_fill(0, 5, 'x'),
        'prompt_text' => 'Some text',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['name']);
});

it('rejects an array value for prompt_text on store (passe adversariale round 4, 2026-07-26)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test Prompt',
        'prompt_text' => ['a', 'b'],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['prompt_text']);
});

it('rejects a prompt_text exceeding the max length on store (passe adversariale round 4, 2026-07-26)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test Prompt',
        'prompt_text' => str_repeat('a', 20001),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['prompt_text']);
});

it('rejects an oversized params payload on store (round 34, 2026-07-27: params n\'avait aucune borne de taille contrairement à prompt_text/tags - trouvé par la passe adversariale)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test Prompt',
        'prompt_text' => 'Test',
        'params' => ['blob' => str_repeat('a', 15000)],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['params']);
    $this->assertDatabaseCount('saved_prompts', 0);
});

it('rejects an oversized params payload on update (round 34, 2026-07-27)', function () {
    $user = User::factory()->create();
    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Original',
        'prompt_text' => 'Original Text',
        'params' => ['verb' => 'rédiger'],
    ]);

    $response = $this->actingAs($user)->putJson("/api/prompts/{$prompt->public_id}", [
        'params' => ['blob' => str_repeat('a', 15000)],
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['params']);
    expect($prompt->fresh()->params)->toBe(['verb' => 'rédiger']);
});

it('accepts a small realistic params payload on store (round 34, 2026-07-27: non-régression)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test Prompt',
        'prompt_text' => 'Test',
        'params' => ['verb' => 'rédiger', 'temperature' => 0.7],
    ]);

    $response->assertCreated();
    $response->assertJsonPath('params.verb', 'rédiger');
});

it('returns 404 (not silently no-op) when a PUT targets the internal numeric id instead of public_id', function () {
    // Régression round 4 (2026-07-26) : le JS assignait par erreur found.id (clé interne) à
    // _editingId au lieu de found.public_id, provoquant ce même 404 côté client - avalé
    // silencieusement faute de vérifier r.ok, avec un faux toast de succès affiché quand même.
    // Ce test documente le contrat serveur correct (404 explicite) que le JS corrigé respecte désormais.
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Original',
        'prompt_text' => 'Original Text',
    ]);

    $response = $this->actingAs($user)->putJson("/api/prompts/{$prompt->id}", [
        'name' => 'Should Not Save',
        'prompt_text' => 'Should Not Save',
    ]);

    $response->assertNotFound();
    $this->assertDatabaseHas('saved_prompts', ['id' => $prompt->id, 'name' => 'Original']);
});

it('stores a prompt correctly and ignores user_id in payload', function () {
    $user = User::factory()->create();
    $attackerId = User::factory()->create()->id;

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'My Prompt',
        'prompt_text' => 'Hello world',
        'params' => ['temperature' => 0.7],
        'is_public' => true,
        'user_id' => $attackerId, // doit être ignoré : user_id ne fait pas partie des règles validate()
    ]);

    $response->assertStatus(201);

    $publicId = $response->json('public_id');
    $this->assertDatabaseHas('saved_prompts', [
        'public_id' => $publicId,
        'user_id' => $user->id,
        'name' => 'My Prompt',
        'prompt_text' => 'Hello world',
        'is_public' => true,
    ]);

    $params = $response->json('params');
    expect($params)->toBe(['temperature' => 0.7]);
});

it('deduplicates tags case-insensitively on store (round 10, 2026-07-26)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Prompt avec tags dupliqués',
        'prompt_text' => 'Texte',
        'tags' => ['Marketing', 'marketing', 'MARKETING', 'Vente'],
    ]);

    $response->assertStatus(201);
    expect($response->json('tags'))->toBe(['Marketing', 'Vente']);
});

it('updates a prompt correctly when owned by the user', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Original Name',
        'prompt_text' => 'Original Text',
        'params' => ['temp' => 0.5],
        'is_public' => false,
    ]);

    $response = $this->actingAs($user)->putJson("/api/prompts/{$prompt->public_id}", [
        'name' => 'Updated Name',
        'prompt_text' => 'Updated Text',
        'params' => ['temp' => 0.9],
        'is_public' => true,
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('saved_prompts', [
        'public_id' => $prompt->public_id,
        'user_id' => $user->id,
        'name' => 'Updated Name',
        'prompt_text' => 'Updated Text',
        'is_public' => true,
    ]);
});

it('soft deletes a prompt on destroy and removes it from index', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'To Delete',
        'prompt_text' => 'Delete me',
    ]);

    $publicId = $prompt->public_id;

    $response = $this->actingAs($user)->deleteJson("/api/prompts/{$publicId}");

    $response->assertNoContent();

    $this->assertSoftDeleted('saved_prompts', [
        'public_id' => $publicId,
        'user_id' => $user->id,
    ]);

    $indexResponse = $this->actingAs($user)->getJson('/api/prompts');
    $indexResponse->assertOk();

    $data = $indexResponse->json('data');
    $publicIds = array_column($data, 'public_id');
    expect($publicIds)->not->toContain($publicId);
});

it('renders the trans_choice() prompt counter as real localized HTML for counts 0/1/2+ (round 27, 2026-07-27: aucun test n\'exerçait le rendu HTTP réel de ce compteur - une désynchronisation Blade/lang aurait cassé la traduction sans faire échouer la suite)', function () {
    Tool::query()->updateOrInsert(
        ['slug' => 'constructeur-prompts'],
        [
            'name' => 'Constructeur de prompts',
            'description' => 'Test',
            'icon' => '✨',
            'is_active' => true,
            'is_under_construction' => false,
            'category' => 'productivite',
        ]
    );

    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['locale' => 'fr'])
        ->get('/user/prompts')
        ->assertOk()
        ->assertSee('Aucun prompt sauvegardé.', escape: false);

    $this->actingAs($user)->withSession(['locale' => 'en'])
        ->get('/user/prompts')
        ->assertOk()
        ->assertSee('No saved prompt.', escape: false);

    SavedPrompt::create(['user_id' => $user->id, 'name' => 'P1', 'prompt_text' => 'Texte 1']);

    $this->actingAs($user)->withSession(['locale' => 'fr'])
        ->get('/user/prompts')
        ->assertSee('1 prompt sauvegardé.', escape: false);

    $this->actingAs($user)->withSession(['locale' => 'en'])
        ->get('/user/prompts')
        ->assertSee('1 saved prompt.', escape: false);

    SavedPrompt::create(['user_id' => $user->id, 'name' => 'P2', 'prompt_text' => 'Texte 2']);

    $this->actingAs($user)->withSession(['locale' => 'fr'])
        ->get('/user/prompts')
        ->assertSee('2 prompts sauvegardés.', escape: false);

    $this->actingAs($user)->withSession(['locale' => 'en'])
        ->get('/user/prompts')
        ->assertSee('2 saved prompts.', escape: false);
});
