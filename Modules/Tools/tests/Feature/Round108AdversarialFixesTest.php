<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 108 (2026-07-27) : passe adversariale fraîche après le lot round 107 (perte de focus sur
// le panneau d'édition des tags, "Mes prompts"). 1 manque réel corrigé, dans le composant PARTAGÉ
// Modules/Core/resources/views/components/confirm-modal.blade.php (modale de confirmation
// site-wide, instanciée une fois avec name="global", déclenchée par 11+ fichiers dont
// constructeur-prompts-core.js::confirmDeleteCard() et user/prompts/index.blade.php pour supprimer
// un prompt sauvegardé) :
//
// La modale n'avait AUCUNE gestion de focus WCAG 2.4.3/2.1.2 : (1) à l'ouverture, le focus
// restait sur le bouton déclencheur désormais recouvert par l'overlay ; (2) aucun piège de focus -
// Tab continuait de naviguer dans la page derrière l'overlay ; (3) à la fermeture (Annuler,
// Confirmer, Échap, clic sur l'overlay), le focus n'était jamais restauré. Fixé : $watch('open',...)
// déplace le focus vers le bouton "Annuler" à l'ouverture et le restaure vers l'élément qui avait
// réellement le focus au moment du déclenchement (document.activeElement, capturé AVANT
// l'ouverture - plus fiable que $event.target qui, pour $dispatch, pointe vers le $el du composant
// Alpine dispatcheur, souvent un <div> wrapper non focalisable) à la fermeture ; trapFocus() cycle
// Tab/Shift+Tab entre les 2 boutons pour empêcher toute sortie du piège tant que la modale est
// ouverte.

it('adds focus management (open, trap, restore) to the shared confirm-modal component (round 108)', function () {
    $blade = file_get_contents(base_path('Modules/Core/resources/views/components/confirm-modal.blade.php'));

    expect($blade)->toContain("this.\$watch('open', function(isOpen)");
    expect($blade)->toContain('trapFocus: function(event)');
    expect($blade)->toContain('@keydown.tab="trapFocus"');
    expect($blade)->toContain('x-ref="cancelBtn"');
    expect($blade)->toContain('x-ref="confirmBtn"');
    expect($blade)->toContain('triggerEl = document.activeElement;');
    // Round 108 : init() est déjà une méthode du x-data (Alpine l'appelle automatiquement) -
    // x-init="init()" causerait une double exécution, ne doit jamais réapparaître.
    expect($blade)->not->toContain('x-init="init()"');
});

it('renders the constructeur-prompts page correctly after the round 108 fix (real page, no regression)', function () {
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

it('renders /user/prompts (Mes prompts) correctly after the round 108 fix (shared modal used there too)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
