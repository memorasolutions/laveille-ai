<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project Académie — extension du menu action-menu au mode Livewire
 *          (wire:click) en plus du mode route existant, pour les 15 vues
 *          Livewire du module Academy qui utilisent des méthodes internes
 *          (confirmation en 2 temps) plutôt que des routes HTTP.
 *
 * Tests de caractérisation (rendu Blade réel via Blade::render + @include,
 * car ce composant est invoqué par @include(...) et n'est PAS enregistré
 * comme tag <x-core::action-menu />). Prouve :
 *  1) le mode route/method existant n'a pas régressé (non-régression) ;
 *  2) le nouveau mode 'wireClick' génère bien wire:click="..." ;
 *  3) les deux modes peuvent cohabiter dans le même menu ;
 *  4) l'accessibilité du bouton déclencheur ⋮ est inchangée.
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->componentPath = base_path('Modules/Core/resources/views/components/action-menu.blade.php');
});

it('le composant action-menu existe sur disque', function () {
    expect($this->componentPath)->toBeFile();
});

it('génère wire:click quand une action fournit wireClick (mode Livewire)', function () {
    $html = Blade::render(
        '@include(\'core::components.action-menu\', [\'actions\' => $actions])',
        ['actions' => [
            ['label' => 'Renommer', 'icon' => 'pencil', 'wireClick' => 'startRenameCohort(42)'],
            ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => 'confirmCohortRemoval(42)', 'danger' => true],
        ]]
    );

    expect($html)
        ->toContain('wire:click="startRenameCohort(42)"')
        ->toContain('wire:click="confirmCohortRemoval(42)"')
        ->toContain('Renommer')
        ->toContain('Supprimer');
});

it('le mode wireClick ne génère ni <form> ni action= (aucune route requise)', function () {
    $html = Blade::render(
        '@include(\'core::components.action-menu\', [\'actions\' => $actions])',
        ['actions' => [
            ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => 'confirmCohortRemoval(42)', 'danger' => true],
        ]]
    );

    expect($html)
        ->not->toContain('<form')
        ->not->toContain('action=');
});

it('le mode wireClick applique la couleur danger comme le mode route', function () {
    $html = Blade::render(
        '@include(\'core::components.action-menu\', [\'actions\' => $actions])',
        ['actions' => [
            ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => 'confirmCohortRemoval(42)', 'danger' => true],
        ]]
    );

    expect($html)->toContain('#DC2626');
});

it('non-régression : le mode route GET simple génère toujours un lien <a href>', function () {
    $html = Blade::render(
        '@include(\'core::components.action-menu\', [\'actions\' => $actions])',
        ['actions' => [
            ['label' => 'Voir', 'icon' => 'eye', 'url' => 'https://example.test/admin/xxx/1'],
        ]]
    );

    expect($html)
        ->toContain('<a href="https://example.test/admin/xxx/1"')
        ->not->toContain('wire:click');
});

it('non-régression : le mode route DELETE + confirm génère toujours un <form> POST avec @method et confirm-action', function () {
    $html = Blade::render(
        '@include(\'core::components.action-menu\', [\'actions\' => $actions])',
        ['actions' => [
            [
                'label'   => 'Supprimer',
                'icon'    => 'trash-2',
                'url'     => 'https://example.test/admin/xxx/1',
                'method'  => 'DELETE',
                'confirm' => 'Supprimer ?',
                'danger'  => true,
            ],
        ]]
    );

    expect($html)
        ->toContain('<form action="https://example.test/admin/xxx/1" method="POST"')
        ->toContain('confirm-action')
        ->not->toContain('wire:click');
});

it('supporte la cohabitation des deux modes (route ET wireClick) dans le même menu', function () {
    $html = Blade::render(
        '@include(\'core::components.action-menu\', [\'actions\' => $actions])',
        ['actions' => [
            ['label' => 'Voir', 'icon' => 'eye', 'url' => 'https://example.test/admin/xxx/1'],
            ['divider' => true],
            ['label' => 'Renommer', 'icon' => 'pencil', 'wireClick' => 'startRenameCohort(42)'],
        ]]
    );

    expect($html)
        ->toContain('<a href="https://example.test/admin/xxx/1"')
        ->toContain('wire:click="startRenameCohort(42)"');
});

it('le bouton déclencheur ⋮ reste inchangé (accessibilité aria-haspopup/aria-expanded)', function () {
    $html = Blade::render(
        '@include(\'core::components.action-menu\', [\'actions\' => $actions])',
        ['actions' => [
            ['label' => 'Renommer', 'icon' => 'pencil', 'wireClick' => 'startRenameCohort(42)'],
        ]]
    );

    expect($html)
        ->toContain('aria-haspopup="true"')
        ->toContain(':aria-expanded="open"')
        ->toContain('aria-label=');
});
