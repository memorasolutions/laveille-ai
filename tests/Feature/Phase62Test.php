<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Subscriber;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('campaign model peut être créé', function () {
    $campaign = Campaign::create([
        'subject' => 'Test',
        'content' => 'Contenu',
        'status' => 'draft',
    ]);

    expect($campaign->isDraft())->toBeTrue();
});

it('campaign factory fonctionne', function () {
    $campaign = Campaign::factory()->create();

    expect((string) $campaign->status)->toBe('draft');
});

it('campaign sent state', function () {
    $campaign = Campaign::factory()->sent()->create();

    expect($campaign->isSent())->toBeTrue();
});

it('admin peut voir la liste des campagnes', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

    $this->actingAs($admin)
        ->get('/admin/newsletter/campaigns')
        ->assertOk();
});

it('admin peut créer une campagne', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

    // CampaignController::store exige désormais le champ template (in: modern|minimal|dark).
    $this->actingAs($admin)
        ->post('/admin/newsletter/campaigns', [
            'subject' => 'Sujet test',
            'content' => 'Contenu test',
            'template' => 'modern',
        ])
        ->assertRedirect();

    expect(Campaign::count())->toBe(1);
});

it('admin peut envoyer une campagne', function () {
    Notification::fake();

    // BrevoService::isConfigured() exige une clé API ; on configure une clé factice
    // et on intercepte tout appel HTTP réel vers Brevo (aucun envoi véritable).
    config(['services.brevo.api_key' => 'test-key']);
    Http::fake([
        'api.brevo.com/*' => Http::response(['messageId' => 'test-message-id'], 201),
    ]);

    $admin = User::factory()->create();
    $admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    Subscriber::factory()->confirmed()->count(2)->create();
    $campaign = Campaign::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/newsletter/campaigns/{$campaign->id}/send")
        ->assertRedirect();

    $campaign->refresh();

    expect($campaign->isSent())->toBeTrue()
        ->and($campaign->recipient_count)->toBe(2);
});

it('envoyer campagne déjà envoyée redirige avec erreur', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    $campaign = Campaign::factory()->sent()->create();

    $this->actingAs($admin)
        ->post("/admin/newsletter/campaigns/{$campaign->id}/send")
        ->assertRedirect()
        ->assertSessionHas('error');
});
