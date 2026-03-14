<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notifications\Database\Seeders\EmailTemplateSeeder;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Notifications\Services\EmailTemplateService;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createEmailTemplate(array $overrides = []): EmailTemplate
{
    return EmailTemplate::create(array_merge([
        'name' => 'Test template',
        'slug' => 'test-'.uniqid(),
        'subject' => 'Test subject',
        'body_html' => '<p>Test body</p>',
        'variables' => ['user.name'],
        'is_active' => true,
        'module' => 'test',
    ], $overrides));
}

test('EmailTemplate model can be created', function () {
    $t = createEmailTemplate(['slug' => 'welcome-test']);

    $this->assertDatabaseHas('email_templates', ['slug' => 'welcome-test']);
});

test('EmailTemplate findBySlug returns model for existing slug', function () {
    createEmailTemplate(['slug' => 'find-me']);

    expect(EmailTemplate::findBySlug('find-me'))->not->toBeNull();
});

test('EmailTemplate findBySlug returns null for unknown slug', function () {
    expect(EmailTemplate::findBySlug('unknown'))->toBeNull();
});

test('EmailTemplate findBySlug returns null when inactive', function () {
    createEmailTemplate(['slug' => 'inactive', 'is_active' => false]);

    expect(EmailTemplate::findBySlug('inactive'))->toBeNull();
});

test('EmailTemplateService render replaces variables', function () {
    createEmailTemplate([
        'slug' => 'greeting',
        'subject' => 'Bonjour {{user.name}}',
        'body_html' => '<p>Bienvenue {{user.name}} ({{user.email}})</p>',
    ]);

    $service = app(EmailTemplateService::class);
    $rendered = $service->render('greeting', [
        'user' => ['name' => 'Jean', 'email' => 'jean@test.com'],
    ]);

    expect($rendered['subject'])->toBe('Bonjour Jean');
    expect($rendered['body_html'])->toContain('Bienvenue Jean');
    expect($rendered['body_html'])->toContain('jean@test.com');
});

test('EmailTemplateService render returns null for missing slug', function () {
    $service = app(EmailTemplateService::class);

    expect($service->render('nonexistent', []))->toBeNull();
});

test('EmailTemplateService render returns null when template inactive', function () {
    createEmailTemplate(['slug' => 'disabled', 'is_active' => false]);

    $service = app(EmailTemplateService::class);

    expect($service->render('disabled', []))->toBeNull();
});

test('admin email-templates index accessible by admin', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/email-templates')->assertOk();
});

test('admin email-templates index denied for regular user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/email-templates')->assertForbidden();
});

test('admin email-templates edit shows form', function () {
    $template = createEmailTemplate(['slug' => 'edit-test']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get("/admin/email-templates/{$template->id}/edit")
        ->assertOk()
        ->assertSee('Sujet');
});

test('admin email-templates update persists changes', function () {
    $template = createEmailTemplate(['slug' => 'update-test']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->put("/admin/email-templates/{$template->id}", [
        'subject' => 'Nouveau sujet',
        'body_html' => '<p>Nouveau contenu</p>',
        'is_active' => true,
    ])->assertRedirect();

    expect($template->fresh()->subject)->toBe('Nouveau sujet');
});

test('admin email-templates update validates subject required', function () {
    $template = createEmailTemplate();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->put("/admin/email-templates/{$template->id}", [
        'subject' => '',
        'body_html' => '<p>Content</p>',
    ])->assertSessionHasErrors('subject');
});

test('admin email-templates preview returns HTML', function () {
    $template = createEmailTemplate([
        'slug' => 'preview-test',
        'body_html' => '<p>Bonjour {{user.name}}</p>',
    ]);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get("/admin/email-templates/{$template->id}/preview")
        ->assertOk()
        ->assertSee('Bonjour Jean Dupont');
});

test('admin email-templates reset restores defaults', function () {
    (new EmailTemplateSeeder)->run();
    $template = EmailTemplate::where('slug', 'welcome')->first();
    $originalSubject = $template->subject;

    $template->update(['subject' => 'Modifié par admin']);

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post("/admin/email-templates/{$template->id}/reset")
        ->assertRedirect();

    expect($template->fresh()->subject)->toBe($originalSubject);
});

test('EmailTemplateSeeder seeds 6 templates', function () {
    (new EmailTemplateSeeder)->run();

    expect(EmailTemplate::count())->toBe(6);
});
