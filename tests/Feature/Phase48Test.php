<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Notifications\Models\SentEmail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesPermissionsDatabaseSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findByName('super_admin', 'web'));
});

// --- RequestId Middleware ---

it('request id is added to response headers', function () {
    $response = $this->getJson('/api/v1/status');

    $response->assertHeader('X-Request-ID');
});

it('request id is propagated from incoming header', function () {
    $response = $this->withHeaders(['X-Request-ID' => 'test-uuid-123'])
        ->getJson('/api/v1/status');

    $response->assertHeader('X-Request-ID', 'test-uuid-123');
});

// --- Scheduler ---

it('scheduler admin page requires authentication', function () {
    $this->get('/admin/scheduler')->assertRedirect();
});

it('scheduler admin page loads for admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/scheduler')
        ->assertOk();
});

it('scheduler controller returns tasks in view', function () {
    $this->actingAs($this->admin)
        ->get('/admin/scheduler')
        ->assertViewHas('systemTasks')
        ->assertViewHas('customTasks');
});

// --- Mail Log ---

it('sent_emails table exists after migration', function () {
    expect(Schema::hasTable('sent_emails'))->toBeTrue();
});

it('sent email can be created', function () {
    $email = SentEmail::create([
        'to' => 'test@example.com',
        'subject' => 'Test Subject',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    expect($email->id)->toBeGreaterThan(0)
        ->and($email->to)->toBe('test@example.com');
});

it('sent email has correct columns', function () {
    expect(Schema::hasColumns('sent_emails', ['to', 'subject', 'mailable_class', 'status', 'sent_at']))
        ->toBeTrue();
});

it('mail log admin page requires authentication', function () {
    $this->get('/admin/mail-log')->assertRedirect();
});

it('mail log admin page loads for admin', function () {
    $this->actingAs($this->admin)
        ->get('/admin/mail-log')
        ->assertOk();
});

it('mail log view has emails variable', function () {
    $this->actingAs($this->admin)
        ->get('/admin/mail-log')
        ->assertViewHas('emails');
});

it('mail log shows sent emails in table', function () {
    SentEmail::create([
        'to' => 'user@test.com',
        'subject' => 'Welcome',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/mail-log')
        ->assertSee('user@test.com')
        ->assertSee('Welcome');
});
