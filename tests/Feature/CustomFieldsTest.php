<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\CustomFields\Models\CustomFieldDefinition;
use Modules\CustomFields\Models\CustomFieldValue;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('redirects guest to login', function () {
    $this->get(route('admin.custom-fields.index'))
        ->assertRedirect(route('login'));
});

it('forbids editor from accessing custom fields', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)
        ->get(route('admin.custom-fields.index'))
        ->assertForbidden();
});

it('allows admin to view index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.custom-fields.index'))
        ->assertOk()
        ->assertSee('Champs personnalisés');
});

it('allows admin to view create form', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.custom-fields.create'))
        ->assertOk()
        ->assertSee('Nouveau champ');
});

it('allows admin to store a definition', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.custom-fields.store'), [
            'name' => 'Couleur primaire',
            'type' => 'color',
            'model_type' => 'article',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.custom-fields.index'));

    $this->assertDatabaseHas('custom_field_definitions', [
        'name' => 'Couleur primaire',
        'type' => 'color',
        'model_type' => 'article',
    ]);
});

it('allows admin to edit a definition', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $def = CustomFieldDefinition::create([
        'name' => 'Mon champ',
        'key' => 'mon_champ',
        'type' => 'text',
        'model_type' => 'article',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.custom-fields.edit', $def))
        ->assertOk()
        ->assertSee('Mon champ');
});

it('allows admin to update a definition', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $def = CustomFieldDefinition::create([
        'name' => 'Original',
        'key' => 'original',
        'type' => 'text',
        'model_type' => 'article',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.custom-fields.update', $def), [
            'name' => 'Updated',
            'key' => 'original',
            'type' => 'number',
            'model_type' => 'article',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.custom-fields.index'));

    $this->assertDatabaseHas('custom_field_definitions', [
        'id' => $def->id,
        'name' => 'Updated',
        'type' => 'number',
    ]);
});

it('allows admin to delete a definition', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $def = CustomFieldDefinition::create([
        'name' => 'To Delete',
        'key' => 'to_delete',
        'type' => 'text',
        'model_type' => 'article',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.custom-fields.destroy', $def))
        ->assertRedirect(route('admin.custom-fields.index'));

    $this->assertDatabaseMissing('custom_field_definitions', ['id' => $def->id]);
});

it('validates required fields on store', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.custom-fields.store'), [])
        ->assertSessionHasErrors(['name', 'type', 'model_type']);
});

it('auto-generates key from name', function () {
    $def = CustomFieldDefinition::create([
        'name' => 'Test Field Name',
        'type' => 'text',
        'model_type' => 'article',
        'is_active' => true,
    ]);

    expect($def->key)->toBe('test_field_name');
});

it('generates validation rules correctly', function () {
    $def = CustomFieldDefinition::create([
        'name' => 'Price',
        'key' => 'price',
        'type' => 'number',
        'model_type' => 'article',
        'is_required' => true,
        'is_active' => true,
        'validation_rules' => 'min:0|max:100',
    ]);

    $rules = $def->getValidationRule();

    expect($rules)->toContain('required')
        ->toContain('numeric')
        ->toContain('min:0')
        ->toContain('max:100');
});

it('stores options as array from comma-separated string', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.custom-fields.store'), [
            'name' => 'Couleur',
            'type' => 'select',
            'model_type' => 'article',
            'is_active' => '1',
            'options' => 'Rouge, Vert, Bleu',
        ]);

    $def = CustomFieldDefinition::where('key', 'couleur')->first();
    expect($def->options)->toBeArray()->toHaveCount(3);
    expect($def->options)->toBe(['Rouge', 'Vert', 'Bleu']);
});

