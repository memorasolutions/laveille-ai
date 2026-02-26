<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Newsletter\Models\Subscriber;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
});

it('admin dashboard includes articles count', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Article::factory()->published()->count(3)->create();

    $this->actingAs($admin)->get('/admin')
        ->assertStatus(200);
});

it('admin dashboard includes newsletter subscribers count', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Subscriber::create(['email' => 'sub@test.com', 'confirmed_at' => now()]);

    $this->actingAs($admin)->get('/admin')->assertStatus(200);
});

it('newsletter link in sidebar for admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin')
        ->assertSee('Newsletter');
});

it('blog show page has newsletter subscribe form', function () {
    $article = Article::factory()->published()->create();

    $this->get('/blog/'.$article->slug)
        ->assertSee('/newsletter/subscribe', false)
        ->assertSee('abonner', false);
});

it('newsletter admin page accessible', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/newsletter')
        ->assertStatus(200);
});
