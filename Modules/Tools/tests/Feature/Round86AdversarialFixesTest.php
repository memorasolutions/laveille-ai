<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 86 (2026-07-27) : passe adversariale fraîche après le lot round 85 (spans de validation
// morts + contraste indicateur d'étapes). 1 manque réel corrigé (systémique, plusieurs boutons) :
//
// Cibles tactiles < 44×44px (WCAG 2.2 AAA SC 2.5.5) sur de nombreux boutons `.ct-btn-sm`/
// `.ct-btn-xs` : ces classes (charte.css:1189-1190) n'ont AUCUN plancher de hauteur (contrairement
// à `.ct-btn-accent`/`.ct-btn-icon` qui ont min-height:44px intégré). Confirmé : les 4 boutons
// "Ouvrir dans" du panneau "Améliorer avec mon IA" avaient déjà min-height:44px, mais les 4
// boutons "Ouvrir dans" identiques de la section principale (constructeur-prompts.blade.php
// ~ligne 649-652) ne l'avaient pas - preuve directe de l'incohérence par comparaison au même
// fichier. Étendu à tous les boutons `.ct-btn-sm`/`.ct-btn-xs` du module sans exception "cible
// inline" déjà établie (les "?" d'aide circulaires 24×24 restent inchangés, exception WCAG AA
// documentée dans charte.css:1205-1214 - non concernés par ce fix). Fixé : ajout de
// min-height:44px (min-width:44px en plus pour le bouton ✕ suppression icône-seul) sur :
// Importer (historique), Se connecter (x2), Importer mes cartes locales, Masquer mes infos
// personnelles/Anonymiseur complet, 4 boutons "Ouvrir dans" (section principale), Effacer,
// Copier (historique), ✕ suppression (historique), et Enregistrer/Annuler (édition tags,
// page Mes prompts).

it('has WCAG AAA 44px touch targets on all ct-btn-sm/ct-btn-xs buttons in constructeur-prompts.blade.php (round 86)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    // Les 4 boutons "Ouvrir dans" de la section principale (round 86 finding #1).
    expect(substr_count($blade, "@click=\"openIn('chatgpt')\""))->toBe(1);
    expect($blade)->toContain("'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')");

    // Le bouton ✕ de suppression (round 86 finding #2) : min-width en plus de min-height (icône seule).
    expect($blade)->toContain('min-height:44px; min-width:44px; padding:1px 5px;');

    // Échantillon des autres boutons corrigés (round 86 finding #3).
    expect(substr_count($blade, 'min-height:44px'))->toBeGreaterThanOrEqual(15);
});

it('has WCAG AAA 44px touch targets on the tag-edit buttons in Mes prompts (round 86)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect(substr_count($blade, 'min-height:44px'))->toBeGreaterThanOrEqual(2);
});

it('renders the "open in" buttons with 44px min-height on the real page regardless of validity state (round 86)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect(substr_count($html, 'min-height:44px'))->toBeGreaterThanOrEqual(15);
});

it('renders the tag-edit buttons with 44px min-height on the real page (round 86)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test',
        'prompt_text' => 'Contenu de test',
    ]);

    $html = $this->actingAs($user)->get('/user/prompts')->assertOk()->getContent();

    expect(substr_count($html, 'min-height:44px'))->toBeGreaterThanOrEqual(2);
});
