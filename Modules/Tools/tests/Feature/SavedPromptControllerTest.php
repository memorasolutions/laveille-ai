<?php
declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
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
