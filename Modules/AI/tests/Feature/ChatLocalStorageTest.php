<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\AI\Livewire\ChatBot;
use Modules\Settings\Models\Setting;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('restaure les messages depuis localStorage pour un guest', function () {
    Setting::set('ai.chatbot_enabled', true);

    $stored = [
        ['role' => 'user', 'content' => 'Bonjour'],
        ['role' => 'assistant', 'content' => 'Comment puis-je vous aider ?'],
    ];

    Livewire::test(ChatBot::class)
        ->call('restoreMessages', $stored)
        ->assertSet('messages', $stored);
});

it('ne restaure pas pour un utilisateur connecté', function () {
    Setting::set('ai.chatbot_enabled', true);
    $user = \App\Models\User::factory()->create();

    Livewire::actingAs($user)
        ->test(ChatBot::class)
        ->call('restoreMessages', [
            ['role' => 'user', 'content' => 'Test'],
        ])
        ->assertSet('messages', []);
});

it('ne restaure pas si des messages existent déjà', function () {
    Setting::set('ai.chatbot_enabled', true);

    Livewire::test(ChatBot::class)
        ->call('startLeadCapture')
        ->call('restoreMessages', [
            ['role' => 'user', 'content' => 'Ancien message'],
        ])
        ->assertNotSet('messages', [['role' => 'user', 'content' => 'Ancien message']]);
});

it('nettoie les messages invalides lors de la restauration', function () {
    Setting::set('ai.chatbot_enabled', true);

    $dirty = [
        ['role' => 'user', 'content' => 'Valide'],
        ['role' => 'system', 'content' => 'Interdit'],
        ['role' => 'user'],
        ['content' => 'Pas de rôle'],
    ];

    Livewire::test(ChatBot::class)
        ->call('restoreMessages', $dirty)
        ->assertSet('messages', [
            ['role' => 'user', 'content' => 'Valide'],
        ]);
});

it('dispatche conversation-cleared quand on efface', function () {
    Setting::set('ai.chatbot_enabled', true);

    Livewire::test(ChatBot::class)
        ->call('clearConversation')
        ->assertDispatched('conversation-cleared');
});
