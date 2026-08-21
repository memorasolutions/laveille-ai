<?php
declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;
uses(Tests\TestCase::class, RefreshDatabase::class);

// Phase 1 permalien public + remix (2026-08-05) - couverture calquée sur SavedPromptControllerTest.php
// (mêmes conventions : actingAs par méthode, assertSame jamais assertEquals via expect(), scope strict
// public_id/is_public jamais user_id sur ce contrôleur - il DOIT rester consultable sans authentification).

function makePublicPromptTool(): void
{
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
}

it('shows the public share page for a public prompt', function () {
    makePublicPromptTool();
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Mon prompt public',
        'prompt_text' => 'Rédige un plan marketing.',
        'params' => ['verb' => 'rédiger'],
        'is_public' => true,
    ]);

    $response = $this->get('/p/'.$prompt->public_id);

    $response->assertOk();
    $response->assertSee('Mon prompt public', escape: false);
    $response->assertSee('Rédige un plan marketing.', escape: false);
});

it('redirects to the constructeur when the prompt does not exist', function () {
    $response = $this->get('/p/doesnotexist12');

    // ?share_error=notfound (2026-08-05) : la cible passe par cacheResponse:600 (Spatie
    // ResponseCache) - le flash de session ci-dessous est conservé mais ne s'affiche pas de
    // façon fiable sur une réponse mise en cache ; le paramètre de requête, lu côté client
    // (constructeur-prompts-core.js), est le mécanisme réellement fiable ici.
    $response->assertRedirect('/outils/constructeur-prompts?share_error=notfound');
    $response->assertSessionHas('error');
});

it('redirects to the constructeur when the prompt exists but is private', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt privé',
        'prompt_text' => 'Texte privé',
        'is_public' => false,
    ]);

    $response = $this->get('/p/'.$prompt->public_id);

    // ?share_error=notfound (2026-08-05) : la cible passe par cacheResponse:600 (Spatie
    // ResponseCache) - le flash de session ci-dessous est conservé mais ne s'affiche pas de
    // façon fiable sur une réponse mise en cache ; le paramètre de requête, lu côté client
    // (constructeur-prompts-core.js), est le mécanisme réellement fiable ici.
    $response->assertRedirect('/outils/constructeur-prompts?share_error=notfound');
    $response->assertSessionHas('error');
});

it('returns remix data for a public prompt without any authentication', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt remixable',
        'prompt_text' => 'Texte à remixer',
        'params' => ['verb' => 'rédiger', 'taskObject' => 'un plan'],
        'is_public' => true,
    ]);

    $response = $this->getJson('/p/'.$prompt->public_id.'/remix-data');

    $response->assertOk();
    $response->assertJson([
        'public_id' => $prompt->public_id,
        'name' => 'Prompt remixable',
        'prompt_text' => 'Texte à remixer',
    ]);
    $response->assertJsonPath('params.verb', 'rédiger');
});

// Test IDOR explicite demandé par le plan : remixData() ne doit JAMAIS renvoyer un prompt privé,
// quel que soit l'appelant (invité ici, mais le scope ne dépend d'aucun user_id - un utilisateur
// connecté quelconque obtiendrait exactement le même 404).
it('never returns remix data for a private prompt (IDOR)', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt privé',
        'prompt_text' => 'Texte privé, ne doit jamais fuiter',
        'is_public' => false,
    ]);

    $response = $this->getJson('/p/'.$prompt->public_id.'/remix-data');

    $response->assertNotFound();
    $response->assertJsonMissing(['prompt_text' => 'Texte privé, ne doit jamais fuiter']);
});

it('returns 404 for remix data on a public_id that does not exist', function () {
    $response = $this->getJson('/p/doesnotexist12/remix-data');

    $response->assertNotFound();
});

// Garde-fou durci (SPEC-BRIQUE1-GABARITS.md point 5) : test de non-régression manquant jusqu'ici.
// SavedPrompt utilise SoftDeletes - le scope global exclut déjà les lignes supprimées de TOUTE
// requête Eloquent standard (SavedPrompt::where(...)->first() dans show()/remixData()), donc la
// suppression logique doit déjà purger l'accès public sans code additionnel. Ce test le PROUVE.
it('treats a soft-deleted prompt as gone: /p/{id} redirects and remix-data 404s', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt supprimé',
        'prompt_text' => 'Texte supprimé',
        'is_public' => true,
    ]);
    $publicId = $prompt->public_id;
    $prompt->delete();

    $showResponse = $this->get('/p/'.$publicId);
    $showResponse->assertRedirect('/outils/constructeur-prompts?share_error=notfound');

    $remixResponse = $this->getJson('/p/'.$publicId.'/remix-data');
    $remixResponse->assertNotFound();
});

it('purges access as soon as is_public is turned off, even without deleting the prompt', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt rendu privé',
        'prompt_text' => 'Texte',
        'is_public' => true,
    ]);
    $publicId = $prompt->public_id;
    $prompt->update(['is_public' => false]);

    $showResponse = $this->get('/p/'.$publicId);
    $showResponse->assertRedirect('/outils/constructeur-prompts?share_error=notfound');

    $remixResponse = $this->getJson('/p/'.$publicId.'/remix-data');
    $remixResponse->assertNotFound();
});

// Non-régression explicite demandée par le plan : le toggle is_public existant (PUT
// /api/prompts/{id}, SavedPromptController::update) reste fonctionnel pour le propriétaire et
// bloqué pour un tiers - aucun nouvel endpoint de bascule n'a été introduit par cette phase.
it('still allows the owner to toggle is_public via the existing PUT endpoint', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Mon prompt',
        'prompt_text' => 'Texte',
        'is_public' => false,
    ]);

    $response = $this->actingAs($user)->putJson('/api/prompts/'.$prompt->public_id, [
        'is_public' => true,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('saved_prompts', [
        'public_id' => $prompt->public_id,
        'is_public' => true,
    ]);
});

it('still blocks a non-owner from toggling is_public via the existing PUT endpoint (IDOR)', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $userA->id,
        'name' => 'Prompt A',
        'prompt_text' => 'Texte A',
        'is_public' => false,
    ]);

    $response = $this->actingAs($userB)->putJson('/api/prompts/'.$prompt->public_id, [
        'is_public' => true,
    ]);

    $response->assertNotFound();
    $this->assertDatabaseHas('saved_prompts', [
        'public_id' => $prompt->public_id,
        'is_public' => false,
    ]);
});
