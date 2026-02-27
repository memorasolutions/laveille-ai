<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\MenuItem;
use Modules\Menu\Services\MenuService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

it('affiche la liste des menus', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.menus.index'))
        ->assertOk();
});

it('affiche le formulaire de création', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.menus.create'))
        ->assertOk();
});

it('crée un menu', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), [
            'name' => 'Menu principal',
            'location' => 'header',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('menus', ['name' => 'Menu principal', 'location' => 'header']);
});

it('valide le nom requis', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), ['location' => 'header'])
        ->assertSessionHasErrors('name');
});

it('valide le nom unique', function () {
    Menu::create(['name' => 'Existant', 'location' => 'header']);

    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), ['name' => 'Existant', 'location' => 'footer'])
        ->assertSessionHasErrors('name');
});

it('affiche le formulaire d\'édition', function () {
    $menu = Menu::create(['name' => 'Test', 'location' => 'header']);

    $this->actingAs($this->admin)
        ->get(route('admin.menus.edit', $menu))
        ->assertOk();
});

it('met à jour un menu', function () {
    $menu = Menu::create(['name' => 'Ancien', 'location' => 'header']);

    $this->actingAs($this->admin)
        ->put(route('admin.menus.update', $menu), ['name' => 'Nouveau', 'location' => 'footer'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('menus', ['id' => $menu->id, 'name' => 'Nouveau', 'location' => 'footer']);
});

it('supprime un menu', function () {
    $menu = Menu::create(['name' => 'A supprimer', 'location' => 'header']);

    $this->actingAs($this->admin)
        ->delete(route('admin.menus.destroy', $menu))
        ->assertRedirect();

    $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
});

it('sauvegarde les items du menu via JSON', function () {
    $menu = Menu::create(['name' => 'Test', 'location' => 'header']);

    $this->actingAs($this->admin)
        ->postJson(route('admin.menus.save-items', $menu), [
            'items' => [
                ['title' => 'Accueil', 'url' => '/', 'type' => 'custom', 'target' => '_self', 'order' => 0, 'enabled' => true],
                ['title' => 'Contact', 'url' => '/contact', 'type' => 'custom', 'target' => '_self', 'order' => 1, 'enabled' => true],
            ],
        ])
        ->assertJson(['success' => true]);

    expect($menu->allItems()->count())->toBe(2);
});

it('supprime les items retirés lors de la sauvegarde', function () {
    $menu = Menu::create(['name' => 'Test', 'location' => 'header']);
    $item1 = MenuItem::create(['menu_id' => $menu->id, 'title' => 'Garder', 'type' => 'custom', 'url' => '/', 'order' => 0]);
    MenuItem::create(['menu_id' => $menu->id, 'title' => 'Supprimer', 'type' => 'custom', 'url' => '/old', 'order' => 1]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.menus.save-items', $menu), [
            'items' => [
                ['id' => $item1->id, 'title' => 'Garder', 'url' => '/', 'type' => 'custom', 'target' => '_self', 'order' => 0, 'enabled' => true],
            ],
        ])
        ->assertJson(['success' => true]);

    expect($menu->allItems()->count())->toBe(1);
});

it('résout l\'URL d\'un item custom', function () {
    $menu = Menu::create(['name' => 'Test', 'location' => 'header']);
    $item = MenuItem::create(['menu_id' => $menu->id, 'title' => 'A propos', 'type' => 'custom', 'url' => '/about', 'order' => 0]);

    expect($item->resolveUrl())->toBe('/about');
});

it('retourne un menu par emplacement via le service', function () {
    Menu::create(['name' => 'Header', 'location' => 'header', 'is_active' => true]);

    $service = app(MenuService::class);
    $menu = $service->getByLocation('header');

    expect($menu)->not->toBeNull();
    expect($menu->name)->toBe('Header');
});

it('retourne null pour un emplacement inexistant', function () {
    $service = app(MenuService::class);
    expect($service->getByLocation('inexistant'))->toBeNull();
});

it('cascade la suppression des items avec le menu', function () {
    $menu = Menu::create(['name' => 'Test', 'location' => 'header']);
    MenuItem::create(['menu_id' => $menu->id, 'title' => 'Item 1', 'type' => 'custom', 'url' => '/', 'order' => 0]);
    MenuItem::create(['menu_id' => $menu->id, 'title' => 'Item 2', 'type' => 'custom', 'url' => '/a', 'order' => 1]);

    $menu->delete();

    expect(MenuItem::where('menu_id', $menu->id)->count())->toBe(0);
});
