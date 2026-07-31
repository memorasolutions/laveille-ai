<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 84 (2026-07-27) : passe adversariale fraîche après le lot round 83 (touch targets radio
// audienceType/personaType/verbType/formatMode). 1 manque réel corrigé :
//
// 1. Modules/Tools/resources/views/user/prompts/index.blade.php:94-102 (page « Mes prompts ») -
//    les chips de filtre par tag (« Tous » + chaque tag du @foreach) n'avaient ni min-height:44px
//    ni box-sizing:border-box, contrairement à la chip sœur « Favoris seulement » (ligne 111, même
//    rangée de filtres, même forme de pill) qui avait déjà ce traitement WCAG 2.2 AAA SC 2.5.5.
//    Avec padding 5px haut/bas et texte 12px, la hauteur réelle tournait autour de 24-30px, sous
//    le seuil de 44px. Fixé : ajout de display:inline-flex; align-items:center; min-height:44px;
//    box-sizing:border-box aux 2 endroits, cohérent avec le pattern déjà en place sur la chip
//    « Favoris seulement » du même fichier.

it('the tag filter chips have WCAG AAA 44px touch targets on the "Mes prompts" page (round 84)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    // 4 occurrences attendues : "Tous", le lien de tag dans le @foreach (barre de filtres globale),
    // "Favoris seulement" (déjà présent avant round 84 - non régressé), et depuis le round 92
    // (2026-07-27) les chips de tag AFFICHÉES DANS CHAQUE CARTE (@foreach($prompt->tags) - un
    // emplacement distinct de la barre de filtres, même défaut WCAG 2.2 AAA SC 2.5.5, même fix).
    expect(substr_count($blade, 'min-height: 44px; box-sizing: border-box'))->toBe(4);
});

it('renders the tag filter chips with 44px min-height on the real page (round 84)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt tagué',
        'prompt_text' => 'Contenu de test',
        'tags' => ['marketing', 'redaction'],
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    // Le bloc "Tags" doit être rendu (preuve que $availableTags n'est pas vide) et les chips
    // ("Tous" + au moins un tag) doivent porter le min-height:44px WCAG AAA.
    expect($html)->toContain('marketing');
    expect(substr_count($html, 'min-height: 44px; box-sizing: border-box'))->toBeGreaterThanOrEqual(2);
});
