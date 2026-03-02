<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\MediaTable;
use Modules\Media\Models\MediaUpload;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function mediaMetadataAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

beforeEach(function () {
    Storage::fake('public');
});

// ── API Tests ──

test('media api index returns metadata fields', function () {
    $user = mediaMetadataAdmin();

    // Upload an image first
    $this->actingAs($user)
        ->postJson('/admin/media-api', ['file' => UploadedFile::fake()->image('photo.jpg')])
        ->assertStatus(201);

    // Verify index includes metadata keys
    $response = $this->actingAs($user)->getJson('/admin/media-api');
    $response->assertOk();

    $item = $response->json('items.0');
    expect($item)->toHaveKeys(['title', 'alt_text', 'caption', 'description']);
});

test('can update media metadata via PATCH', function () {
    $user = mediaMetadataAdmin();

    $upload = $this->actingAs($user)
        ->postJson('/admin/media-api', ['file' => UploadedFile::fake()->image('photo.jpg')]);
    $mediaId = $upload->json('id');

    $response = $this->actingAs($user)->patchJson("/admin/media-api/{$mediaId}", [
        'title' => 'Coucher de soleil',
        'alt_text' => 'Photo d\'un coucher de soleil sur la mer',
        'caption' => 'Été 2026',
        'description' => 'Prise depuis la terrasse du restaurant.',
    ]);

    $response->assertOk()
        ->assertJson([
            'title' => 'Coucher de soleil',
            'alt_text' => 'Photo d\'un coucher de soleil sur la mer',
        ]);

    // Verify persisted in DB
    $media = Media::find($mediaId);
    expect($media->getCustomProperty('title'))->toBe('Coucher de soleil')
        ->and($media->getCustomProperty('alt_text'))->toBe('Photo d\'un coucher de soleil sur la mer')
        ->and($media->getCustomProperty('caption'))->toBe('Été 2026')
        ->and($media->getCustomProperty('description'))->toBe('Prise depuis la terrasse du restaurant.');
});

