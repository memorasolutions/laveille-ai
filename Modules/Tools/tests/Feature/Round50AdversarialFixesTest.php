<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 50 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 1 fix réel.
//
// Le bouton favori (⭐) de "Mes prompts" passait son état courant comme un booléen PHP figé au
// rendu serveur (@click="toggleFavorite(id, {{ $prompt->is_favorite ? 'true' : 'false' }}, $el)").
// toggleFavorite() calculait next = !current à partir de ce littéral jamais réévalué - après le
// tout premier clic, chaque clic suivant renvoyait exactement le même PUT (is_favorite figé à
// true ou false selon l'état au chargement de la page), rendant le bouton inversible une seule
// fois par chargement. Fix : lire l'état courant depuis le DOM (aria-pressed, déjà tenu à jour à
// chaque succès) au lieu d'un paramètre figé.

it('does not embed a stale server-rendered boolean in the favorite toggle click handler (round 50)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();
    $favoritePrompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt favori round 50',
        'prompt_text' => 'Texte',
        'public_id' => 'r50fav123',
        'is_favorite' => true,
    ]);
    $nonFavoritePrompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt non favori round 50',
        'prompt_text' => 'Texte',
        'public_id' => 'r50nonfav123',
        'is_favorite' => false,
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    // Round 50 : le handler ne doit plus jamais contenir un booléen littéral comme 2e argument -
    // seulement l'ID et $el. Ni le prompt favori ni le non-favori ne doivent laisser passer
    // l'ancien pattern figé. Blade échappe json_encode() en attribut HTML (" -> &quot;).
    expect($html)->toContain("toggleFavorite(" . e(json_encode($favoritePrompt->public_id)) . ", \$el)");
    expect($html)->toContain("toggleFavorite(" . e(json_encode($nonFavoritePrompt->public_id)) . ", \$el)");
    expect($html)->not->toContain("toggleFavorite(" . e(json_encode($favoritePrompt->public_id)) . ", true, \$el)");
    expect($html)->not->toContain("toggleFavorite(" . e(json_encode($nonFavoritePrompt->public_id)) . ", false, \$el)");
});

it('derives the current favorite state from the DOM instead of a fixed parameter (round 50)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();
    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 50',
        'prompt_text' => 'Texte',
        'public_id' => 'r50fn123',
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();

    // Tâche #1416 (2026-08-02) : la fonction toggleFavorite() vit désormais dans le fichier extrait
    // public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus dans le HTML rendu.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    // Round 50 : la fonction toggleFavorite() doit lire son état courant depuis buttonEl
    // (aria-pressed), pas depuis un paramètre - sinon le bug se reproduit sous une autre forme.
    expect($js)->toContain("async toggleFavorite(publicId, buttonEl) {");
    expect($js)->toContain("buttonEl.getAttribute('aria-pressed') === 'true'");
});
