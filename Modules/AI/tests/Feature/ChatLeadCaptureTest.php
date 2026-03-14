<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\AI\Livewire\ChatBot;
use Modules\AI\Notifications\ChatLeadNotification;
use Modules\Settings\Models\Setting;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('affiche le bouton être contacté', function () {
    Setting::set('ai.chatbot_enabled', true);

    Livewire::test(ChatBot::class)
        ->call('toggleOpen')
        ->assertSee('Être contacté');
});

it('démarre le lead capture', function () {
    Setting::set('ai.chatbot_enabled', true);

    Livewire::test(ChatBot::class)
        ->call('startLeadCapture')
        ->assertSet('leadMode', true)
        ->assertSet('leadStep', 1);
});

it('collecte le nom', function () {
    Setting::set('ai.chatbot_enabled', true);

    Livewire::test(ChatBot::class)
        ->call('startLeadCapture')
        ->set('message', 'Jean Tremblay')
        ->call('submitLeadField')
        ->assertSet('leadStep', 2)
        ->assertSet('leadData.name', 'Jean Tremblay');
});

it('valide le format email', function () {
    Setting::set('ai.chatbot_enabled', true);

    Livewire::test(ChatBot::class)
        ->call('startLeadCapture')
        ->set('message', 'Jean Tremblay')
        ->call('submitLeadField')
        ->set('message', 'pas-un-email')
        ->call('submitLeadField')
        ->assertSet('leadStep', 2);
});

it('accepte un email valide', function () {
    Setting::set('ai.chatbot_enabled', true);

    Livewire::test(ChatBot::class)
        ->call('startLeadCapture')
        ->set('message', 'Test')
        ->call('submitLeadField')
        ->set('message', 'test@example.com')
        ->call('submitLeadField')
        ->assertSet('leadStep', 3);
});

it('permet de passer le téléphone', function () {
    Setting::set('ai.chatbot_enabled', true);

    Livewire::test(ChatBot::class)
        ->set('leadMode', true)
        ->set('leadStep', 3)
        ->set('leadData', ['name' => 'Jean', 'email' => 'jean@test.com', 'phone' => '', 'message' => ''])
        ->set('message', 'passer')
        ->call('submitLeadField')
        ->assertSet('leadStep', 4)
        ->assertSet('leadData.phone', '');
});

it('crée un ContactMessage à la fin du flow', function () {
    Setting::set('ai.chatbot_enabled', true);
    Notification::fake();

    Livewire::test(ChatBot::class)
        ->call('startLeadCapture')
        ->set('message', 'Jean')
        ->call('submitLeadField')
        ->set('message', 'jean@test.com')
        ->call('submitLeadField')
        ->set('message', 'passer')
        ->call('submitLeadField')
        ->set('message', 'Mon message de test')
        ->call('submitLeadField');

    $this->assertDatabaseHas('contact_messages', [
        'name' => 'Jean',
        'email' => 'jean@test.com',
    ]);

    Notification::assertSentOnDemand(ChatLeadNotification::class);
});

it('annule le lead capture', function () {
    Setting::set('ai.chatbot_enabled', true);

    Livewire::test(ChatBot::class)
        ->call('startLeadCapture')
        ->call('cancelLeadCapture')
        ->assertSet('leadMode', false)
        ->assertSet('leadStep', 0);
});