it('casts values correctly by type', function () {
    $numDef = CustomFieldDefinition::create([
        'name' => 'Nombre', 'key' => 'nombre', 'type' => 'number',
        'model_type' => 'article', 'is_active' => true,
    ]);

    $numVal = CustomFieldValue::create([
        'custom_field_definition_id' => $numDef->id,
        'fieldable_type' => Article::class,
        'fieldable_id' => 1,
        'value' => '42.5',
    ]);
    expect($numVal->getCastedValue())->toBe(42.5);

    $cbDef = CustomFieldDefinition::create([
        'name' => 'Actif', 'key' => 'actif', 'type' => 'checkbox',
        'model_type' => 'article', 'is_active' => true,
    ]);

    $cbVal = CustomFieldValue::create([
        'custom_field_definition_id' => $cbDef->id,
        'fieldable_type' => Article::class,
        'fieldable_id' => 1,
        'value' => '1',
    ]);
    expect($cbVal->getCastedValue())->toBeTrue();

    $dateDef = CustomFieldDefinition::create([
        'name' => 'Date', 'key' => 'date_evt', 'type' => 'date',
        'model_type' => 'article', 'is_active' => true,
    ]);

    $dateVal = CustomFieldValue::create([
        'custom_field_definition_id' => $dateDef->id,
        'fieldable_type' => Article::class,
        'fieldable_id' => 1,
        'value' => '2026-03-01',
    ]);
    expect($dateVal->getCastedValue())->toBeInstanceOf(\Carbon\Carbon::class);
    expect($dateVal->getCastedValue()->format('Y-m-d'))->toBe('2026-03-01');
});

it('allows setting and getting custom fields via trait', function () {
    $def = CustomFieldDefinition::create([
        'name' => 'Couleur', 'key' => 'couleur_trait', 'type' => 'text',
        'model_type' => 'article', 'is_active' => true,
    ]);

    $article = Article::factory()->create();
    $article->setCustomField('couleur_trait', 'bleu');

    expect($article->getCustomField('couleur_trait'))->toBe('bleu');

    $this->assertDatabaseHas('custom_field_values', [
        'custom_field_definition_id' => $def->id,
        'fieldable_type' => Article::class,
        'fieldable_id' => $article->id,
        'value' => 'bleu',
    ]);
});

it('stores repeater value as JSON via trait', function () {
    $def = CustomFieldDefinition::create([
        'name' => 'Contacts', 'key' => 'contacts_rep', 'type' => 'repeater',
        'options' => ['Nom', 'Email'],
        'model_type' => 'article', 'is_active' => true,
    ]);

    $article = Article::factory()->create();
    $repeaterData = [
        ['Nom' => 'John Doe', 'Email' => 'john@example.com'],
        ['Nom' => 'Jane Smith', 'Email' => 'jane@example.com'],
    ];

    $article->setCustomField('contacts_rep', $repeaterData);

    $this->assertDatabaseHas('custom_field_values', [
        'custom_field_definition_id' => $def->id,
        'fieldable_type' => Article::class,
        'fieldable_id' => $article->id,
    ]);

    $stored = CustomFieldValue::where('custom_field_definition_id', $def->id)
        ->where('fieldable_id', $article->id)->first();
    expect(json_decode($stored->value, true))->toBe($repeaterData);
});

it('casts repeater value to array', function () {
    $def = CustomFieldDefinition::create([
        'name' => 'Liens', 'key' => 'liens_rep', 'type' => 'repeater',
        'options' => ['Nom', 'URL'],
        'model_type' => 'article', 'is_active' => true,
    ]);

    $data = [['Nom' => 'Google', 'URL' => 'https://google.com']];
    $val = CustomFieldValue::create([
        'custom_field_definition_id' => $def->id,
        'fieldable_type' => Article::class,
        'fieldable_id' => 1,
        'value' => json_encode($data),
    ]);

    expect($val->getCastedValue())->toBeArray()->toBe($data);
});

it('validates repeater type as json', function () {
    $def = CustomFieldDefinition::create([
        'name' => 'Repeater', 'key' => 'rep_valid', 'type' => 'repeater',
        'model_type' => 'article', 'is_active' => true,
    ]);

    expect($def->getValidationRule())->toContain('json');
});
