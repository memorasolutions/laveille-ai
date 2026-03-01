<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\FormBuilder\Models\Form;
use Modules\FormBuilder\Models\FormField;
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

test('published form is accessible publicly', function () {
    $form = Form::create([
        'title' => 'Public',
        'slug' => 'public-form',
        'is_published' => true,
    ]);

    FormField::create([
        'form_id' => $form->id,
        'type' => 'text',
        'label' => 'Nom',
        'name' => 'nom',
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $this->get(route('formbuilder.show', $form))->assertStatus(200);
});

test('unpublished form returns 404', function () {
    $form = Form::create([
        'title' => 'Brouillon',
        'slug' => 'brouillon',
        'is_published' => false,
    ]);

    $this->get(route('formbuilder.show', $form))->assertStatus(404);
});

test('visitor can submit a form', function () {
    $form = Form::create([
        'title' => 'Contact',
        'slug' => 'contact',
        'is_published' => true,
    ]);

    FormField::create([
        'form_id' => $form->id,
        'type' => 'text',
        'label' => 'Nom',
        'name' => 'nom',
        'is_required' => true,
        'sort_order' => 1,
    ]);

    FormField::create([
        'form_id' => $form->id,
        'type' => 'email',
        'label' => 'Email',
        'name' => 'email',
        'is_required' => true,
        'sort_order' => 2,
    ]);

    $response = $this->post(route('formbuilder.submit', $form), [
        'fields' => [
            'nom' => 'Jean Dupont',
            'email' => 'jean@example.com',
        ],
        '_honeypot' => '',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('form_submissions', ['form_id' => $form->id]);
});

test('honeypot blocks bots', function () {
    $form = Form::create([
        'title' => 'Protégé',
        'slug' => 'protege',
        'is_published' => true,
    ]);

    FormField::create([
        'form_id' => $form->id,
        'type' => 'text',
        'label' => 'Nom',
        'name' => 'nom',
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $response = $this->post(route('formbuilder.submit', $form), [
        'fields' => ['nom' => 'Bot'],
        '_honeypot' => 'spam-value',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('form_submissions', 0);
});

test('form validation works', function () {
    $form = Form::create([
        'title' => 'Validation',
        'slug' => 'validation',
        'is_published' => true,
    ]);

    FormField::create([
        'form_id' => $form->id,
        'type' => 'text',
        'label' => 'Champ requis',
        'name' => 'requis',
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $response = $this->post(route('formbuilder.submit', $form), [
        'fields' => ['requis' => ''],
        '_honeypot' => '',
    ]);

    $response->assertSessionHasErrors('fields.requis');
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
