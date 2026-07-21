<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->path = base_path('Modules/Directory/resources/views/admin/create.blade.php');
    $this->content = file_get_contents($this->path);
});

it('utilise Livewire.dispatch pour le toast de capture d\'écran (pas CustomEvent cassé)', function () {
    expect($this->content)->toContain("Livewire.dispatch('toast'");
});

it('affiche le message d\'avertissement avec les apostrophes correctes', function () {
    expect($this->content)->toContain("Entrez d\\'abord l\\'URL du site.");
});
