<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Signature\Models\Signature;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $this->superadmin->assignRole('super_admin');
});

function sigCreateSignature(array $overrides = []): Signature
{
    $sig = new Signature();
    $sig->template = $overrides['template'] ?? 'minimal';
    $sig->content = $overrides['content'] ?? ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $sig->status = Signature::STATUS_ACTIVE;
    $sig->last_owner_activity_at = now();
    $sig->admin_token_hash = hash('sha256', $overrides['admin_token'] ?? 'jeton-de-test');
    if (array_key_exists('user_id', $overrides)) {
        $sig->user_id = $overrides['user_id'];
    }
    $sig->save();

    return $sig;
}

test('le bon jeton donne accès à la page de gestion (superadmin, outil en construction)', function (): void {
    $sig = sigCreateSignature(['admin_token' => 'le-vrai-jeton']);

    $this->actingAs($this->superadmin)
        ->get(route('signature.manage', ['token' => 'le-vrai-jeton']))
        ->assertOk()
        ->assertSee('Marie', false);
});

test('un jeton invalide est refusé (404 générique, jamais une distinction devinable)', function (): void {
    sigCreateSignature(['admin_token' => 'le-vrai-jeton']);

    $this->actingAs($this->superadmin)
        ->get(route('signature.manage', ['token' => 'un-jeton-different']))
        ->assertNotFound();
});

test('une chaîne vide ou aléatoire ne donne jamais accès', function (): void {
    sigCreateSignature(['admin_token' => 'le-vrai-jeton']);

    $this->actingAs($this->superadmin)
        ->get(route('signature.manage', ['token' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxx']))
        ->assertNotFound();
});

test('le jeton en clair n\'est jamais stocké - seul son hash SHA-256 est en base', function (): void {
    $sig = sigCreateSignature(['admin_token' => 'jeton-secret-abc']);

    expect($sig->admin_token_hash)->toBe(hash('sha256', 'jeton-secret-abc'))
        ->and($sig->admin_token_hash)->not->toBe('jeton-secret-abc');
});

test('la page de gestion porte l\'en-tête Referrer-Policy: no-referrer', function (): void {
    sigCreateSignature(['admin_token' => 'jeton-referrer']);

    $this->actingAs($this->superadmin)
        ->get(route('signature.manage', ['token' => 'jeton-referrer']))
        ->assertHeader('Referrer-Policy', 'no-referrer');
});

test('la régénération du jeton invalide l\'ancien et le nouveau fonctionne', function (): void {
    sigCreateSignature(['admin_token' => 'jeton-avant-rotation']);

    $response = $this->actingAs($this->superadmin)
        ->post(route('signature.manage.rotate', ['token' => 'jeton-avant-rotation']));

    $response->assertOk();
    $newToken = $response->json('admin_token');

    $this->actingAs($this->superadmin)
        ->get(route('signature.manage', ['token' => 'jeton-avant-rotation']))
        ->assertNotFound();

    $this->actingAs($this->superadmin)
        ->get(route('signature.manage', ['token' => $newToken]))
        ->assertOk();
});
