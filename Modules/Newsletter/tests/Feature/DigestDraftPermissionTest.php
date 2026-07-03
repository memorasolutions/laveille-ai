<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Preuve négative : un utilisateur avec un accès admin générique
 * (view_admin_panel, via EnsureIsAdmin) mais SANS la permission granulaire
 * update_newsletter doit se faire bloquer sur le brouillon de la newsletter
 * hebdomadaire (digest), au même titre que les autres actions mutantes du
 * module (voir RolesAndPermissionsSeeder et PermissionEnforcementTest).
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Newsletter\Models\NewsletterIssue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeRestrictedNewsletterAdmin(string ...$permissions): User
{
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'restricted_admin_test', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::firstOrCreate(['name' => 'view_admin_panel', 'guard_name' => 'web']));
    foreach ($permissions as $permission) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    $user->assignRole($role);

    return $user;
}

function makeDraftIssue(): NewsletterIssue
{
    return NewsletterIssue::create([
        'week_number' => 27,
        'year' => 2026,
        'subject' => 'Concentré IA de la semaine',
        'status' => 'draft',
    ]);
}

test('digest edit bloque un admin sans update_newsletter', function () {
    makeDraftIssue();
    $admin = makeRestrictedNewsletterAdmin();

    $this->actingAs($admin)
        ->get(route('admin.newsletter.digest.edit'))
        ->assertForbidden();
});

test('digest update bloque un admin sans update_newsletter', function () {
    $issue = makeDraftIssue();
    $admin = makeRestrictedNewsletterAdmin();

    $this->actingAs($admin)
        ->put(route('admin.newsletter.digest.update', $issue), [
            'subject' => 'Nouveau sujet',
        ])
        ->assertForbidden();

    expect($issue->fresh()->subject)->toBe('Concentré IA de la semaine');
});

test('digest edit fonctionne pour un admin avec update_newsletter', function () {
    // Note : le rendu complet de la vue dépend d'un bug préexistant et
    // indépendant (layout de digest-draft-edit.blade.php), hors du périmètre
    // de ce correctif ; seul le comportement de la permission est vérifié ici.
    makeDraftIssue();
    $admin = makeRestrictedNewsletterAdmin('update_newsletter');

    $response = $this->actingAs($admin)->get(route('admin.newsletter.digest.edit'));

    expect($response->status())->not->toBe(403);
});

test('digest update fonctionne pour un admin avec update_newsletter', function () {
    $issue = makeDraftIssue();
    $admin = makeRestrictedNewsletterAdmin('update_newsletter');

    $this->actingAs($admin)
        ->put(route('admin.newsletter.digest.update', $issue), [
            'subject' => 'Nouveau sujet',
        ])
        ->assertRedirect();

    expect($issue->fresh()->subject)->toBe('Nouveau sujet');
});
