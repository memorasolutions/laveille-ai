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
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Subscriber;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);
});

// ── Abonnés ───────────────────────────────────────────────────────────────────

it('newsletter index loads with stats', function () {
    Subscriber::factory()->count(3)->create();

    $this->get(route('admin.newsletter.index'))
        ->assertStatus(200)
        ->assertSee('Liste des abonnés');
});

it('non-admin gets 403 on newsletter index', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.newsletter.index'))
        ->assertStatus(403);
});

it('deletes a subscriber', function () {
    $sub = Subscriber::factory()->create();

    $this->delete(route('admin.newsletter.destroy', $sub))
        ->assertRedirect();

    expect(Subscriber::find($sub->id))->toBeNull();
});

it('exports newsletter subscribers as csv', function () {
    Subscriber::factory()->count(2)->create(['confirmed_at' => now()]);

    $response = $this->get(route('admin.newsletter.export'));
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

// ── Campagnes ─────────────────────────────────────────────────────────────────

it('campaigns index loads', function () {
    $this->get(route('admin.newsletter.campaigns.index'))
        ->assertStatus(200)
        ->assertSee('Campagnes newsletter');
});

it('campaigns create page loads', function () {
    $this->get(route('admin.newsletter.campaigns.create'))
        ->assertStatus(200)
        ->assertSee('Nouvelle campagne');
});

it('stores a campaign as draft', function () {
    // CampaignController::store exige désormais le champ template (in: modern|minimal|dark).
    $this->post(route('admin.newsletter.campaigns.store'), [
        'subject' => 'Ma première campagne',
        'content' => 'Bonjour à tous !',
        'template' => 'modern',
    ])->assertRedirect();

    expect(Campaign::where('subject', 'Ma première campagne')->where('status', 'draft')->exists())
        ->toBeTrue();
});

it('sends a draft campaign', function () {
    // BrevoService::isConfigured() exige une clé API ; on configure une clé factice
    // et on intercepte tout appel HTTP réel vers Brevo (aucun envoi véritable).
    config(['services.brevo.api_key' => 'test-key']);
    Http::fake([
        'api.brevo.com/*' => Http::response(['messageId' => 'test-message-id'], 201),
    ]);

    $campaign = Campaign::factory()->create(['status' => 'draft']);
    Subscriber::factory()->count(2)->create(['confirmed_at' => now()]);

    $this->post(route('admin.newsletter.campaigns.send', $campaign))
        ->assertRedirect();

    expect($campaign->fresh()->isSent())->toBeTrue();
});
