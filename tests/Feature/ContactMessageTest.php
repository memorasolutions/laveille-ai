<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

// ── Admin : index ──

it('affiche la liste des messages en admin', function () {
    ContactMessage::create(['name' => 'Visiteur', 'email' => 'v@test.com', 'subject' => 'Sujet', 'message' => 'Contenu du message.']);

    $this->actingAs($this->admin)
        ->get(route('admin.contact-messages.index'))
        ->assertOk()
        ->assertSee('Visiteur')
        ->assertSee('v@test.com');
});

it('affiche le badge non lus', function () {
    ContactMessage::create(['name' => 'A', 'email' => 'a@test.com', 'subject' => 'S', 'message' => 'M', 'status' => 'new']);
    ContactMessage::create(['name' => 'B', 'email' => 'b@test.com', 'subject' => 'S', 'message' => 'M', 'status' => 'read']);

    $this->actingAs($this->admin)
        ->get(route('admin.contact-messages.index'))
        ->assertOk()
        ->assertSee('1 non lu');
});

it('filtre par statut', function () {
    ContactMessage::create(['name' => 'Nouveau', 'email' => 'n@test.com', 'subject' => 'S', 'message' => 'M', 'status' => 'new']);
    ContactMessage::create(['name' => 'Lu', 'email' => 'l@test.com', 'subject' => 'S', 'message' => 'M', 'status' => 'read']);

    $this->actingAs($this->admin)
        ->get(route('admin.contact-messages.index', ['status' => 'new']))
        ->assertOk()
        ->assertSee('Nouveau')
        ->assertDontSee('l@test.com');
});

it('recherche par nom ou email', function () {
    ContactMessage::create(['name' => 'Alice Martin', 'email' => 'alice@test.com', 'subject' => 'S', 'message' => 'M']);
    ContactMessage::create(['name' => 'Bob Dupont', 'email' => 'bob@test.com', 'subject' => 'S', 'message' => 'M']);

    $this->actingAs($this->admin)
        ->get(route('admin.contact-messages.index', ['search' => 'Alice']))
        ->assertOk()
        ->assertSee('Alice Martin')
        ->assertDontSee('Bob Dupont');
});

// ── Admin : show ──

it('affiche le détail d\'un message', function () {
    $msg = ContactMessage::create(['name' => 'Detail', 'email' => 'd@test.com', 'subject' => 'Sujet détail', 'message' => 'Message complet ici.']);

    $this->actingAs($this->admin)
        ->get(route('admin.contact-messages.show', $msg))
        ->assertOk()
        ->assertSee('Sujet détail')
        ->assertSee('Message complet ici.');
});

it('marque le message comme lu à l\'ouverture', function () {
    $msg = ContactMessage::create(['name' => 'A lire', 'email' => 'r@test.com', 'subject' => 'S', 'message' => 'M', 'status' => 'new']);

    $this->actingAs($this->admin)
        ->get(route('admin.contact-messages.show', $msg));

    $msg->refresh();
    expect($msg->status)->toBe('read');
    expect($msg->read_at)->not->toBeNull();
});

// ── Admin : destroy ──

it('supprime un message', function () {
    $msg = ContactMessage::create(['name' => 'À supprimer', 'email' => 's@test.com', 'subject' => 'S', 'message' => 'M']);

    $this->actingAs($this->admin)
        ->delete(route('admin.contact-messages.destroy', $msg))
        ->assertRedirect(route('admin.contact-messages.index'));

    $this->assertDatabaseMissing('contact_messages', ['id' => $msg->id]);
});

// ── Modèle ──

it('scope unread retourne les messages non lus', function () {
    ContactMessage::create(['name' => 'A', 'email' => 'a@test.com', 'subject' => 'S', 'message' => 'M', 'status' => 'new']);
    ContactMessage::create(['name' => 'B', 'email' => 'b@test.com', 'subject' => 'S', 'message' => 'M', 'status' => 'read']);

    expect(ContactMessage::unread()->count())->toBe(1);
});

it('markAsRead ne change pas un message déjà lu', function () {
    $msg = ContactMessage::create(['name' => 'A', 'email' => 'a@test.com', 'subject' => 'S', 'message' => 'M', 'status' => 'read', 'read_at' => now()->subHour()]);
    $originalReadAt = $msg->read_at->toDateTimeString();

    $msg->markAsRead();
    $msg->refresh();

    expect($msg->read_at->toDateTimeString())->toBe($originalReadAt);
});

// ── Sécurité ──

it('refuse l\'accès admin sans permission', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.contact-messages.index'))
        ->assertStatus(403);
});
