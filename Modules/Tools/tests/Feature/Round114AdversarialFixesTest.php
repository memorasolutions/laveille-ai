<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 114 (2026-07-27) : passe adversariale fraîche après le lot round 113, sur un axe
// VOLONTAIREMENT différent (backend + WCAG + i18n, la saga PII des rounds 109-113 étant close).
// Le backend (SavedPromptController, ToolPreferenceController, modèle, routes) a été inspecté en
// profondeur : IDOR scopé strictement (public_id + user_id partout), mass-assignment protégé,
// throttling dédié, validation solide - aucun manque. 2 manques réels corrigés ailleurs :
//
// 1. WCAG 2.2 AAA SC 2.5.5 (cibles tactiles) sur le composant PARTAGÉ action-menu.blade.php :
//    les rounds 90/92 avaient corrigé la cible du BOUTON DÉCLENCHEUR (⋮, bien à 44x44), mais
//    jamais celle des ITEMS du menu déroulant eux-mêmes (« Modifier les tags », « Dupliquer »,
//    « Supprimer »... sur /user/prompts, et 41+ autres usages site-wide) : `padding: 8px 14px`
//    sur du texte 13px = ~30-34px de haut, sous le seuil AAA de 44px. Fixé : min-height 44px +
//    box-sizing border-box sur les 4 branches de rendu (alpineClick, wireClick, form POST/DELETE,
//    lien <a>).
//
// 2. i18n : profile-anon-guard.js (round 112/113) avait TOUS ses textes codés en dur en français
//    (3 libellés de champs, message d'avertissement, aria-label du bouton fermer) - contrairement
//    à son script jumeau prompt-anon-panel.js qui lit window.promptBuilderConfig.i18n. Or la page
//    a un vrai switch de langue : tout se traduisait SAUF ce bandeau. Fixé : lecture de
//    window.promptProfileGuardI18n (injectée par le Blade, même pattern), repli FR si absente.

it('applies WCAG 2.2 AAA touch target size to the action-menu ITEMS, not only the trigger (round 114)', function () {
    $blade = file_get_contents(base_path('Modules/Core/resources/views/components/action-menu.blade.php'));

    // Les 4 branches de rendu (alpineClick, wireClick, form POST/DELETE, lien <a>) doivent
    // toutes porter la cible tactile ; le déclencheur (44x44) était déjà correct avant.
    expect(substr_count($blade, 'min-height: 44px; box-sizing: border-box;'))->toBe(4);
    expect($blade)->toContain('width: 44px; height: 44px;');
});

it('reads the PII guard labels from injected i18n instead of hardcoded French (round 114)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/profile-anon-guard.js'));

    expect($js)->toContain('window.promptProfileGuardI18n');
    expect($js)->toContain('i18n.fieldRole ||');
    expect($js)->toContain('i18n.fieldStyle ||');
    expect($js)->toContain('i18n.fieldConstraints ||');
    expect($js)->toContain('i18n.warning ||');
    expect($js)->toContain('i18n.close ||');
});

it('injects the guard i18n from the blade before the deferred script (round 114)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect($blade)->toContain('window.promptProfileGuardI18n = {');
    // L'injection inline doit précéder le script deferred, sinon l'i18n n'existe pas encore.
    $posInline = strpos($blade, 'window.promptProfileGuardI18n');
    $posScript = strpos($blade, 'profile-anon-guard.js');
    expect($posInline)->toBeLessThan($posScript);
});

it('has English translations for the new guard i18n keys (round 114)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($en)->toHaveKey('Métier / rôle habituel');
    expect($en)->toHaveKey('Style d\'écriture préféré');
    expect($en)->toHaveKey('Contraintes récurrentes');
    expect($en)->toHaveKey('Fermer l\'avertissement');
    expect($en)->toHaveKey('On dirait qu\'il y a des infos personnelles dans « %s ». Retire-les avant d\'enregistrer ton profil - elles seront réutilisées automatiquement dans tes futurs prompts.');
});

it('renders /user/prompts correctly after the round 114 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 114',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-114'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
