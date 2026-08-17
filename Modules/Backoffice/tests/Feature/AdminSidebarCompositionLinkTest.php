<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — lien « Actualités IA 2.0 » du sidebar admin vers l'écran de composition
 * (Modules/Backoffice/resources/views/themes/backend/partials/sidebar.blade.php), gardé par
 * @if(Route::has('admin.news.composition.index')). Couvre l'affichage du lien pour un admin
 * connecté et l'inaccessibilité de l'écran de composition sans connexion.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

test('le sidebar admin affiche le lien Actualités IA 2.0 pour un admin', function (): void {
    $admin = \App\Models\User::factory()->create(['email' => config('app.superadmin_email')]);
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get('/admin/news/articles');

    $response->assertOk();
    $response->assertSee('Actualités IA 2.0', false);
    $response->assertSee(route('admin.news.composition.index'), false);
});

test('l\'écran de composition est inaccessible sans connexion', function (): void {
    $response = $this->get('/admin/news/composition');

    $response->assertRedirect();
});
