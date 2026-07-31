<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 102 (2026-07-27) : passe adversariale fraîche après le lot round 101 (selectedTask
// round-trip). 1 manque réel corrigé, dans un composant PARTAGÉ site-wide (pas seulement
// constructeur-prompts, mais l'action « Supprimer » de /user/prompts - "Mes prompts" - en fait
// partie et était directement affectée) :
//
// Modules/Core/resources/views/components/action-menu.blade.php - la branche `alpineClick` du
// menu kebab (⋮) réutilisé site-wide codait en dur color:#374151 et le survol #F9FAFB, SANS jamais
// lire $action['danger'] - contrairement aux 3 autres branches du même fichier (wireClick, formulaire
// POST/DELETE, lien <a>) qui basculent déjà vers #DC2626/#FEF2F2 quand danger est vrai. L'action
// « Supprimer » d'un prompt sauvegardé (user/prompts/index.blade.php) passe par
// $dispatch('open-confirm-global', ...) - donc par alpineClick - avec 'danger' => true : elle
// s'affichait dans le même gris neutre que « Dupliquer »/« Modifier les tags », sans aucun indice
// visuel de dangerosité avant une suppression irréversible. Fixé : la branche alpineClick applique
// désormais la même logique conditionnelle danger que les 3 autres branches (mirroir exact du
// pattern déjà en place, lignes wireClick/formulaire/lien du même fichier).
//
// Trouvé en vérifiant ce fix (pas signalé par le sous-agent) : la branche <a> (lien simple) avait
// déjà color conditionnel au danger, mais son onmouseover restait codé en dur sur #F9FAFB (jamais
// #FEF2F2) - même classe de bug, sur la moitié seulement du style. Fixé en même temps.

it('applies the danger style to the alpineClick action-menu branch when danger is true (round 102)', function () {
    $blade = file_get_contents(base_path('Modules/Core/resources/views/components/action-menu.blade.php'));

    // La branche alpineClick doit désormais lire $action['danger'], comme les 3 autres branches.
    // Round 103 (2026-07-27) : la valeur elle-même est passée de #DC2626 (~4,83:1, AA seulement) à
    // #991B1B (~8,3:1, AAA) - voir Round103AdversarialFixesTest.php - donc cette assertion vérifie
    // désormais #991B1B, plus #DC2626.
    expect(substr_count($blade, "color: {{ isset(\$action['danger']) && \$action['danger'] ? '#991B1B' : '#374151' }}"))->toBe(4);
    expect(substr_count($blade, "background='{{ isset(\$action['danger']) && \$action['danger'] ? '#FEF2F2' : '#F9FAFB' }}'"))->toBe(4);
});

it('renders /user/prompts (Mes prompts) correctly after the round 102 fix (real page, no regression)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});

it('renders the constructeur-prompts page correctly after the round 102 fix (real page, no regression)', function () {
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
