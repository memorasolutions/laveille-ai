<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 47 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts, 2 fixes réels.

it('resets the abandoned tags draft when Annuler is clicked on "Mes prompts" (round 47)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();
    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt round 47',
        'prompt_text' => 'Texte',
        'public_id' => 'r47test123',
        'is_favorite' => false,
        'is_public' => false,
        'tags' => ['marketing', 'redaction'],
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    // Round 47 : le bouton Annuler doit restaurer tagsInput à sa valeur d'origine (le CSV réel des
    // tags), pas seulement fermer editingTags - sinon un brouillon abandonné reste visible si
    // l'utilisateur rouvre l'édition sans recharger la page.
    expect($html)->toContain("tagsInput = 'marketing, redaction'; editingTags = false");
});

it('scopes the Tools API rate limit to its own bucket, distinct from the site-wide bare throttle:60,1 (round 47)', function () {
    $route = Route::getRoutes()->getByName('api.api.tool-preferences.show');
    expect($route)->not->toBeNull();

    $throttleMiddleware = collect($route->gatherMiddleware())
        ->first(fn ($m) => str_starts_with($m, 'throttle:'));

    // Round 47 : throttle:60,1 SANS préfixe utilise sha1($user->getAuthIdentifier()) seul comme clé
    // (ThrottleRequests::resolveRequestSignature()) - partagé avec plusieurs autres modules du site
    // utilisant le même throttle:60,1 nu. Un préfixe dédié isole le compteur de Modules/Tools.
    expect($throttleMiddleware)->toBe('throttle:60,1,tools-api');
});
