<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 128 (2026-07-30) : perte de travail utilisateur, silencieuse et irréversible - constat
// d'origine : selectTask() remplaçait le champ taskObject par le gabarit brut d'une carte sans
// vérifier si l'utilisateur y avait déjà écrit quelque chose. Voir le round 100 lignes originales
// pour la description complète de ce bug (aujourd'hui obsolète : voir note ci-dessous).
//
// Étape 9 (2026-08-02, réécriture complète) : le MÉCANISME testé ici a disparu avec l'ancien
// assistant multi-étapes (plus de selectTask()/taskObject/query_template - voir Round100, retiré).
// Mais la RÈGLE MÉTIER qu'il protégeait reste explicitement exigée par le nouveau plan (« changer
// de carte après remplissage partiel », .outils/PLAN-CONSTRUCTEUR-PROMPTS-ULTRA-2026-08-02.md,
// section 4, convergence 94/100 claude.ai + 88/100 Gemini) : changer d'avis après avoir rempli des
// trous ne doit JAMAIS effacer le travail déjà saisi. La nouvelle architecture résout ce problème
// structurellement plutôt que par une garde ponctuelle : `values` est un objet UNIQUE, partagé
// entre les 9 gabarits et jamais réinitialisé au changement de carte ou au retour à la grille des
// 9 choix (voir constructeur-prompts-core.js) - il ne peut donc plus y avoir de remplacement
// destructeur puisqu'aucun gabarit n'écrit jamais directement dans les champs de l'utilisateur.
// Ce test réancre la même intention (« ne jamais perdre le texte déjà saisi ») sur le nouveau
// mécanisme, au lieu de verrouiller du code disparu.

it('shares a single values object across all 9 templates instead of resetting fields per card (round 128, re-ancré étape 9)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // `values` est déclaré UNE SEULE FOIS (réduit depuis ALL_SLOTS), jamais recréé à la sélection
    // d'une carte - c'est cette absence de réinitialisation qui garantit qu'aucun texte n'est
    // jamais écrasé par un gabarit.
    expect(substr_count($js, "values: ALL_SLOTS.reduce("))->toBe(1);

    // onCardSelected() (déclenché au clic sur une carte) ne doit contenir aucune remise à zéro de
    // `values` - seule une détection de contenu existant (notifyKept) est autorisée.
    $pos = strpos($js, 'onCardSelected: function () {');
    expect($pos)->not->toBeFalse();
    $body = substr($js, $pos, 1200);
    expect($body)->not->toContain('this.values =');
    expect($body)->not->toContain('this.values[');
});

it('never clears values when returning to the 9-card grid (resetSelection, round 128 re-ancré)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    $pos = strpos($js, 'resetSelection: function () {');
    expect($pos)->not->toBeFalse();
    $body = substr($js, $pos, 400);

    expect($body)->not->toContain('this.values =');
    expect($body)->not->toContain('this.values[');
});

it('announces preserved text with a neutral status notice, not an error alert (round 128 re-ancré)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('x-show="notifyKept" x-cloak role="status" aria-live="polite"');
    expect($blade)->toContain("{{ __('Votre texte a été conservé.') }}");
});

it('renders the wizard after the round 128 fix (real page)', function () {
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
