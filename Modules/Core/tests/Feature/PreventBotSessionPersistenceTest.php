<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

use App\Models\User;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Couvre le constat 2026-08-23 : la table `sessions` grossissait d'environ
 * 32 000 lignes/jour (962 903 lignes, 737 Mo, 12 % de l'espace base de
 * données du compte), ~85 % des lignes provenant de robots qui ne
 * renvoient jamais le cookie de session. Cible
 * Modules\Core\Http\Middleware\PreventBotSessionPersistence, câblé AVANT
 * StartSession dans bootstrap/app.php (prependToGroup('web', ...)).
 *
 * phpunit.xml force SESSION_DRIVER=array pour toute la suite de tests -
 * chaque test restaure explicitement le pilote "database" (celui de
 * production, voir config/session.php) AVANT de faire la requête, sinon la
 * bascule du middleware ne serait jamais observable (tout serait déjà en
 * mémoire, robot ou pas).
 *
 * Route GET utilisée : /llms.txt (App\Http\Controllers\LlmsController) -
 * publique, sous le groupe "web", strictement en lecture (Cache::remember +
 * comptages try/catch), aucune dépendance lourde.
 * Route POST utilisée : /locale/fr (Modules\Translation\...\LocaleController)
 * - seule route POST publique légère du site, sous le groupe "web".
 */
beforeEach(function () {
    $this->botUa = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
    $this->browserUa = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';
    config(['session.driver' => 'database']);
});

test('un GET de robot connu sans cookie n\'écrit aucune ligne dans sessions', function () {
    $avant = DB::table('sessions')->count();

    $this->withHeaders(['User-Agent' => $this->botUa])->get('/llms.txt');

    expect(DB::table('sessions')->count())->toBe($avant);
});

test('ce même robot reçoit quand même une réponse 200 avec le contenu normal', function () {
    $reponse = $this->withHeaders(['User-Agent' => $this->botUa])->get('/llms.txt');

    $reponse->assertOk()->assertSee('La veille (laveille.ai)', false);
});

test('un GET de navigateur ordinaire écrit bien sa ligne (non-régression)', function () {
    $avant = DB::table('sessions')->count();

    $this->withHeaders(['User-Agent' => $this->browserUa])->get('/llms.txt');

    expect(DB::table('sessions')->count())->toBe($avant + 1);
});

test('un POST de robot écrit sa ligne - condition 2, protège le jeton CSRF', function () {
    $avant = DB::table('sessions')->count();

    $this->withHeaders(['User-Agent' => $this->botUa])->post('/locale/fr');

    expect(DB::table('sessions')->count())->toBe($avant + 1);
});

test('une requête portant déjà un cookie de session écrit sa ligne, même avec un user-agent de robot', function () {
    $avant = DB::table('sessions')->count();

    $this->withCookie((string) config('session.cookie'), 'valeur-existante-quelconque')
        ->withHeaders(['User-Agent' => $this->botUa])
        ->get('/llms.txt');

    expect(DB::table('sessions')->count())->toBe($avant + 1);
});

test('une requête authentifiée n\'est jamais basculée, même avec un user-agent de robot', function () {
    $utilisateur = User::factory()->create();
    $avant = DB::table('sessions')->count();

    $this->actingAs($utilisateur)
        ->withHeaders(['User-Agent' => $this->botUa])
        ->get('/llms.txt');

    expect(DB::table('sessions')->count())->toBe($avant + 1);
});
