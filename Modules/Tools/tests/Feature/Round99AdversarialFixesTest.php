<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 99 (2026-07-27) : passe adversariale fraîche après le lot round 98 (_editingId persiste
// après update). 1 manque réel corrigé (cause racine commune à 2 findings du sous-agent) :
//
// lang/fr/validation.php - le tableau 'attributes' n'avait aucune entrée pour 'tags', 'tags.*' ni
// 'prompt_text'. Round 96 (saveTags()) et le pattern round 35/82 (addToHistory()) affichent
// désormais le message serveur PRÉCIS en cas d'échec 422 - mais sans mapping d'attribut, Laravel
// utilise le nom de champ technique brut/anglicisé dans la phrase FR (ex. "Le texte tags.0 ne doit
// pas contenir plus de 30 caractères." ou "Le champ prompt text ne doit pas dépasser..."), ce qui
// contredit la règle "français impeccable" du projet. Fixé : ajout de 'tags' => 'étiquettes',
// 'tags.*' => 'étiquette', 'prompt_text' => 'texte du prompt' au tableau attributes.

it('translates the tags and prompt_text validation attribute names to proper French (round 99)', function () {
    $php = file_get_contents(base_path('lang/fr/validation.php'));

    expect($php)->toContain("'tags' => 'étiquettes',");
    expect($php)->toContain("'tags.*' => 'étiquette',");
    expect($php)->toContain("'prompt_text' => 'texte du prompt',");
});

it('renders a French-only validation message (no raw field name) when a tag exceeds 30 characters (round 99)', function () {
    app()->setLocale('fr');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test',
        'prompt_text' => 'Contenu de test',
        'params' => [],
        'tags' => [str_repeat('a', 31)],
    ]);

    $response->assertStatus(422);
    $errors = $response->json('errors');
    $message = $errors['tags.0'][0] ?? $errors['tags'][0] ?? $response->json('message');

    expect($message)->toContain('étiquette');
    expect($message)->not->toContain('tags.0');
    expect($message)->not->toContain('tags.*');
});

it('renders a French-only validation message (no raw field name) when prompt_text exceeds 20000 characters (round 99)', function () {
    app()->setLocale('fr');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/prompts', [
        'name' => 'Test',
        'prompt_text' => str_repeat('a', 20001),
        'params' => [],
    ]);

    $response->assertStatus(422);
    $message = $response->json('errors.prompt_text.0') ?? $response->json('message');

    expect($message)->toContain('texte du prompt');
    expect($message)->not->toContain('prompt_text');
});
