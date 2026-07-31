<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 96 (2026-07-27) : passe adversariale fraîche après le lot round 95 (commitCardPanelBlur()).
// 1 manque réel corrigé :
//
// Modules/Tools/resources/views/user/prompts/index.blade.php - le bouton "Enregistrer" du panneau
// d'édition inline des tags exécutait `saveTags(...); editingTags = false` en SYNCHRONE : le
// panneau se fermait AVANT même la résolution de la Promise async saveTags(). Si le serveur
// rejetait la requête (422, un tag > 30 caractères via la règle `tags.* => string|max:30` de
// SavedPromptController::update()), l'utilisateur voyait un toast générique "Erreur lors de la
// mise à jour des tags." sans jamais connaître la cause précise, et le panneau était déjà fermé -
// impossible de corriger rapidement. Fixé : saveTags() retourne désormais true/false, lit
// res.json() en cas d'échec pour afficher le message serveur précis quand le statut est 422 (même
// pattern que addToHistory(), constructeur-prompts-core.js, rounds 35/82), et le bouton attend
// (async IIFE) la résolution avant de fermer le panneau - seulement en cas de succès. Le label
// mentionne désormais la limite de 30 caractères par tag.

it('gives the tags-save button an async IIFE that only closes the panel on success (round 96)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect($blade)->toContain('@click="(async () => { if (await saveTags(');
    expect($blade)->not->toContain('@click="saveTags({{ json_encode($prompt->public_id) }}, tagsInput); editingTags = false"');
});

it('makes saveTags() return a boolean and surface the precise 422 server message (round 96)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect($blade)->toContain('return true;');
    expect($blade)->toContain("if (res.status === 422 && body.message) {");
    expect($blade)->toContain('this._toast(body.message, \'danger\');');
});

it('mentions the 30-character per-tag limit in the tags label (round 96)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect($blade)->toContain('30 caractères max chacun');
});

it('renders the "Mes prompts" page with the round 96 async tags-save binding present (real page)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 96',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-96'],
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    expect($html)->toContain('(async () => { if (await saveTags(');
});
