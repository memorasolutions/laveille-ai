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

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\FrontTheme\Http\Controllers\ContactController;

// RefreshDatabase : on a désormais besoin de la table contact_messages pour vérifier
// la persistance (quarantaine spam / boîte légitime) en plus du comportement mail.
uses(Tests\TestCase::class, RefreshDatabase::class);

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

it('envoie le courriel et persiste en « new » sans raison pour un message légitime', function () {
    $response = submitContact(contactPayload());

    expect($response->getSession()->get('success'))->not->toBeNull();

    $subjects = sentSubjects();
    expect($subjects)->toHaveCount(1);
    expect(str_starts_with($subjects[0], '[Spam probable]'))->toBeFalse();

    // Persistance : un message en boîte légitime, aucune raison de spam.
    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('new');
    expect($msg->spam_reason)->toBeNull();
});

it('met en quarantaine (status spam) un spam clair, avec raison et sans courriel', function () {
    $response = submitContact(contactPayload([
        'subject' => 'THE $27,000,000 JACKPOT IS A CROWN FOR CASH',
        'message' => 'YOU ARE THE LUCKY WINNER OF THE GRAND PRIZE CLAIM IT NOW AT url.in.th/FqcAS',
    ]));

    // Rejet silencieux côté mail : succès renvoyé, AUCUN courriel envoyé.
    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();

    // Mais le message est consultable en quarantaine, avec la raison.
    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('spam');
    expect($msg->spam_reason)->not->toBeEmpty();
});

