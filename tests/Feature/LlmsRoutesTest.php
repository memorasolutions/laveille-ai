<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;

// Pas de uses(Tests\TestCase::class) ici : tests/Pest.php applique déjà
// pest()->extend(Tests\TestCase::class)->in('Feature') à tout ce dossier — le redéclarer casse
// le chargement des tests (« Test case Tests\TestCase can not be used »). Contrairement à
// Modules/Tools/tests/Feature (non auto-lié), aucune déclaration n'est nécessaire ici.
//
// RefreshDatabase EST requis malgré tout : le middleware global SetBackofficeTheme lit la table
// `settings` (Modules\Settings\Models\Setting) sans try/catch, donc un sqlite :memory: non
// migré fait échouer les DEUX routes avec un 500 avant même d'atteindre les contrôleurs Llms.
uses(RefreshDatabase::class);

// Épingle /llms.txt (LlmsController::index) et /llms-full.txt (LlmsFullController, contrôleur
// invokable) après la séparation des deux routes en deux contrôleurs distincts. Aucun test ne
// les couvrait avant : un régression silencieuse (route cassée, corps vidé, dump tronqué)
// serait passée inaperçue.

it('/llms.txt répond 200 avec un corps non vide', function () {
    $response = $this->get('/llms.txt');

    $response->assertOk();
    expect(trim((string) $response->getContent()))->not->toBe('');
});

it('/llms-full.txt répond 200 avec un corps non vide', function () {
    $response = $this->get('/llms-full.txt');

    $response->assertOk();
    expect(trim((string) $response->getContent()))->not->toBe('');
});

it('/llms.txt est servi en text/plain', function () {
    $response = $this->get('/llms.txt');

    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
});

it('/llms-full.txt est servi en text/plain', function () {
    $response = $this->get('/llms-full.txt');

    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
});

it('/llms-full.txt (dump complet) est plus long que /llms.txt (index)', function () {
    // Sur une base vide (aucune fixture), /llms-full.txt n'a que ses en-têtes de section
    // (« ## Glossaire (0 termes) », etc.) et est PLUS COURT que le texte narratif fixe de
    // /llms.txt : la propriété « full > index » n'est vraie qu'avec du contenu réel, comme en
    // production. On seed des articles publiés pour reproduire cette condition réelle.
    Article::factory()->count(60)->published()->create();

    $index = (string) $this->get('/llms.txt')->getContent();
    $full = (string) $this->get('/llms-full.txt')->getContent();

    expect(strlen($full))->toBeGreaterThan(strlen($index));
});
