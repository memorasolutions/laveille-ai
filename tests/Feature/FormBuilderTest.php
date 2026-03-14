<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FormBuilder\Models\Form;
use Modules\FormBuilder\Models\FormSubmission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

test('guest cannot access form admin', function () {
    $response = $this->get(route('admin.formbuilder.forms.index'));
    $response->assertRedirect(route('login'));
});

test('admin can view forms list', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get(route('admin.formbuilder.forms.index'))
        ->assertStatus(200);
});

test('admin can create a form', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->post(route('admin.formbuilder.forms.store'), [
        'title' => 'Formulaire de contact',
        'description' => 'Description test',
        'is_published' => 1,
    ]);

    $response->assertRedirect(route('admin.formbuilder.forms.index'));
    $this->assertDatabaseHas('forms', ['slug' => 'formulaire-de-contact']);
});

test('admin can update form with fields', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $form = Form::create([
        'title' => 'Test',
        'slug' => 'test',
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)->put(route('admin.formbuilder.forms.update', $form), [
        'title' => 'Test modifié',
        'slug' => 'test',
        'is_published' => 1,
        'fields' => [
            [
                'label' => 'Nom',
                'name' => 'nom',
                'type' => 'text',
                'is_required' => 1,
                'sort_order' => 1,
                'options' => '',
            ],
            [
                'label' => 'Email',
                'name' => 'email',
                'type' => 'email',
                'is_required' => 1,
                'sort_order' => 2,
                'options' => '',
            ],
        ],
    ]);

    $response->assertRedirect();
    expect($form->fields()->count())->toBe(2);
    $this->assertDatabaseHas('form_fields', ['name' => 'nom', 'form_id' => $form->id]);
    $this->assertDatabaseHas('form_fields', ['name' => 'email', 'form_id' => $form->id]);
});

test('admin can delete a form', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $form = Form::create([
        'title' => 'À supprimer',
        'slug' => 'a-supprimer',
        'is_published' => false,
    ]);

    $this->actingAs($user)
        ->delete(route('admin.formbuilder.forms.destroy', $form))
        ->assertRedirect();

    $this->assertDatabaseMissing('forms', ['id' => $form->id]);
});

test('admin can view submissions', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $form = Form::create([
        'title' => 'Soumissions',
        'slug' => 'soumissions',
        'is_published' => true,
    ]);

    $this->actingAs($user)
        ->get(route('admin.formbuilder.forms.submissions.index', $form))
        ->assertStatus(200);
});

test('admin can export CSV', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $form = Form::create([
        'title' => 'Export',
        'slug' => 'export',
        'is_published' => true,
    ]);

    FormSubmission::create([
        'form_id' => $form->id,
        'data' => ['nom' => 'Test'],
        'ip_address' => '127.0.0.1',
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.formbuilder.forms.submissions.export', $form));

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
});

test('submission is marked as read on show', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $form = Form::create([
        'title' => 'Lecture',
        'slug' => 'lecture',
        'is_published' => true,
    ]);

    $submission = FormSubmission::create([
        'form_id' => $form->id,
        'data' => ['nom' => 'Test'],
        'ip_address' => '127.0.0.1',
    ]);

    expect($submission->read_at)->toBeNull();

    $this->actingAs($user)
        ->get(route('admin.formbuilder.forms.submissions.show', [$form, $submission]))
        ->assertStatus(200);

    $submission->refresh();
    expect($submission->read_at)->not->toBeNull();
});
