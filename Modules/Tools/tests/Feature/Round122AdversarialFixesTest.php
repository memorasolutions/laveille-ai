<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\SavedPrompt;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 122 (2026-07-30) : passe adversariale fraîche après le round 121, en cherchant les chemins
// JUMEAUX du correctif précédent. 2 manques réels corrigés - même cause racine, deux surfaces.
//
// Cause commune : les items du menu ⋮ exécutent tous `open = false`, ce qui masque (display:none)
// le conteneur `<div x-ref="menu" x-show="open">` contenant le bouton QUI A LE FOCUS. Le focus
// retombe sur <body>. Le round 121 avait traité « Modifier les tags » ; restaient « Supprimer »
// et « Dupliquer ».
//
// 1. SUPPRIMER (gravité haute, portée SITE-WIDE). Le composant partagé confirm-modal capture
//    `triggerEl = document.activeElement` au $dispatch - correctement, car Alpine reporte l'effet
//    du x-show en microtask, donc le bouton est encore visible à cet instant. MAIS à la fermeture
//    de la boîte de dialogue (des centaines de ms plus tard, le temps que l'utilisateur lise), le
//    menu est passé en display:none. Or focus() sur un élément display:none est un NO-OP
//    SILENCIEUX : aucune exception, aucun log, et le focus reste sur <body>. Scénario le plus net :
//    l'utilisateur clique « Annuler » - le prompt n'est PAS supprimé, donc rien ne recharge la
//    page - et se retrouve projeté au début du document sans aucune annonce.
//    Ce composant est utilisé par 10 fichiers du projet : le correctif les couvre tous.
//
// 2. DUPLIQUER (gravité moyenne). duplicatePrompt() ne restaure le focus dans aucun chemin. En cas
//    de succès, le window.location.reload() masque le problème ; mais sur les DEUX chemins
//    d'échec (statut non-201 ou exception réseau), l'utilisateur voit un toast d'erreur et garde
//    un focus perdu sur <body> pour le reste de la session.
//
// Correctifs : repli vers le bouton ⋮ (x-ref=trigger), qui reste toujours visible - même cible
// que restoreFocusIfLost() du round 107, donc cohérent avec le pattern déjà établi.

it('falls back to the still-visible menu trigger when the captured element is hidden (round 122)', function () {
    $modal = file_get_contents(base_path('Modules/Core/resources/views/components/confirm-modal.blade.php'));

    // La tentative d'origine reste en premier : si triggerEl est encore visible, rien ne change.
    expect($modal)->toContain('self.triggerEl.focus();');
    // Détection du no-op silencieux, puis repli.
    expect($modal)->toContain('document.activeElement === document.body && self.triggerEl');
    expect($modal)->toContain("self.triggerEl.closest('[x-ref=menu]')");
    expect($modal)->toContain("menu.parentElement.querySelector('[x-ref=trigger]')");
});

it('restores card focus on both duplicate failure paths only (round 122)', function () {
    // Tâche #1416 (2026-08-02) : _restoreCardFocus()/duplicatePrompt() vivent désormais dans le
    // fichier extrait public/assets/tools/user-prompts/user-prompts-core.js (Alpine.data), plus
    // dans le Blade. NB : à ne pas confondre avec restoreFocusIfLost(el), méthode DISTINCTE
    // définie dans le x-data INLINE par carte (markup Blade, inchangée par cette extraction).
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    expect($js)->toContain('_restoreCardFocus(publicId) {');
    expect($js)->toContain("card.querySelector('[x-ref=trigger]')");
    // 1 définition + exactement 2 appels (échec HTTP + échec réseau). Le chemin de SUCCÈS ne doit
    // pas être instrumenté : il recharge la page, un focus() y serait sans effet et trompeur.
    expect(substr_count($js, '_restoreCardFocus(publicId);'))->toBe(2);
    expect($js)->toContain('window.location.reload();');
});

it('guards the restore behind an actual focus loss (round 122, no side effect)', function () {
    // Tâche #1416 (2026-08-02) : _restoreCardFocus() vit désormais dans le fichier extrait.
    $js = file_get_contents(public_path('assets/tools/user-prompts/user-prompts-core.js'));

    // Si le focus n'a PAS été perdu, on ne le vole pas à l'utilisateur : sortie immédiate.
    expect($js)->toContain('if (document.activeElement !== document.body) return;');
});

it('keeps the rounds 107 and 121 focus fixes untouched (round 122 non-regression)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/user/prompts/index.blade.php'));

    expect(substr_count($blade, 'restoreFocusIfLost($el)'))->toBe(2);
    expect($blade)->toContain('openTagsEditor(el) { this.editingTags = true;');
});

it('renders /user/prompts correctly after the round 122 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt test round 122',
        'prompt_text' => 'Contenu de test',
        'tags' => ['test-round-122'],
    ]);

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});
