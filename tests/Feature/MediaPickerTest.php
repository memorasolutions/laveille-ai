<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaUpload;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

// --- API Media ---

it('la route media-api.index nécessite une authentification', function () {
    $this->getJson(route('admin.media-api.index'))
        ->assertUnauthorized();
});

it('admin peut lister les médias', function () {
    $this->actingAs($this->admin)
        ->getJson(route('admin.media-api.index'))
        ->assertOk()
        ->assertJsonStructure(['items', 'meta']);
});

it('admin peut uploader une image', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.jpg', 640, 480);

    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.media-api.store'), ['file' => $file])
        ->assertStatus(201)
        ->assertJsonStructure(['id', 'url', 'file_name']);

    expect($response->json('file_name'))->toContain('photo');
});

it('la validation rejette un fichier non-image', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($this->admin)
        ->postJson(route('admin.media-api.store'), ['file' => $file])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});

it('la validation rejette un fichier trop volumineux', function () {
    $file = UploadedFile::fake()->image('huge.jpg')->size(6000);

    $this->actingAs($this->admin)
        ->postJson(route('admin.media-api.store'), ['file' => $file])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});

it('admin peut supprimer un média', function () {
    Storage::fake('public');

    $container = MediaUpload::create(['name' => 'general']);
    $media = $container->addMedia(UploadedFile::fake()->image('delete-me.jpg'))
        ->toMediaCollection('images');

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.media-api.destroy', $media->id))
        ->assertStatus(204);
});

it('la recherche filtre par nom de fichier', function () {
    Storage::fake('public');

    $container = MediaUpload::create(['name' => 'general']);
    $container->addMedia(UploadedFile::fake()->image('laravel-logo.png'))
        ->toMediaCollection('images');
    $container->addMedia(UploadedFile::fake()->image('vue-icon.png'))
        ->toMediaCollection('images');

    $this->actingAs($this->admin)
        ->getJson(route('admin.media-api.index', ['search' => 'laravel']))
        ->assertOk()
        ->assertJsonCount(1, 'items');
});

// --- Modèle MediaUpload ---

it('le modèle MediaUpload implémente HasMedia', function () {
    $upload = new MediaUpload();
    expect($upload)->toBeInstanceOf(\Spatie\MediaLibrary\HasMedia::class);
});

// --- Composant TipTap ---

it('le composant TipTap contient le bouton image', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blog.articles.create'))
        ->assertOk()
        ->assertSee('addImage()', false);
});

it('le composant TipTap contient la modale media picker', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.blog.articles.create'))
        ->assertOk()
        ->assertSee('mediaPickerOpen', false)
        ->assertSee('Sélectionner une image', false);
});
