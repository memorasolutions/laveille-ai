<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Barre de navigation Académie PERSISTANTE et RÔLE-AWARE (<x-academy::nav>).
 *
 * Prouve que l'AFFICHAGE des liens est role-aware (l'accès reste, lui, garanti
 * serveur par les middlewares/policies, testés ailleurs) :
 *  - invité          → « Se connecter », JAMAIS « Créer un cours » ni « Mon espace » ;
 *  - étudiant        → « Mon espace », PAS « Créer un cours » ;
 *  - formateur/admin → « Créer un cours ».
 *
 * Préfixe autonome « anav » pour éviter toute collision de noms de fonctions/helpers.
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    // Désactive la gate « en construction » pour atteindre /academie sans gate superadmin.
    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// INVITÉ — « Se connecter », jamais d'action gérant
// ─────────────────────────────────────────────────────────────────────────────

test('anav : invité voit « Se connecter » mais PAS « Créer un cours » ni « Mon espace »', function (): void {
    $response = $this->get(route('academy.index'));

    $response->assertOk();
    $response->assertSee('Navigation de l\'Académie', false);
    $response->assertSee('Se connecter', false);
    $response->assertDontSee('Créer un cours', false);
    $response->assertDontSee('Mon espace', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// ÉTUDIANT — « Mon espace », jamais « Créer un cours »
// ─────────────────────────────────────────────────────────────────────────────

test('anav : étudiant voit « Mon espace » mais PAS « Créer un cours »', function (): void {
    $etudiant = User::factory()->create();
    $etudiant->assignRole('student');

    $response = $this->actingAs($etudiant)->get(route('academy.index'));

    $response->assertOk();
    $response->assertSee('Mon espace', false);
    $response->assertDontSee('Créer un cours', false);
    $response->assertDontSee('Se connecter', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// FORMATEUR — voit « Créer un cours » (gate hasRole('instructor'))
// ─────────────────────────────────────────────────────────────────────────────

test('anav : formateur voit « Créer un cours » et « Mon espace »', function (): void {
    $formateur = User::factory()->create();
    $formateur->assignRole('instructor');

    $response = $this->actingAs($formateur)->get(route('academy.index'));

    $response->assertOk();
    $response->assertSee('Mon espace', false);
    $response->assertSee('Créer un cours', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN — voit « Créer un cours » (gate can('academy.manage'))
// ─────────────────────────────────────────────────────────────────────────────

test('anav : admin voit « Créer un cours »', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    $response = $this->actingAs($admin)->get(route('academy.index'));

    $response->assertOk();
    $response->assertSee('Créer un cours', false);
});
