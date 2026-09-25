<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Brief du 2026-09-25 : en production, l'outil n'est visible QUE par le fondateur (superadmin) ;
 * tout autre visiteur voit la page "en construction" (200 + noindex), jamais 503 - réutilise
 * Modules\Tools\Models\Tool::isAccessibleTo() (constructeur-prompts) plutôt qu'un mécanisme neuf.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Signature\Models\Signature;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $this->superadmin->assignRole('super_admin');

    // Réplique le seed migration 2026_09_25_100200_seed_signature_tool_entry.php (RefreshDatabase
    // exécute les migrations mais pas nécessairement chaque seed applicatif hors artisan migrate
    // - garantit un état déterministe pour ce test indépendamment du pipeline de seed).
    Tool::updateOrCreate(['slug' => 'signature-courriel'], [
        'name' => 'Signature de courriel',
        'description' => 'x',
        'is_active' => true,
        'is_under_construction' => true,
        'construction_mode' => 'construction',
    ]);
});

test('un visiteur anonyme voit la page "en construction" (200, noindex), jamais l\'éditeur', function (): void {
    $response = $this->get(route('signature.assistant'));

    $response->assertOk();
    $response->assertSee('construction', false);
    expect($response->getContent())->not->toContain('signatureAssistant(');
});

test('un membre connecté non-superadmin voit aussi la page "en construction"', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('signature.assistant'))
        ->assertOk()
        ->assertSee('construction', false);
});

test('le fondateur (superadmin) voit l\'éditeur réel (200)', function (): void {
    $this->actingAs($this->superadmin)
        ->get(route('signature.assistant'))
        ->assertOk()
        ->assertSee('signatureAssistant(', false);
});

test('la page "en construction" ne renvoie jamais un code 503 pour cet outil jamais encore lancé', function (): void {
    $this->get(route('signature.assistant'))->assertStatus(200);
});

test('un visiteur anonyme muni d\'un jeton secret VALIDE reste bloqué par le gate construction (l\'outil entier reste réservé au fondateur)', function (): void {
    $sig = new Signature();
    $sig->template = 'minimal';
    $sig->content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $sig->admin_token_hash = hash('sha256', 'jeton-pendant-construction');
    $sig->status = Signature::STATUS_ACTIVE;
    $sig->save();

    $this->get(route('signature.manage', ['token' => 'jeton-pendant-construction']))
        ->assertOk()
        ->assertSee('construction', false);
});

test('après publication (is_under_construction=false), un visiteur anonyme voit l\'éditeur normalement', function (): void {
    Tool::where('slug', 'signature-courriel')->update(['is_under_construction' => false]);

    $this->get(route('signature.assistant'))
        ->assertOk()
        ->assertSee('signatureAssistant(', false);
});