it('met en quarantaine (url_in_name) un nom contenant une URL, sans courriel', function () {
    $response = submitContact(contactPayload([
        'name' => 'Come see me ugy2mr2.gentlecouple.org/dkf8d6u?m=1',
        'message' => 'Bonjour, je voulais simplement vous écrire un petit mot aujourd\'hui.',
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();

    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('spam');
    expect($msg->spam_reason)->toContain('url_in_name');
});

it('ne met pas en quarantaine un nom normal ou à initiale pointée (aucun faux positif url_in_name)', function () {
    foreach (['Jean Tremblay', 'J.Robert Gagnon'] as $name) {
        Mail::mailer('array')->getSymfonyTransport()->flush();

        submitContact(contactPayload(['name' => $name]));

        expect(sentSubjects())->toHaveCount(1);

        $msg = ContactMessage::query()->latest('id')->first();
        expect($msg->status)->toBe('new');
        expect((string) $msg->spam_reason)->not->toContain('url_in_name');
    }
});

it('met en quarantaine (status spam) quand le honeypot est rempli, sans courriel', function () {
    $response = submitContact(contactPayload([
        'hp_url' => 'http://bot.example/spam',
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();

    // On persiste aussi le honeypot pour visibilité (vérification des faux positifs).
    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('spam');
    expect($msg->spam_reason)->toBe('honeypot');
});

it('met en quarantaine (status spam) une soumission trop rapide (time-trap), sans courriel', function () {
    $response = submitContact(contactPayload([
        'form_ts' => time(),
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();

    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('spam');
    expect($msg->spam_reason)->toContain('timetrap');
});

it('met en quarantaine (time-trap) une soumission SANS jeton form_ts (robot postant en direct), sans courriel', function () {
    // Un robot qui poste directement sur la route ne charge pas la page et n'envoie donc
    // aucun form_ts. La vraie vue du formulaire le porte toujours -> son absence = robot.
    $payload = contactPayload();
    unset($payload['form_ts']);

    $response = submitContact($payload);

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();

    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('spam');
    expect($msg->spam_reason)->toContain('timetrap');
});

it('met en quarantaine (time-trap) un form_ts VIDE ou NON NUMÉRIQUE (robot bricolé), sans courriel', function (string $bad) {
    // Un robot peut envoyer form_ts avec une valeur bidon (chaîne vide, texte). is_numeric la rejette
    // -> même verdict que l'absence : quarantaine silencieuse. Un vrai humain n'atteint jamais ce cas
    // (la vue rend toujours un entier). NB : « 1e10 » est is_numeric=vrai et se caste en 1 -> délai
    // ancien -> ACCEPTÉ (compromis page en cache), donc absent de ce jeu, testé séparément ci-dessous.
    $response = submitContact(contactPayload(['form_ts' => $bad]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();

    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('spam');
    expect((string) $msg->spam_reason)->toContain('timetrap');
})->with([
    'chaîne vide' => '',
    'texte' => 'abc',
    'NaN' => 'NaN',
]);

it('accepte une soumission au form_ts ANCIEN (page en cache) sans faux positif time-trap', function () {
    // Garde-fou anti-faux-positif : un visiteur légitime dont la page est servie depuis un
    // cache soumet un form_ts vieux de plusieurs heures. Ce délai long doit rester valide.
    $response = submitContact(contactPayload([
        'form_ts' => time() - 7200,
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();

    $subjects = sentSubjects();
    expect($subjects)->toHaveCount(1);
    expect(str_starts_with($subjects[0], '[Spam probable]'))->toBeFalse();

    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg->status)->toBe('new');
    expect((string) $msg->spam_reason)->not->toContain('timetrap');
});

it('envoie en « new », trace la raison et préfixe « [Spam probable] » pour un seul signal faible', function () {
    $response = submitContact(contactPayload([
        'subject' => 'Une ressource à partager',
        'message' => 'Bonjour, voici un lien que je trouve pertinent : bit.ly/abcdef. Au plaisir.',
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();

    $subjects = sentSubjects();
    expect($subjects)->toHaveCount(1);
    expect(str_starts_with($subjects[0], '[Spam probable] '))->toBeTrue();

    // Persisté en boîte légitime mais la raison faible est tracée.
    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('new');
    expect($msg->spam_reason)->toBe('shortener');
});

it('met en quarantaine le spam charabia du 2026-10-09 (adresse jetable + chiffres répétés), sans courriel', function () {
    // Reproduction exacte du spam reçu : même suite « 696976 » dans le nom, le sujet ET le message,
    // charabia en majuscules, expéditeur sur un domaine jetable (notboxletters.com).
    $response = submitContact(contactPayload([
        'name' => 'NARETGR696976NERTHRTYHR',
        'email' => 'jorre_4319@notboxletters.com',
        'subject' => 'TOTYJTRT696976TIRTYRTTR',
        'message' => 'MEJTYJY696976MAMYJRTH, un message assez long pour franchir la validation min:10.',
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();

    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('spam');
    expect((string) $msg->spam_reason)->toContain('disposable_email');
    expect((string) $msg->spam_reason)->toContain('repeated_token');
});

it('met en quarantaine une adresse jetable, même avec un message normal, sans courriel', function () {
    $response = submitContact(contactPayload([
        'email' => 'test1234@mailinator.com',
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(sentSubjects())->toBeEmpty();

    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg)->not->toBeNull();
    expect($msg->status)->toBe('spam');
    expect((string) $msg->spam_reason)->toContain('disposable_email');
});

it('ne pénalise PAS un message légitime répétant un mot et contenant un numéro (aucun faux positif)', function () {
    // « plateforme » se répète entre sujet et message (mot, pas une suite de chiffres), et le
    // message porte un numéro de téléphone (chiffres présents dans UN seul champ). Ni repeated_token
    // ni gibberish ne doivent se déclencher : le message part normalement, sans préfixe.
    $response = submitContact(contactPayload([
        'subject' => 'Question sur la plateforme de veille',
        'message' => 'Bonjour, votre plateforme de veille m\'intéresse beaucoup. Rappelez-moi au 418 555 1234. Merci.',
    ]));

    $subjects = sentSubjects();
    expect($subjects)->toHaveCount(1);
    expect(str_starts_with($subjects[0], '[Spam probable]'))->toBeFalse();

    $msg = ContactMessage::query()->latest('id')->first();
    expect($msg->status)->toBe('new');
    expect((string) $msg->spam_reason)->not->toContain('repeated_token');
    expect((string) $msg->spam_reason)->not->toContain('gibberish');
});
