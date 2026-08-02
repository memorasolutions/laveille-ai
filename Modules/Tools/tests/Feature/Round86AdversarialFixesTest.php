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

// Étape 9 (2026-08-02, réécriture complète) : le test qui comptait >= 15 boutons min-height:44px
// et verrouillait le bouton openIn('chatgpt') de l'ancienne interface a été retiré - la nouvelle
// page, radicalement plus simple (plan section 1, "un seul écran"), n'a plus qu'une poignée de
// boutons d'action (Copier, Ouvrir dans, Masquer mes infos, Effacer l'historique), tous avec
// style="min-height:44px;" (voir constructeur-prompts.blade.php) mais en nombre bien inférieur à
// l'ancien plancher de 15 - ce compte n'est plus un signal utile. Le test sur /user/prompts
// (page inchangée) ci-dessous reste valide tel quel.

it('has WCAG AAA 44px touch targets on the tag-edit buttons in Mes prompts (round 86)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect(substr_count($blade, 'min-height:44px'))->toBeGreaterThanOrEqual(2);
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
