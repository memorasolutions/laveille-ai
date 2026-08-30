<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->path = base_path('Modules/Tools/resources/views/public/tools/calculatrice-taxes.blade.php');
    $this->content = file_get_contents($this->path);
    $this->enginePath = base_path('public/tools/calculatrice/js/calculator-simple.js');
    $this->engineContent = file_get_contents($this->enginePath);
});

it('affiche les info-bulles avec les apostrophes correctes', function () {
    expect($this->content)
        ->toContain('l\'autre champ se calcule automatiquement')
        ->not->toContain('l autre champ');
});

// #P0-audit 2026-08-30 : un <input type="number"> rejette la virgule française au clavier
// (confirmé par frappe réelle : "12,50" devient "1250" dans le DOM, une erreur de 100x). Les
// deux champs de montant et le pourcentage de pourboire doivent rester en type="text".
it('n\'utilise plus input type=number pour les montants et le pourcentage de pourboire', function () {
    expect($this->content)
        ->not->toContain('type="number" id="amount-before-tax"')
        ->not->toContain('type="number" id="amount-after-tax"')
        ->not->toContain('type="number" id="rt-tip-percent"')
        ->toContain('type="text" id="amount-before-tax"')
        ->toContain('type="text" id="amount-after-tax"')
        ->toContain('type="text" id="rt-tip-percent"');
});

// P0 : le pourboire ne doit plus jamais EXTRAIRE (diviser) un montant déjà saisi - c'est ce qui
// faisait baisser le sous-total affiché dès qu'un pourcentage était choisi. Le nouveau moteur ne
// touche plus jamais les champs "avant taxes"/tax1/tax2 depuis la logique du pourboire.
it('le module pourboire n\'écrase plus les champs avant-taxes/taxes (fin de l\'extraction)', function () {
    expect($this->content)
        ->not->toContain('beforeEl.value = subtotalRev.toFixed(2)')
        ->not->toContain('t1Display.value = tax1Amount.toFixed(2)')
        ->not->toContain('recalcReverseTipOverride')
        ->toContain('function recalcTip()');
});

// Point 2 du mandat : choix explicite avant/après taxes pour la base du pourboire, défaut =
// avant taxes (usage québécois).
it('propose un choix explicite avant/après taxes pour le pourboire, avant taxes par défaut', function () {
    expect($this->content)
        ->toContain('name="ct-tip-base"')
        ->toContain('id="ct-tip-base-before"')
        ->toContain('id="ct-tip-base-after"')
        ->toContain('value="before" checked');

    // Le radio "before" doit être checked et PAS le "after" (défaut sans ambiguïté)
    preg_match('/id="ct-tip-base-after"[^>]*>/', $this->content, $afterMatch);
    expect($afterMatch)->not->toBeEmpty();
    expect($afterMatch[0])->not->toContain('checked');
});

it('réutilise le mécanisme existant de préférences par outil (pas un nouveau système)', function () {
    expect($this->content)
        ->toContain('/api/tool-preferences/calculatrice-taxes')
        ->toContain("key: 'tip_base'");
});

it('ne contient plus la mention d\'une case à cocher disparue dans l\'aide contextuelle', function () {
    expect($this->content)->not->toContain('cochez « Le total inclut un pourboire »');
});

it('n\'utilise aucune popup native interdite par le projet', function () {
    expect($this->content)
        ->not->toMatch('/\balert\s*\(/')
        ->not->toMatch('/\bconfirm\s*\(/')
        ->not->toMatch('/\bprompt\s*\(/');
    expect($this->engineContent)
        ->not->toMatch('/\balert\s*\(/')
        ->not->toMatch('/\bconfirm\s*\(/')
        ->not->toMatch('/\bprompt\s*\(/');
});

it('les boutons de montants rapides et de pourboire respectent la cible tactile AAA de 44px', function () {
    expect($this->content)->not->toContain('min-height: 32px');
});

it('le résultat du pourboire est annoncé aux lecteurs d\'écran (aria-live)', function () {
    expect($this->content)->toMatch('/id="rt-result"[^>]*aria-live="polite"/');
});

// #P0-audit : le moteur de calcul arrondit désormais de façon sûre (Number.EPSILON), corrige un
// cas vérifié (2,90 $ avant taxes, Québec -> TPS 0,14 $ au lieu de 0,15 $ sans le correctif).
it('le moteur de calcul utilise un arrondi sûr contre les erreurs de virgule flottante', function () {
    expect($this->engineContent)->toContain('Number.EPSILON');
});

it('ne double plus le symbole $ dans les champs de taxes calculés', function () {
    expect($this->engineContent)->not->toContain('${number.toFixed(2)}$');
});

it('la province change recalcule aussi en mode "avec taxes" (plus de blocage sur amountBefore)', function () {
    expect($this->engineContent)->toContain("this.state.activeField === 'after'")
        ->toContain('_recalculateReverse();');
});
