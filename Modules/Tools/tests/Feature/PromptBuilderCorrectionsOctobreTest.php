<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);
});

it('propose le format Courriel dans la liste des formats', function () {
    $html = $this->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('Courriel');
    expect($html)->toContain('"value":"Courriel"');
});

it('affiche le bouton « Terminer et copier le prompt » uniquement à l\'étape 4', function () {
    $html = $this->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('Terminer et copier le prompt');
    expect($html)->toContain('x-show="step === 4"');
    expect($html)->toContain('finishAndCopy()');
});

it('affiche un avertissement doux quand Concis et Paragraphes détaillés sont combinés', function () {
    $html = $this->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('x-show="lengthFormatConflict"');
    expect($html)->toContain('lengthFormatConflictMessage');
    expect($html)->toContain('role="status"');
});
