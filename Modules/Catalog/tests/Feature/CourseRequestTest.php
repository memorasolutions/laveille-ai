<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

/*
 * Tests du formulaire public « demander un cours personnalisé » (CourseRequestController).
 * Même doctrine que ContactSpamTest : on invoque le contrôleur DIRECTEMENT (sans passer par
 * la pile HTTP ni le middleware de thème), et aucun vrai service mail n'est touché - le mailer
 * « workspace » est pointé sur le transport « array » le temps du test, et on inspecte les
 * messages capturés en mémoire. Mail::fake() ne capturant PAS Mail::raw (no-op), on utilise le
 * transport array, plus fidèle. Vérifie : demande légitime persistée en « new » + courriel ;
 * honeypot, piège temporel (présent, absent, non numérique), courriel jetable et URL dans le nom
 * mis en QUARANTAINE (status spam) sans courriel ; Turnstile dormant (clé absente) ne bloque pas ;
 * route désactivée (drapeau à false) -> 404 propre.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\Catalog\Http\Controllers\CourseRequestController;
use Modules\Catalog\Http\Requests\StoreCourseRequest;
use Modules\Catalog\Models\CourseRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Le contrôleur notifie par Mail::mailer('workspace') (transactionnel, jamais Brevo). On le
    // pointe sur le transport « array » pour capturer le message en mémoire sans envoi réel.
    config(['mail.mailers.workspace' => ['transport' => 'array']]);
    config(['mail.from.address' => 'info@laveille.ai', 'mail.from.name' => 'laveille.ai']);

    // Le drapeau du module est actif par défaut (abort_unless ne doit pas se déclencher).
    config(['catalog.request_enabled' => true]);

    // On neutralise l'écouteur de journalisation des courriels (il écrit dans sent_emails,
    // absente de la base de test). Le transport array capture quand même AVANT le dispatch.
    Event::fake([MessageSent::class]);

    Mail::mailer('workspace')->getSymfonyTransport()->flush();
});

/**
 * Charge utile valide par défaut (form_rendered_at vieux de 10 s = humain, honeypot vide).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function coursePayload(array $overrides = []): array
{
    return array_merge([
        'nom' => 'Marie Tremblay',
        'courriel' => 'marie@example.com',
        'organisation' => 'Commission scolaire des Rivières',
        'sujet' => 'Intégrer l\'IA au travail de bureau',
        'heures' => 6,
        'niveau' => 'debutant',
        'participants' => 8,
        'message' => 'Bonjour, nous aimerions une formation sur l\'IA appliquée au travail administratif. Merci.',
        'consentement' => '1',
        'form_rendered_at' => time() - 10,
        'hp_url' => '',
    ], $overrides);
}

/**
 * Valide la charge via la FormRequest puis soumet au contrôleur (comme la pile HTTP le ferait).
 *
 * @param  array<string, mixed>  $payload
 */
function submitCourseRequest(array $payload): \Symfony\Component\HttpFoundation\Response
{
    $request = StoreCourseRequest::create(route('catalog.request.store'), 'POST', $payload);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    return (new CourseRequestController)->store($request);
}

/**
 * Sujets des courriels effectivement envoyés (transport array du mailer workspace).
 *
 * @return list<string>
 */
function courseSentSubjects(): array
{
    $subjects = [];
    foreach (Mail::mailer('workspace')->getSymfonyTransport()->messages() as $sent) {
        $subjects[] = $sent->getOriginalMessage()->getSubject();
    }

    return $subjects;
}

it('persiste en « new » et notifie le fondateur pour une demande légitime (Turnstile dormant ne bloque pas)', function () {
    $response = submitCourseRequest(coursePayload());

    expect($response->getSession()->get('success'))->not->toBeNull();

    // Un seul courriel, vers l'adresse du domaine, sujet métier.
    $subjects = courseSentSubjects();
    expect($subjects)->toHaveCount(1);
    expect($subjects[0])->toContain('Demande de cours sur mesure');

    $req = CourseRequest::query()->latest('id')->first();
    expect($req)->not->toBeNull();
    expect($req->status)->toBe('new');
    expect($req->spam_reason)->toBeNull();
    expect($req->sujet)->toBe('Intégrer l\'IA au travail de bureau');
});

it('met en quarantaine (status spam) quand le honeypot hp_url est rempli, sans courriel', function () {
    $response = submitCourseRequest(coursePayload(['hp_url' => 'http://bot.example/spam']));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(courseSentSubjects())->toBeEmpty();

    $req = CourseRequest::query()->latest('id')->first();
    expect($req)->not->toBeNull();
    expect($req->status)->toBe('spam');
    expect($req->spam_reason)->toBe('honeypot');
});

it('met en quarantaine (timetrap) une soumission trop rapide, sans courriel', function () {
    $response = submitCourseRequest(coursePayload(['form_rendered_at' => time()]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(courseSentSubjects())->toBeEmpty();

    $req = CourseRequest::query()->latest('id')->first();
    expect($req->status)->toBe('spam');
    expect((string) $req->spam_reason)->toContain('timetrap');
});

it('met en quarantaine (timetrap) un form_rendered_at absent ou non numérique (robot direct), sans courriel', function (mixed $bad) {
    $payload = coursePayload();
    if ($bad === '__absent__') {
        unset($payload['form_rendered_at']);
    } else {
        $payload['form_rendered_at'] = $bad;
    }

    $response = submitCourseRequest($payload);

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(courseSentSubjects())->toBeEmpty();

    $req = CourseRequest::query()->latest('id')->first();
    expect($req->status)->toBe('spam');
    expect((string) $req->spam_reason)->toContain('timetrap');
})->with([
    'absent' => '__absent__',
    'chaîne vide' => '',
    'texte' => 'abc',
]);

it('met en quarantaine (disposable_email) un courriel jetable, sans courriel', function () {
    $response = submitCourseRequest(coursePayload(['courriel' => 'test1234@mailinator.com']));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(courseSentSubjects())->toBeEmpty();

    $req = CourseRequest::query()->latest('id')->first();
    expect($req->status)->toBe('spam');
    expect((string) $req->spam_reason)->toContain('disposable_email');
});

it('met en quarantaine (url_in_name) un nom contenant une URL, sans courriel', function () {
    $response = submitCourseRequest(coursePayload([
        'nom' => 'Voir ici ugy2mr2.gentlecouple.org/dkf8d6u?m=1',
    ]));

    expect($response->getSession()->get('success'))->not->toBeNull();
    expect(courseSentSubjects())->toBeEmpty();

    $req = CourseRequest::query()->latest('id')->first();
    expect($req->status)->toBe('spam');
    expect((string) $req->spam_reason)->toContain('url_in_name');
});

it('ne pénalise pas un nom normal (aucun faux positif url_in_name)', function () {
    submitCourseRequest(coursePayload(['nom' => 'Jean-Philippe Côté']));

    expect(courseSentSubjects())->toHaveCount(1);

    $req = CourseRequest::query()->latest('id')->first();
    expect($req->status)->toBe('new');
    expect((string) $req->spam_reason)->not->toContain('url_in_name');
});

it('renvoie 404 quand le module est désactivé (drapeau catalog.request_enabled à false)', function () {
    config(['catalog.request_enabled' => false]);

    expect(fn () => (new CourseRequestController)->create())
        ->toThrow(NotFoundHttpException::class);
});
