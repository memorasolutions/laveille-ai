<?php

declare(strict_types=1);

use App\Mail\ContactMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('la page contact retourne 200', function () {
    $this->get('/contact')->assertStatus(200);
});

it('validation échoue si name est vide', function () {
    $this->post('/contact', [
        'name' => '',
        'email' => 'test@example.com',
        'subject' => 'Sujet',
        'message' => 'Message de test suffisamment long.',
    ])->assertSessionHasErrors('name');
});

it('validation échoue si email est invalide', function () {
    $this->post('/contact', [
        'name' => 'Jean Dupont',
        'email' => 'email-invalide',
        'subject' => 'Sujet',
        'message' => 'Message de test suffisamment long.',
    ])->assertSessionHasErrors('email');
});

it('validation échoue si message est trop court', function () {
    $this->post('/contact', [
        'name' => 'Jean Dupont',
        'email' => 'test@example.com',
        'subject' => 'Sujet',
        'message' => 'court',
    ])->assertSessionHasErrors('message');
});

it('un email est envoyé lors de la soumission valide', function () {
    Mail::fake();

    $this->post('/contact', [
        'name' => 'Jean Dupont',
        'email' => 'jean@example.com',
        'subject' => 'Demande d\'information',
        'message' => 'Bonjour, je souhaite obtenir des informations sur vos services.',
    ]);

    Mail::assertQueued(ContactMail::class);
});

it('soumission valide redirige avec message de succès', function () {
    $this->post('/contact', [
        'name' => 'Jean Dupont',
        'email' => 'jean@example.com',
        'subject' => 'Demande d\'information',
        'message' => 'Bonjour, je souhaite obtenir des informations sur vos services.',
    ])->assertSessionHas('success');
});
