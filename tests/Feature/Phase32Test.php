<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Backoffice\Models\WebhookEndpoint;

uses(RefreshDatabase::class);

function makePhase32Admin(): User
{
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

test('can list api tokens page', function () {
    $admin = makePhase32Admin();

    $this->actingAs($admin)
        ->get('/admin/profile/tokens')
        ->assertOk();
});

test('can create api token', function () {
    $admin = makePhase32Admin();

    $this->actingAs($admin)
        ->post('/admin/profile/tokens', ['name' => 'Test Token'])
        ->assertRedirect();

    $this->assertDatabaseHas('personal_access_tokens', [
        'name' => 'Test Token',
        'tokenable_id' => $admin->id,
    ]);
});

test('can revoke api token', function () {
    $admin = makePhase32Admin();
    $token = $admin->createToken('tok')->accessToken;

    $this->actingAs($admin)
        ->delete("/admin/profile/tokens/{$token->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

test('can list webhooks page', function () {
    $admin = makePhase32Admin();

    $this->actingAs($admin)
        ->get('/admin/webhooks')
        ->assertOk();
});

test('can create webhook', function () {
    $admin = makePhase32Admin();

    $this->actingAs($admin)
        ->post('/admin/webhooks', [
            'url' => 'https://ex.com/hook',
            'secret' => 'abc',
            'name' => 'My Hook',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('webhook_endpoints', [
        'url' => 'https://ex.com/hook',
    ]);
});

test('can delete webhook', function () {
    $admin = makePhase32Admin();
    $webhook = WebhookEndpoint::factory()->create();

    $this->actingAs($admin)
        ->delete("/admin/webhooks/{$webhook->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('webhook_endpoints', ['id' => $webhook->id]);
});

test('unauthenticated access redirects to login', function () {
    $this->get('/admin/profile/tokens')
        ->assertRedirect('/login');
});

test('non-admin access returns 403', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/profile/tokens')
        ->assertStatus(403);
});
