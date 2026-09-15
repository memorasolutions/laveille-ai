<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - marquage du trafic interne pour Google Analytics (paramètre traffic_type).
 *
 * Mesuré le 2026-09-15 (#2583) : la page de liste des actualités affichait 99 sessions pour SIX
 * personnes réelles, dont UNE seule qui en générait 17. Des décisions de produit avaient été
 * prises sur ce bruit. Ces tests figent le marquage qui en retire la part authentifiée.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Le bloc de script n'est rendu que si ces DEUX clés sont vraies. Sans elles, tous les tests
    // ci-dessous passeraient sur une page qui ne contient aucun marquage.
    config()->set('services.ga.measurement_id', 'G-TEST123');
    config()->set('services.ga.privacy_enabled', true);
});

test('un visiteur anonyme est marqué comme trafic externe', function (): void {
    // Si ce test tombe, de vrais visiteurs seraient exclus des rapports : le site paraîtrait mort
    // alors qu'il ne l'est pas, ce qui est le défaut inverse et plus grave que celui qu'on corrige.
    $contenu = $this->get('/')->getContent();

    expect($contenu)->toContain("'traffic_type': 'external'");
});

test('un administrateur connecté est marqué comme trafic interne', function (): void {
    // Si ce test tombe, le travail d'édition sur le site recompte comme de l'audience, et chaque
    // chiffre du tableau de bord redevient flatteur pour de mauvaises raisons.
    $user = User::factory()->create();
    $user->assignRole('admin');

    $contenu = $this->actingAs($user)->get('/')->getContent();

    expect($contenu)
        ->toContain("'traffic_type': 'internal'")
        ->not->toContain("'traffic_type': 'external'");
});

test("un utilisateur connecté SANS rôle d'équipe reste du trafic externe", function (): void {
    // C'est la frontière qui compte : marquer un visiteur ordinaire comme interne le ferait
    // disparaître des rapports, ce qui serait pire que le défaut qu'on corrige.
    $user = User::factory()->create();

    $contenu = $this->actingAs($user)->get('/')->getContent();

    expect($contenu)->toContain("'traffic_type': 'external'");
});

test("sans identifiant de mesure configuré, aucun marquage n'est émis", function (): void {
    // Contrôle négatif : prouve que les trois tests précédents ne passent pas par accident sur un
    // bloc qui serait toujours présent quoi qu'il arrive.
    config()->set('services.ga.measurement_id', null);

    $contenu = $this->get('/')->getContent();

    expect($contenu)->not->toContain('traffic_type');
});
