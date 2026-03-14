<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\OnboardingStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SaaS\Models\Plan;
use Modules\SEO\Models\MetaTag;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('plans create form has form-select for currency', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.plans.create'))
        ->assertOk()
        ->assertSee('name="currency"', false)
        ->assertSee('<select', false);
});

test('plans create form shows currency options', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.plans.create'))
        ->assertOk()
        ->assertSee('CAD - Dollar canadien', false)
        ->assertSee('USD - Dollar américain', false)
        ->assertSee('EUR - Euro', false)
        ->assertSee('GBP - Livre sterling', false)
        ->assertSee('CHF - Franc suisse', false);
});

test('plans edit form has form-select for currency', function () {
    $plan = Plan::factory()->create(['currency' => 'CAD']);

    $this->actingAs($this->admin)
        ->get(route('admin.plans.edit', $plan))
        ->assertOk()
        ->assertSee('name="currency"', false)
        ->assertSee('<select', false);
});

test('plans edit form preserves selected currency', function () {
    $plan = Plan::factory()->create(['currency' => 'EUR']);

    $this->actingAs($this->admin)
        ->get(route('admin.plans.edit', $plan))
        ->assertOk()
        ->assertSee('EUR - Euro', false);
});

test('plans create does not have text input for currency', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.plans.create'))
        ->assertOk()
        ->assertDontSee('maxlength="3"', false);
});

test('seo create form has form-select for robots', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.seo.create'))
        ->assertOk()
        ->assertSee('name="robots"', false)
        ->assertSee('<select', false);
});

test('seo create form shows 6 robots directives', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.seo.create'))
        ->assertOk();

    $directives = [
        'index, follow',
        'noindex, follow',
        'index, nofollow',
        'noindex, nofollow',
        'noarchive',
        'nosnippet',
    ];

    foreach ($directives as $directive) {
        $response->assertSee($directive, false);
    }
});

test('seo edit form has form-select for robots', function () {
    $metaTag = MetaTag::factory()->create(['robots' => 'index, follow']);

    $this->actingAs($this->admin)
        ->get(route('admin.seo.edit', $metaTag))
        ->assertOk()
        ->assertSee('name="robots"', false)
        ->assertSee('<select', false);
});

test('seo edit form preserves selected robots value', function () {
    $metaTag = MetaTag::factory()->create(['robots' => 'noindex, nofollow']);

    $this->actingAs($this->admin)
        ->get(route('admin.seo.edit', $metaTag))
        ->assertOk()
        ->assertSee('noindex, nofollow', false);
});

test('seo create does not have text input for robots', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.seo.create'))
        ->assertOk()
        ->assertDontSee('maxlength="100"', false);
});

test('onboarding steps edit form has form-select for icon', function () {
    $step = OnboardingStep::create([
        'slug' => 'test-step',
        'title' => 'Test Step',
        'icon' => 'solar:star-outline',
        'order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.onboarding-steps.edit', $step))
        ->assertOk()
        ->assertSee('name="icon"', false)
        ->assertSee('<select', false);
});

test('onboarding steps edit form shows Iconify icon options', function () {
    $step = OnboardingStep::create([
        'slug' => 'test-step-2',
        'title' => 'Test Step 2',
        'icon' => 'solar:star-outline',
        'order' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.onboarding-steps.edit', $step))
        ->assertOk()
        ->assertSee('solar:star-outline', false)
        ->assertSee('solar:check-circle-outline', false)
        ->assertSee('solar:user-check-outline', false)
        ->assertSee('solar:shield-check-outline', false);
});
