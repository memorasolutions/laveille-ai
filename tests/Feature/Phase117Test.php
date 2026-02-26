<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\MediaTable;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->user = User::factory()->create();
});

it('media page is accessible for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.media.index'))
        ->assertOk();
});

it('media page redirects guests', function () {
    $this->get(route('admin.media.index'))
        ->assertRedirect();
});

it('media page is forbidden for non-admin', function () {
    $this->actingAs($this->user)
        ->get(route('admin.media.index'))
        ->assertForbidden();
});

it('admin can upload file via livewire', function () {
    Storage::fake('local');
    $this->actingAs($this->admin);
    Livewire::test(MediaTable::class)
        ->set('file', UploadedFile::fake()->image('photo.jpg', 100, 100))
        ->call('upload')
        ->assertDispatched('$refresh');
});

it('upload validates file is required', function () {
    $this->actingAs($this->admin);
    Livewire::test(MediaTable::class)
        ->set('file', null)
        ->call('upload')
        ->assertHasErrors(['file']);
});

it('upload validates max file size', function () {
    $this->actingAs($this->admin);
    Livewire::test(MediaTable::class)
        ->set('file', UploadedFile::fake()->image('large.jpg')->size(11000))
        ->call('upload')
        ->assertHasErrors(['file']);
});

it('admin can delete media via livewire', function () {
    Storage::fake('local');
    $this->actingAs($this->admin);

    $tmpPath = tempnam(sys_get_temp_dir(), 'media').'.jpg';
    file_put_contents($tmpPath, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));

    $media = $this->admin->addMedia($tmpPath)
        ->usingFileName('test.jpg')
        ->toMediaCollection('gallery');

    Livewire::test(MediaTable::class)
        ->call('deleteMedia', $media->id)
        ->assertDispatched('$refresh');

    expect(\Spatie\MediaLibrary\MediaCollections\Models\Media::find($media->id))->toBeNull();
});

it('media page shows upload form', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.media.index'))
        ->assertSee('Uploader');
});

it('upload route exists', function () {
    expect(route('admin.media.index'))->toBeString();
});
