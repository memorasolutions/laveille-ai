<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Notifications\Models\EmailTemplate;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function marketingAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

function createMarketingTemplate(array $overrides = []): EmailTemplate
{
    return EmailTemplate::create(array_merge([
        'name' => 'Welcome Series',
        'slug' => 'welcome-series-'.uniqid(),
        'subject' => 'Welcome aboard!',
        'body_html' => '<p>Hello {{subscriber.name}}</p>',
        'variables' => ['subscriber.name'],
        'module' => 'newsletter',
        'is_active' => true,
    ], $overrides));
}

test('templates index loads for admin', function () {
    $user = marketingAdmin();
    createMarketingTemplate();

    $this->actingAs($user)
        ->get(route('admin.newsletter.templates.index'))
        ->assertOk()
        ->assertSee('Templates marketing')
        ->assertSee('Welcome Series');
});

test('templates index shows empty state', function () {
    $this->actingAs(marketingAdmin())
        ->get(route('admin.newsletter.templates.index'))
        ->assertOk()
        ->assertSee('Aucun template marketing');
});

test('create template page loads', function () {
    $this->actingAs(marketingAdmin())
        ->get(route('admin.newsletter.templates.create'))
        ->assertOk()
        ->assertSee('Nouveau template marketing');
});

test('store creates marketing template', function () {
    $this->actingAs(marketingAdmin())
        ->post(route('admin.newsletter.templates.store'), [
            'name' => 'Onboarding Step 1',
            'slug' => 'onboarding-step-1',
            'subject' => 'Getting started',
            'body_html' => '<p>Welcome {{subscriber.name}}</p>',
            'category' => 'onboarding',
        ])
        ->assertRedirect(route('admin.newsletter.templates.index'));

    $this->assertDatabaseHas('email_templates', [
        'slug' => 'onboarding-step-1',
        'module' => 'newsletter',
    ]);
});

test('store validates required fields', function () {
    $this->actingAs(marketingAdmin())
        ->post(route('admin.newsletter.templates.store'), [])
        ->assertSessionHasErrors(['name', 'slug', 'subject', 'body_html']);
});

test('edit template page loads', function () {
    $template = createMarketingTemplate();

    $this->actingAs(marketingAdmin())
        ->get(route('admin.newsletter.templates.edit', $template))
        ->assertOk()
        ->assertSee($template->name);
});

test('update modifies template', function () {
    $template = createMarketingTemplate();

    $this->actingAs(marketingAdmin())
        ->put(route('admin.newsletter.templates.update', $template), [
            'name' => 'Updated Name',
            'subject' => 'Updated Subject',
            'body_html' => '<p>Updated</p>',
            'is_active' => '0',
        ])
        ->assertRedirect(route('admin.newsletter.templates.index'));

    $template->refresh();
    expect($template->name)->toBe('Updated Name')
        ->and($template->is_active)->toBeFalse();
});

test('destroy deletes template', function () {
    $template = createMarketingTemplate();

    $this->actingAs(marketingAdmin())
        ->delete(route('admin.newsletter.templates.destroy', $template))
        ->assertRedirect(route('admin.newsletter.templates.index'));

    $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
});

test('preview renders template with dummy data', function () {
    $template = createMarketingTemplate([
        'body_html' => '<p>Hello {{subscriber.name}}, your email is {{subscriber.email}}</p>',
    ]);

    $this->actingAs(marketingAdmin())
        ->get(route('admin.newsletter.templates.preview', $template))
        ->assertOk()
        ->assertSee('Jean Dupont')
        ->assertSee('jean@exemple.com');
});

test('non-admin gets 403', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.newsletter.templates.index'))
        ->assertForbidden();
});
