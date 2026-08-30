<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * Tests Phase 2 du scroll infini admin :
 * vérifie que les 6 nouveaux composants Livewire chargent et répondent à loadMore().
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\BlockedIpsTable;
use Modules\Backoffice\Livewire\ContactMessagesTable;
use Modules\Backoffice\Livewire\LoginHistoryTable;
use Modules\Backoffice\Livewire\MailLogTable;
use Modules\Backoffice\Livewire\NotificationsTable;
use Modules\Backoffice\Livewire\TagsTable;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesPermissionsDatabaseSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findByName('super_admin', 'web'));
});

// --- MailLogTable ---

it('mail log table composant Livewire répond à loadMore', function () {
    $this->actingAs($this->admin);

    Livewire::test(MailLogTable::class)
        ->call('loadMore')
        ->assertOk();
});

// --- LoginHistoryTable ---

it('login history table composant Livewire répond à loadMore', function () {
    $this->actingAs($this->admin);

    Livewire::test(LoginHistoryTable::class)
        ->call('loadMore')
        ->assertOk();
});

// --- BlockedIpsTable ---

it('blocked ips table composant Livewire répond à loadMore', function () {
    $this->actingAs($this->admin);

    Livewire::test(BlockedIpsTable::class)
        ->call('loadMore')
        ->assertOk();
});

// --- NotificationsTable ---

it('notifications table composant Livewire répond à loadMore', function () {
    $this->actingAs($this->admin);

    Livewire::test(NotificationsTable::class)
        ->call('loadMore')
        ->assertOk();
});

// --- TagsTable ---

it('tags table composant Livewire répond à loadMore', function () {
    // Correctif 2026-08-30 : la garde ci-dessous affirmait la table 'tags' absente en SQLite
    // :memory:, mais Modules/Blog/database/migrations/2026_02_27_400001_create_tags_table.php
    // (créée le 2026-02-27) n'utilise que le Schema Builder portable (aucune syntaxe MySQL) - la
    // table existe bel et bien ici. La garde date du 2026-06-29, quatre mois APRÈS la migration :
    // elle n'a jamais été vraie sur ce dépôt et sautait ce test en silence pour de bon.
    $this->actingAs($this->admin);

    Livewire::test(TagsTable::class)
        ->call('loadMore')
        ->assertOk();
});

// --- ContactMessagesTable ---

it('contact messages table composant Livewire répond à loadMore', function () {
    $this->actingAs($this->admin);

    Livewire::test(ContactMessagesTable::class)
        ->call('loadMore')
        ->assertOk();
});

it('contact messages table filtre le statut spam', function () {
    $this->actingAs($this->admin);

    Livewire::test(ContactMessagesTable::class)
        ->set('filterStatus', 'spam')
        ->assertOk();
});

it('contact messages table réinitialise les filtres', function () {
    $this->actingAs($this->admin);

    Livewire::test(ContactMessagesTable::class)
        ->set('filterStatus', 'spam')
        ->set('search', 'test')
        ->call('resetFilters')
        ->assertSet('filterStatus', '')
        ->assertSet('search', '');
});
