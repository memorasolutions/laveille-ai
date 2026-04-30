<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->path = base_path('Modules/Directory/resources/views/public/show.blade.php');
    $this->content = file_get_contents($this->path);
});

it('valide la regex client pour les URLs', function () {
    expect($this->content)->toContain('/^https?:\/\/[^\s]+\.[^\s]+/i');
});

it('affiche un toast warning pour URL invalide', function () {
    expect($this->content)->toContain('URL invalide. Utilisez https:// avec un domaine valide.');
});

it('utilise la variante warning sur erreur URL', function () {
    expect($this->content)->toContain("variant: 'warning'");
});

it('utilise une duree 4000 sur le toast warning URL', function () {
    expect($this->content)->toContain('duration: 4000');
});

it('montre le bouton step 2 conditionnellement', function () {
    expect($this->content)->toContain('x-show="step===2"');
});

it('utilise @submit.prevent sur le formulaire step 3', function () {
    expect($this->content)->toContain('@submit.prevent');
});

it('affiche le message specifique 429 trop de tentatives', function () {
    expect($this->content)->toContain('Trop de tentatives. Patientez 60 secondes avant de réessayer.');
});

it('utilise la variante danger sur erreur reseau', function () {
    expect($this->content)->toContain("variant: 'danger'");
});

it('declare le toast Erreur reseau pour fetch fail', function () {
    expect($this->content)->toContain('Erreur réseau');
});

it('utilise CustomEvent toast-show pour notifications', function () {
    expect($this->content)->toContain("new CustomEvent('toast-show'");
});
