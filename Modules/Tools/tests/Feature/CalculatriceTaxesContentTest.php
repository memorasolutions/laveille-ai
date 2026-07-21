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
});

it('affiche les info-bulles avec les apostrophes correctes', function () {
    expect($this->content)
        ->toContain('l\'autre champ se calcule automatiquement')
        ->not->toContain('l autre champ');
});
