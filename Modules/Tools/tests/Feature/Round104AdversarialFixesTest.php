<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 104 (2026-07-27) : passe adversariale fraîche après le lot round 103 (couleur AAA
// #991B1B sur les 4 branches de action-menu.blade.php). 1 manque réel corrigé :
//
// Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php:244 - le bouton
// "Importer mes X cartes locales" (invité connecté avec des cartes personnalisées créées avant
// authentification) affichait toujours "cartes locales" au pluriel, même pour N=1 ("Importer mes
// 1 cartes locales" - faute d'accord en français). Le toast de SUCCÈS affiché juste après le
// clic gère déjà correctement ce cas exact depuis le round 25
// (customCardsImportedOne/customCardsImportedMany, lignes 887-888 du même fichier et 1163-1164
// de constructeur-prompts-core.js) - le bouton qui déclenche la même action, un écran plus tôt,
// n'avait jamais reçu ce même traitement. Fixé : accord singulier ("Importer 1 carte locale")
// géré via un ternaire x-text, mirroir du pattern déjà établi pour le toast.

it('agrees in number on the "Importer mes X cartes locales" button (round 104)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain("(_localCardsToImport && _localCardsToImport.length === 1) ? '");
    expect($blade)->toContain(__('Importer 1 carte locale'));
});

it('has fr.json/en.json translation entries for the new singular string (round 104)', function () {
    $fr = json_decode(file_get_contents(lang_path('fr.json')), true);
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($fr)->toHaveKey('Importer 1 carte locale');
    expect($en)->toHaveKey('Importer 1 carte locale');
});

it('renders the constructeur-prompts page correctly after the round 104 fix (real page, no regression)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk();
});
