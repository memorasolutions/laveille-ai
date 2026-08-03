<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 81 (2026-07-27) : passe adversariale fraîche après le lot round 80 (SavedPromptController
// duplicate() i18n). 1 manque réel corrigé sur 2 rapportés (voir décision ci-dessous) :
//
// 1. public/assets/tools/constructeur-prompts/constructeur-prompts-core.js:741 (importLocalStorage)
//    - le nom de repli 'Prompt importé' (utilisé quand un item localStorage historique n'a pas de
//    nom) était un littéral français codé en dur, jamais ponté via window.promptBuilderConfig.i18n,
//    absent de lang/en.json. Ce nom devient le titre PERSISTÉ du SavedPrompt, affiché tel quel dans
//    « Mes prompts » quel que soit le locale - contrairement à newCardTitle/untitledCard (round 78),
//    équivalent purement UI-facing (le champ `name` n'est JAMAIS injecté dans get prompt(), donc pas
//    concerné par le verrou round 74). Fixé : clé i18n.importedPromptName + repli français JS.
//
// 2e finding du round 81 REJETÉ (pas un manque - même famille round 74, non re-signalée par erreur
// de briefing du superviseur, mais vérifiée en profondeur ici) : canvasFormatMap (JS ~lignes 56-61,
// options du sélecteur "Format attendu prédéfini") - ces libellés français ("Tableau interactif",
// "App embarquée", etc.) sont injectés BRUTS dans constraints.push(canvasLine) qui alimente
// directement get prompt(), le texte réellement envoyé à l'IA - lequel reste TOUJOURS en français
// par design (round 74). Les traduire créerait le même mélange de grammaire FR/EN que round 74 a
// explicitement évité pour personas/verbes/audiences. Non implémenté, cohérent avec la décision
// existante - PAS une régression ni un oubli.

it('has an English translation for the "Prompt importé" default import name (round 81)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($en)->toHaveKey('Prompt importé');
    expect($en['Prompt importé'])->toBe('Imported prompt');
});

it('the JS file falls back to window.promptBuilderConfig.i18n.importedPromptName (round 81)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain("item.name || i18nImport.importedPromptName || 'Prompt importé'");
});

it('injects importedPromptName translated on the real page in EN locale (round 81)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('importedPromptName: "Imported prompt"');
});
