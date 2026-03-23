<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AI\Adapters\EmailChannelAdapter;
use Modules\AI\Models\Channel;
use Modules\AI\Models\ChannelMessage;
use Modules\AI\Models\Ticket;
use Modules\AI\Services\ChannelRegistry;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

// --- Channels CRUD ---

it('guest ne peut pas accéder aux canaux', function () {
    $this->get(route('admin.ai.channels.index'))
        ->assertRedirect(route('login'));
});

it('user reçoit 403 sur canaux', function () {
    $this->actingAs($this->user)
        ->get(route('admin.ai.channels.index'))
        ->assertForbidden();
});

it('admin peut lister les canaux', function () {
    Channel::factory()->count(2)->create();
    $this->actingAs($this->admin)
        ->get(route('admin.ai.channels.index'))
        ->assertOk();
});

it('admin peut créer un canal', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.ai.channels.store'), [
            'name' => 'Support email',
            'type' => 'email',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('channels', [
        'name' => 'Support email',
        'type' => 'email',
    ]);
});

it('admin peut modifier un canal', function () {
    $channel = Channel::factory()->create(['name' => 'Ancien', 'type' => 'email']);

    $this->actingAs($this->admin)
        ->put(route('admin.ai.channels.update', $channel), [
            'name' => 'Nouveau nom',
            'type' => 'email',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('channels', ['id' => $channel->id, 'name' => 'Nouveau nom']);
});

it('admin peut activer/désactiver un canal', function () {
    $channel = Channel::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)
        ->patch(route('admin.ai.channels.toggle', $channel))
        ->assertRedirect();

    expect($channel->fresh()->is_active)->toBeFalse();
});

it('admin peut supprimer un canal', function () {
    $channel = Channel::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.ai.channels.destroy', $channel))
        ->assertRedirect();

    $this->assertDatabaseMissing('channels', ['id' => $channel->id]);
});

// --- Unified Inbox ---

it('admin peut lister la boîte de réception', function () {
    ChannelMessage::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.ai.inbox.index'))
        ->assertOk();
});

it('admin peut filtrer par canal', function () {
    $channelA = Channel::factory()->create();
    $channelB = Channel::factory()->create();
    ChannelMessage::factory()->count(2)->create(['channel_id' => $channelA->id]);
    ChannelMessage::factory()->create(['channel_id' => $channelB->id]);

    $this->actingAs($this->admin)
        ->get(route('admin.ai.inbox.index', ['channel_id' => $channelA->id]))
        ->assertOk();
});

it('admin peut lier un message à un ticket', function () {
    $message = ChannelMessage::factory()->create(['ticket_id' => null]);
    $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.ai.inbox.link', $message), [
            'ticket_id' => $ticket->id,
        ])
        ->assertRedirect();

    expect($message->fresh()->ticket_id)->toBe($ticket->id);
});

// --- ChannelRegistry ---

it('ChannelRegistry résout EmailChannelAdapter pour type email', function () {
    $channel = Channel::factory()->create(['type' => 'email']);
    $registry = app(ChannelRegistry::class);
    $adapter = $registry->adapterFor($channel);

    expect($adapter)->toBeInstanceOf(EmailChannelAdapter::class);
});

it('ChannelRegistry lance une exception pour type inconnu', function () {
    $channel = Channel::factory()->create(['type' => 'unknown']);
    $registry = app(ChannelRegistry::class);

    expect(fn () => $registry->adapterFor($channel))
        ->toThrow(\InvalidArgumentException::class);
});

// --- EmailChannelAdapter ---

it('EmailChannelAdapter crée un ticket depuis email entrant', function () {
    $channel = Channel::factory()->create(['type' => 'email']);
    $adapter = app(EmailChannelAdapter::class);

    $channelMessage = $adapter->receive([
        'from' => 'client@example.com',
        'subject' => 'Problème urgent',
        'body' => 'J\'ai un souci.',
        'message_id' => '<unique1@host>',
    ], $channel);

    expect($channelMessage)->toBeInstanceOf(ChannelMessage::class);
    expect($channelMessage->ticket_id)->not()->toBeNull();
    $this->assertDatabaseHas('tickets', ['title' => 'Problème urgent']);
});

it('EmailChannelAdapter lie au ticket existant pour "Re: Ticket #XX"', function () {
    $channel = Channel::factory()->create(['type' => 'email']);
    $ticket = Ticket::factory()->create(['user_id' => $this->admin->id]);
    $adapter = app(EmailChannelAdapter::class);

    $channelMessage = $adapter->receive([
        'from' => 'reply@example.com',
        'subject' => "Re: Ticket #{$ticket->id}",
        'body' => 'Voici plus d\'infos.',
        'message_id' => '<unique2@host>',
    ], $channel);

    expect($channelMessage->ticket_id)->toBe($ticket->id);
});

// --- Email Webhook ---

it('le webhook email crée un channel message', function () {
    $channel = Channel::factory()->create([
        'type' => 'email',
        'inbound_secret' => 'test-secret-123',
    ]);

    $this->postJson(route('ai.webhooks.email', 'test-secret-123'), [
        'from' => 'incoming@example.com',
        'subject' => 'Test webhook',
        'body' => 'Contenu du message',
        'message_id' => '<webhook@id>',
    ])->assertOk();

    $this->assertDatabaseHas('channel_messages', [
        'channel_id' => $channel->id,
        'external_id' => '<webhook@id>',
    ]);
});

it('le webhook email retourne 404 pour secret invalide', function () {
    $this->postJson(route('ai.webhooks.email', 'invalid-secret'), [
        'from' => 'someone@example.com',
        'subject' => 'Test',
        'body' => 'test',
        'message_id' => '<test@id>',
    ])->assertNotFound();
});
