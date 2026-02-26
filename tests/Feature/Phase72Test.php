<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\FeatureFlagsTable;
use Modules\Backoffice\Livewire\PlansTable;
use Modules\SaaS\Models\Plan;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_active' => true]);
    $this->admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    $this->actingAs($this->admin);
});

// === PLANS CRUD ===

test('plans - admin voit la liste', function () {
    $this->get(route('admin.plans.index'))->assertOk();
});

test('plans - admin voit le formulaire de création', function () {
    $this->get(route('admin.plans.create'))->assertOk();
});

test('plans - admin peut créer un plan', function () {
    $this->post(route('admin.plans.store'), [
        'name' => 'Plan Pro',
        'slug' => 'plan-pro',
        'price' => 29.99,
        'currency' => 'USD',
        'interval' => 'monthly',
        'trial_days' => 14,
        'is_active' => true,
        'sort_order' => 1,
    ])->assertRedirect(route('admin.plans.index'));

    $this->assertDatabaseHas('plans', ['slug' => 'plan-pro', 'name' => 'Plan Pro']);
});

test('plans - admin voit le formulaire d\'édition', function () {
    $plan = Plan::factory()->create();

    $this->get(route('admin.plans.edit', $plan))->assertOk();
});

test('plans - admin peut modifier un plan', function () {
    $plan = Plan::factory()->create(['name' => 'Ancien Nom', 'slug' => 'ancien-nom']);

    $this->put(route('admin.plans.update', $plan), [
        'name' => 'Nouveau Nom',
        'slug' => 'nouveau-nom',
        'price' => 49.99,
        'currency' => 'EUR',
        'interval' => 'yearly',
        'is_active' => false,
    ])->assertRedirect(route('admin.plans.index'));

    $this->assertDatabaseHas('plans', ['id' => $plan->id, 'slug' => 'nouveau-nom', 'name' => 'Nouveau Nom']);
});

test('plans - admin peut supprimer un plan', function () {
    $plan = Plan::factory()->create();

    $this->delete(route('admin.plans.destroy', $plan))
        ->assertRedirect(route('admin.plans.index'));

    $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
});

test('plans - user non-admin est refusé (403)', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('admin.plans.index'))
        ->assertForbidden();
});

// === PLANS TABLE LIVEWIRE ===

test('plans table - monte sans erreur', function () {
    Plan::factory()->count(3)->create();

    Livewire::test(PlansTable::class)->assertOk();
});

test('plans table - filtre par recherche (search)', function () {
    Plan::factory()->create(['name' => 'Plan Premium', 'slug' => 'plan-premium']);
    Plan::factory()->create(['name' => 'Plan Basique', 'slug' => 'plan-basique']);

    Livewire::test(PlansTable::class)
        ->set('search', 'Premium')
        ->assertSee('Plan Premium')
        ->assertDontSee('Plan Basique');
});

test('plans table - filtre par interval (filterInterval)', function () {
    Plan::factory()->monthly()->create(['name' => 'Plan Mensuel', 'slug' => 'plan-mensuel']);
    Plan::factory()->yearly()->create(['name' => 'Plan Annuel', 'slug' => 'plan-annuel']);

    Livewire::test(PlansTable::class)
        ->set('filterInterval', 'monthly')
        ->assertSet('filterInterval', 'monthly')
        ->assertSee('Plan Mensuel')
        ->assertDontSee('Plan Annuel');
});

test('plans table - resetFilters remet tout à zéro', function () {
    Livewire::test(PlansTable::class)
        ->set('search', 'test')
        ->set('filterInterval', 'yearly')
        ->set('filterActive', '1')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('filterInterval', '')
        ->assertSet('filterActive', '');
});

test('plans table - sort() change sortBy et inverse sortDirection', function () {
    Livewire::test(PlansTable::class)
        ->call('sort', 'name')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortDirection', 'desc');
});

// === FEATURE FLAGS ===

test('feature flags - admin voit la page', function () {
    $this->get(route('admin.feature-flags.index'))->assertOk();
});

test('feature flags - toggle active une feature inexistante (insert, scope=null, value=true)', function () {
    $this->post(route('admin.feature-flags.toggle', 'test-feature'))
        ->assertRedirect();

    $this->assertDatabaseHas('features', [
        'name' => 'test-feature',
        'scope' => 'global',
        'value' => 'true',
    ]);
});

test('feature flags - toggle désactive une feature déjà active (value devient false)', function () {
    DB::table('features')->insert([
        'name' => 'test-feature',
        'scope' => 'global',
        'value' => 'true',
    ]);

    $this->post(route('admin.feature-flags.toggle', 'test-feature'))
        ->assertRedirect();

    $this->assertDatabaseHas('features', [
        'name' => 'test-feature',
        'scope' => 'global',
        'value' => 'false',
    ]);
});

// === FEATURE FLAGS TABLE LIVEWIRE ===

test('feature flags table - knownFeatures contient les 10 features attendues', function () {
    $component = Livewire::test(FeatureFlagsTable::class);

    $component->assertSet('knownFeatures', function ($value) {
        return count($value) === 10
            && in_array('module-blog', $value)
            && in_array('two-factor-auth', $value)
            && in_array('export-csv', $value);
    });
});
