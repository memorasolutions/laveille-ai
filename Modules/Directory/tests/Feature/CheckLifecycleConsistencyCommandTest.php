<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests `php artisan directory:check-lifecycle-consistency` - commande de LECTURE SEULE qui
 * compare le lifecycle_status AFFICHÉ sur une fiche au dernier url_last_status réellement mesuré
 * par directory:check-links, et signale les deux quand ils se contredisent.
 *
 * Origine (v1.295.0, 2026-09-23) : la fiche Headroom affichait « Cette plateforme a fermé ses
 * portes. » (lifecycle_status='closed') alors que le service répondait 401 avec « walls.sh is
 * private » - il n'était pas disparu, il était devenu privé. Rien ne détectait ce genre de
 * contradiction tout seul ; ces tests couvrent le contrôle qui la trouve désormais.
 *
 * Convention réutilisée de CheckLinksCommandTest.php (même module) : la fiche est construite à la
 * main via setTranslation() plutôt que via un slug littéral, parce que directory_tools.slug est
 * TRADUISIBLE (JSON Spatie) - jamais de chaîne nue. Cette commande ne filtre d'ailleurs jamais par
 * slug ni par aucune fonction JSON propre à MariaDB (JSON_UNQUOTE n'existe pas sur SQLite, où
 * tourne cette suite).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

/**
 * Construit une fiche de test déjà « contrôlée » : lifecycle_status affiché et résultat du
 * dernier contrôle de lien (url_last_status/url_last_note/url_failure_streak) posés directement,
 * sans passer par directory:check-links - cette commande LIT l'observation, elle ne la produit
 * pas.
 */
function makeLifecycleTestTool(string $slug, array $etat): Tool
{
    $tool = new Tool();
    $tool->url = "https://{$slug}.exemple-test.invalid/page";
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', ucfirst($slug));
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Outil de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Test.');
    $tool->lifecycle_status = $etat['lifecycle_status'] ?? 'active';
    $tool->url_last_status = $etat['url_last_status'] ?? null;
    $tool->url_last_note = $etat['url_last_note'] ?? null;
    $tool->url_failure_streak = $etat['url_failure_streak'] ?? 0;
    $tool->url_last_checked_at = $etat['url_last_checked_at'] ?? now();
    $tool->save();

    return $tool;
}

test('contradiction 1 (cas Headroom) : closed affiche alors que le code mesure est AMBIGU (401)', function () {
    makeLifecycleTestTool('outil-closed-mais-401', [
        'lifecycle_status' => 'closed',
        'url_last_status' => '401',
        'url_last_note' => 'walls.sh is private',
    ]);

    $this->artisan('directory:check-lifecycle-consistency')
        ->expectsOutputToContain('1 contradiction détectée')
        ->expectsOutputToContain('Outil-closed-mais-401')
        ->expectsOutputToContain('statut affiché : Plus en ligne (closed)')
        ->expectsOutputToContain('suggestion : Accès privé (private)')
        ->assertExitCode(0);
});

test('contradiction 2 : active affiche alors que le code mesure est DISPARU (404) et l\'echec dure', function () {
    makeLifecycleTestTool('outil-active-mais-404', [
        'lifecycle_status' => 'active',
        'url_last_status' => '404',
        'url_failure_streak' => 3,
    ]);

    $this->artisan('directory:check-lifecycle-consistency')
        ->expectsOutputToContain('1 contradiction détectée')
        ->expectsOutputToContain('Outil-active-mais-404')
        ->expectsOutputToContain('statut affiché : Actif (active)')
        ->expectsOutputToContain('suggestion : Plus en ligne (closed)')
        ->assertExitCode(0);
});

test('un 404 isole (echec pas encore durable) n\'est PAS signale : le seuil protege d\'un faux positif', function () {
    makeLifecycleTestTool('outil-404-isole', [
        'lifecycle_status' => 'active',
        'url_last_status' => '404',
        'url_failure_streak' => 1,
    ]);

    $this->artisan('directory:check-lifecycle-consistency')
        ->expectsOutputToContain('Aucune contradiction détectée')
        ->assertExitCode(0);
});

test('cas sain : le statut affiche concorde avec le code mesure, rien n\'est signale (sortie explicite)', function () {
    makeLifecycleTestTool('outil-sain', [
        'lifecycle_status' => 'active',
        'url_last_status' => '200',
    ]);

    $this->artisan('directory:check-lifecycle-consistency')
        ->expectsOutputToContain('Fiches examinées')
        ->expectsOutputToContain('Aucune contradiction détectée : le statut affiché concorde avec le dernier code HTTP mesuré sur toutes les fiches examinées.')
        ->assertExitCode(0);
});

test('une fiche jamais controlee (url_last_status absent) est ignoree sans faire planter la commande', function () {
    makeLifecycleTestTool('outil-jamais-controle', [
        'lifecycle_status' => 'closed',
        'url_last_status' => null,
        'url_last_checked_at' => null,
    ]);

    $this->artisan('directory:check-lifecycle-consistency')
        ->expectsOutputToContain('Aucune contradiction détectée')
        ->assertExitCode(0);
});

test('--fail-on-contradiction fait sortir en echec (code 1) quand une contradiction est trouvee', function () {
    makeLifecycleTestTool('outil-fail-on-contradiction', [
        'lifecycle_status' => 'closed',
        'url_last_status' => '403',
    ]);

    $this->artisan('directory:check-lifecycle-consistency', ['--fail-on-contradiction' => true])
        ->assertExitCode(1);
});

test('sans --fail-on-contradiction, une contradiction trouvee n\'empeche pas le code de sortie 0', function () {
    makeLifecycleTestTool('outil-sans-fail-flag', [
        'lifecycle_status' => 'closed',
        'url_last_status' => '503',
    ]);

    $this->artisan('directory:check-lifecycle-consistency')
        ->assertExitCode(0);
});

test('--limit restreint le nombre de fiches examinees', function () {
    makeLifecycleTestTool('outil-limit-1', ['lifecycle_status' => 'closed', 'url_last_status' => '401']);
    makeLifecycleTestTool('outil-limit-2', ['lifecycle_status' => 'closed', 'url_last_status' => '403']);

    $this->artisan('directory:check-lifecycle-consistency', ['--limit' => 1])
        ->expectsOutputToContain('Fiches examinées (statut affiché vs dernier code HTTP mesuré) : 1.')
        ->assertExitCode(0);
});

test('contradiction 3 (sens inverse) : private affiche alors que le code mesure est un succes plein (2xx)', function () {
    makeLifecycleTestTool('outil-private-mais-200', [
        'lifecycle_status' => 'private',
        'url_last_status' => '200',
    ]);

    $this->artisan('directory:check-lifecycle-consistency')
        ->expectsOutputToContain('1 contradiction détectée')
        ->expectsOutputToContain('Outil-private-mais-200')
        ->expectsOutputToContain('statut affiché : Accès privé (private)')
        ->expectsOutputToContain('suggestion : À reconsidérer, peut-être Actif (active)')
        ->assertExitCode(0);
});

test('directory:check-lifecycle-consistency ne modifie jamais lifecycle_status (lecture seule)', function () {
    $tool = makeLifecycleTestTool('outil-lecture-seule', [
        'lifecycle_status' => 'closed',
        'url_last_status' => '401',
    ]);

    $this->artisan('directory:check-lifecycle-consistency')->assertExitCode(0);

    expect($tool->fresh()->lifecycle_status)->toBe('closed');
});
