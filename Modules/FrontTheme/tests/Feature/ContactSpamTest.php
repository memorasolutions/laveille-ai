<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

/*
 * Tests anti-spam du formulaire de contact (ContactController@send).
 * Aucun vrai service mail n'est appelé : l'environnement de test utilise le transport
 * « array » (phpunit.xml MAIL_MAILER=array) ; on inspecte les messages capturés en mémoire.
 * On invoque le contrôleur DIRECTEMENT (sans HTTP) pour ne pas dépendre du middleware de
 * thème (lecture de la table settings) ni d'une base migrée, comme les autres tests
 * réflexifs du module FrontTheme. Note : Mail::fake() ne capture PAS Mail::raw (no-op),
 * d'où l'usage du transport array, plus fidèle. Vérifie : message légitime envoyé ;
 * spam clair rejeté en silence (succès renvoyé) ; honeypot et time-trap rejetés en silence ;
 * un signal faible isolé passe mais le sujet est préfixé « [Spam probable] ».
 */

use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\FrontTheme\Http\Controllers\ContactController;

uses(Tests\TestCase::class);

beforeEach(function () {
    // On neutralise l'écouteur de journalisation des courriels (Notifications\LogSentEmail
    // écrit dans la table sent_emails, absente de la base en mémoire des tests). Le transport
    // « array » capture quand même le message AVANT que MessageSent ne soit dispatché.
    Event::fake([MessageSent::class]);

    // Vide la boîte « array » entre chaque test (transport en mémoire, zéro envoi réel).
    Mail::mailer('array')->getSymfonyTransport()->flush();
});

/**
 * Construit une charge utile valide par défaut (form_ts vieux de 10 s = humain).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contactPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Marie Tremblay',
        'email' => 'marie@example.com',
        'subject' => 'Question sur la plateforme',
        'message' => 'Bonjour, j\'aimerais en savoir plus sur vos services de veille. Merci.',
        'form_ts' => time() - 10,
        'hp_url' => '',
    ], $overrides);
}

/**
 * Soumet la charge utile au contrôleur et retourne la réponse.
 *
 * @param  array<string, mixed>  $payload
 */
function submitContact(array $payload): \Symfony\Component\HttpFoundation\Response
{
    $request = Request::create(route('contact.send'), 'POST', $payload);

    return (new ContactController)->send($request);
}

/**
 * Sujets des courriels effectivement envoyés (transport array).
 *
 * @return list<string>
 */
function sentSubjects(): array
{
    $subjects = [];
    foreach (Mail::mailer('array')->getSymfonyTransport()->messages() as $sent) {
        $subjects[] = $sent->getOriginalMessage()->getSubject();
    }

    return $subjects;
}

it('envoie le courriel pour un message légitime', function () {
    $response = submitContact(contactPayload());

    expect($response->getSession()->get('success'))->not->toBeNull();

    $subjects = sentSubjects();
    expect($subjects)->toHaveCount(1);
    expect(str_starts_with($subjects[0], '[Spam probable]'))->toBeFalse();
});

it('rejette silencieusement un spam clair (jackpot, MAJUSCULES, raccourcisseur)', function () {
    $response = submitContact(contactPayload([
        'subject' => 'THE $27,000,000 JACKPOT IS A CROWN FOR CASH',
        'message' => 'YOU ARE THE LUCKY WINNER OF THE GRAND PRIZE CLAIM IT NOW AT url.in.th/FqcAS',
    ]));

    // Rejet silencieux : succès renvoyé, mais aucun courriel envoyé.
    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();
});

it('rejette silencieusement quand le honeypot est rempli', function () {
    $response = submitContact(contactPayload([
        'hp_url' => 'http://bot.example/spam',
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();
});

it('envoie mais préfixe « [Spam probable] » pour un seul signal faible (raccourcisseur seul)', function () {
    $response = submitContact(contactPayload([
        'subject' => 'Une ressource à partager',
        'message' => 'Bonjour, voici un lien que je trouve pertinent : bit.ly/abcdef. Au plaisir.',
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();

    $subjects = sentSubjects();
    expect($subjects)->toHaveCount(1);
    expect(str_starts_with($subjects[0], '[Spam probable] '))->toBeTrue();
});

it('rejette silencieusement une soumission trop rapide (time-trap)', function () {
    $response = submitContact(contactPayload([
        'form_ts' => time(),
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();
});
