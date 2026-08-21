<?php
declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Database\Seeders\OfficialPromptTemplatesSeeder;
use Modules\Tools\Models\SavedPrompt;
use Tests\TestCase;
uses(Tests\TestCase::class, RefreshDatabase::class);

// Bibliothèque de gabarits curés (2026-08-20, Brique 1) - couverture demandée par
// docs/specs/SPEC-BRIQUE1-GABARITS.md section « Tests ». Conventions calquées sur
// SavedPromptControllerTest.php / PublicPromptControllerTest.php (mêmes helpers actingAs,
// assertions, style).

it('defaults is_official to false on a normal saved prompt', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt ordinaire',
        'prompt_text' => 'Texte',
    ]);

    expect($prompt->fresh()->is_official)->toBeFalse();
});

it('scopeOfficial only returns official prompts', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt ordinaire',
        'prompt_text' => 'Texte',
        'is_official' => false,
    ]);
    $official = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Gabarit officiel',
        'prompt_text' => 'Texte officiel',
        'is_official' => true,
        'is_public' => true,
    ]);

    $results = SavedPrompt::official()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($official->id);
});

// Sécurité (spec point 2) : is_official est listé dans $fillable (voir commentaire du modèle),
// mais SavedPromptController::store() ne renvoie via $request->validate() QUE les clés déclarées
// dans ses règles - 'is_official' n'y figure jamais. Un utilisateur ordinaire ne peut donc pas se
// fabriquer un gabarit officiel via l'API, quel que soit le contenu brut de sa requête.
it('never allows a regular user to set is_official=true via the store API', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Mon faux gabarit',
        'prompt_text' => 'Texte',
        'is_official' => true,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('saved_prompts', [
        'name' => 'Mon faux gabarit',
        'is_official' => false,
    ]);
});

it('never allows a regular user to set is_official=true via the update API', function () {
    $user = User::factory()->create();

    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Mon prompt',
        'prompt_text' => 'Texte',
        'is_official' => false,
    ]);

    $response = $this->actingAs($user)->putJson('/api/prompts/'.$prompt->public_id, [
        'is_official' => true,
        'name' => 'Mon prompt renommé',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('saved_prompts', [
        'public_id' => $prompt->public_id,
        'is_official' => false,
    ]);
});

it('seeds the 6 curated templates idempotently, without duplicates on a second run', function () {
    (new OfficialPromptTemplatesSeeder)->run();

    expect(SavedPrompt::official()->count())->toBe(6);

    $firstPublicIds = SavedPrompt::official()->orderBy('id')->pluck('public_id');

    // Deuxième passage : ne doit créer aucun doublon (updateOrCreate par user système + name).
    (new OfficialPromptTemplatesSeeder)->run();

    expect(SavedPrompt::official()->count())->toBe(6);
    $secondPublicIds = SavedPrompt::official()->orderBy('id')->pluck('public_id');
    expect($secondPublicIds->all())->toBe($firstPublicIds->all());
});

it('gives each official template a real wizard state loadable via the existing remix path', function () {
    (new OfficialPromptTemplatesSeeder)->run();

    $template = SavedPrompt::official()->where('name', 'Courriel professionnel à un client')->firstOrFail();

    expect($template->is_public)->toBeTrue();
    expect($template->params['spaces'])->toBe([
        ['text' => 'Nom du client'],
        ['text' => 'Sujet du courriel'],
        ['text' => 'Ton souhaité'],
    ]);

    // Même endpoint public que le partage/remix d'un prompt utilisateur (PublicPromptController::
    // remixData) - aucune route dédiée créée pour les gabarits, zéro authentification requise.
    $response = $this->getJson('/p/'.$template->public_id.'/remix-data');

    $response->assertOk();
    $response->assertJsonPath('params.taskObject', $template->params['taskObject']);
    $response->assertJsonCount(3, 'params.spaces');
});

it('never shows an official template in another user\'s "Mes prompts"', function () {
    (new OfficialPromptTemplatesSeeder)->run();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/prompts');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(0);
});