test('metadata validation rejects too long title', function () {
    $user = mediaMetadataAdmin();

    $upload = $this->actingAs($user)
        ->postJson('/admin/media-api', ['file' => UploadedFile::fake()->image('photo.jpg')]);

    $this->actingAs($user)
        ->patchJson('/admin/media-api/' . $upload->json('id'), [
            'title' => str_repeat('x', 256),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

test('store accepts optional metadata', function () {
    $user = mediaMetadataAdmin();

    $response = $this->actingAs($user)->postJson('/admin/media-api', [
        'file' => UploadedFile::fake()->image('photo.jpg'),
        'alt_text' => 'Texte alternatif initial',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('alt_text', 'Texte alternatif initial');

    $media = Media::find($response->json('id'));
    expect($media->getCustomProperty('alt_text'))->toBe('Texte alternatif initial');
});

test('unauthenticated users cannot update metadata', function () {
    $this->patchJson('/admin/media-api/1', ['title' => 'Test'])
        ->assertUnauthorized();
});

// ── Livewire Tests ──

test('livewire editMedia loads custom properties', function () {
    $user = mediaMetadataAdmin();
    $this->actingAs($user);

    $container = MediaUpload::firstOrCreate(['name' => 'general']);
    $media = $container->addMedia(UploadedFile::fake()->image('test.jpg'))
        ->toMediaCollection('images');
    $media->setCustomProperty('title', 'Mon titre');
    $media->setCustomProperty('alt_text', 'Mon alt');
    $media->save();

    Livewire::test(MediaTable::class)
        ->call('editMedia', $media->id)
        ->assertSet('editingMediaId', $media->id)
        ->assertSet('editTitle', 'Mon titre')
        ->assertSet('editAltText', 'Mon alt')
        ->assertSet('editCaption', '')
        ->assertSet('editDescription', '');
});

test('livewire updateMedia saves metadata', function () {
    $user = mediaMetadataAdmin();
    $this->actingAs($user);

    $container = MediaUpload::firstOrCreate(['name' => 'general']);
    $media = $container->addMedia(UploadedFile::fake()->image('test.jpg'))
        ->toMediaCollection('images');

    Livewire::test(MediaTable::class)
        ->call('editMedia', $media->id)
        ->set('editTitle', 'Nouveau titre')
        ->set('editAltText', 'Nouveau alt')
        ->set('editCaption', 'Nouvelle légende')
        ->set('editDescription', 'Nouvelle description')
        ->call('updateMedia')
        ->assertSet('editingMediaId', null)
        ->assertDispatched('toast');

    $media->refresh();
    expect($media->getCustomProperty('title'))->toBe('Nouveau titre')
        ->and($media->getCustomProperty('alt_text'))->toBe('Nouveau alt')
        ->and($media->getCustomProperty('caption'))->toBe('Nouvelle légende')
        ->and($media->getCustomProperty('description'))->toBe('Nouvelle description');
});

test('livewire cancelEdit resets state', function () {
    $user = mediaMetadataAdmin();
    $this->actingAs($user);

    $container = MediaUpload::firstOrCreate(['name' => 'general']);
    $media = $container->addMedia(UploadedFile::fake()->image('test.jpg'))
        ->toMediaCollection('images');

    Livewire::test(MediaTable::class)
        ->call('editMedia', $media->id)
        ->assertSet('editingMediaId', $media->id)
        ->call('cancelEdit')
        ->assertSet('editingMediaId', null)
        ->assertSet('editTitle', '')
        ->assertSet('editAltText', '');
});

test('alt text badge visible when alt_text set', function () {
    $user = mediaMetadataAdmin();
    $this->actingAs($user);

    $container = MediaUpload::firstOrCreate(['name' => 'general']);
    $media = $container->addMedia(UploadedFile::fake()->image('test.jpg'))
        ->toMediaCollection('images');
    $media->setCustomProperty('alt_text', 'Description image');
    $media->save();

    Livewire::test(MediaTable::class)
        ->assertSee('ALT');
});

// ── Folder Tests ──

test('can assign folder to media via PATCH', function () {
    $user = mediaMetadataAdmin();

    $upload = $this->actingAs($user)
        ->postJson('/admin/media-api', ['file' => UploadedFile::fake()->image('photo.jpg')]);

    $this->actingAs($user)->patchJson('/admin/media-api/' . $upload->json('id'), [
        'folder' => 'Logos',
    ])->assertOk()->assertJsonPath('folder', 'Logos');

    $media = Media::find($upload->json('id'));
    expect($media->getCustomProperty('folder'))->toBe('Logos');
});

test('api index returns folder field', function () {
    $user = mediaMetadataAdmin();

    $this->actingAs($user)
        ->postJson('/admin/media-api', ['file' => UploadedFile::fake()->image('photo.jpg')])
        ->assertStatus(201);

    $response = $this->actingAs($user)->getJson('/admin/media-api');
    expect($response->json('items.0'))->toHaveKey('folder');
});

test('livewire folder filter shows in dropdown', function () {
    $user = mediaMetadataAdmin();
    $this->actingAs($user);

    $container = MediaUpload::firstOrCreate(['name' => 'general']);
    $media = $container->addMedia(UploadedFile::fake()->image('test.jpg'))
        ->toMediaCollection('images');
    $media->setCustomProperty('folder', 'Photos');
    $media->save();

    Livewire::test(MediaTable::class)
        ->assertSee('Photos');
});

test('livewire updateMedia saves folder', function () {
    $user = mediaMetadataAdmin();
    $this->actingAs($user);

    $container = MediaUpload::firstOrCreate(['name' => 'general']);
    $media = $container->addMedia(UploadedFile::fake()->image('test.jpg'))
        ->toMediaCollection('images');

    Livewire::test(MediaTable::class)
        ->call('editMedia', $media->id)
        ->set('editFolder', 'Documents')
        ->call('updateMedia')
        ->assertSet('editingMediaId', null);

    $media->refresh();
    expect($media->getCustomProperty('folder'))->toBe('Documents');
});
