<?php

declare(strict_types=1);

namespace Tests\Feature;

use Mockery;
use Modules\Newsletter\Mail\Transport\BrevoApiTransport;
use Modules\Newsletter\Services\BrevoService;
use ReflectionMethod;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

function makeBrevoSentMessage(Email $email): SentMessage
{
    return new SentMessage($email, new Envelope(new Address('from@x.com'), [new Address('to@x.com')]));
}

function invokeBrevoDoSend(BrevoApiTransport $transport, SentMessage $message): void
{
    $method = new ReflectionMethod(BrevoApiTransport::class, 'doSend');
    $method->setAccessible(true);
    $method->invoke($transport, $message);
}

afterEach(fn () => Mockery::close());

it('returns brevo://api as string identifier', function () {
    $service = Mockery::mock(BrevoService::class);
    expect((string) new BrevoApiTransport($service))->toBe('brevo://api');
});

it('throws TransportException when email has no recipient', function () {
    $service = Mockery::mock(BrevoService::class);
    // Email sans ->to() : Symfony valide en interne le header To dès SentMessage,
    // donc on mock l'Email pour bypasser ensureValidity et tester uniquement la logique guard.
    $email = Mockery::mock(Email::class);
    $email->shouldReceive('getTo')->andReturn([]);
    $sent = Mockery::mock(SentMessage::class);
    $sent->shouldReceive('getOriginalMessage')->andReturn($email);

    expect(fn () => invokeBrevoDoSend(new BrevoApiTransport($service), $sent))
        ->toThrow(TransportException::class, 'aucune adresse destinataire');
});

it('throws TransportException when API returns success=false', function () {
    $service = Mockery::mock(BrevoService::class);
    $service->shouldReceive('sendCampaignEmail')
        ->once()
        ->andReturn(['success' => false, 'error' => 'API down']);

    $email = (new Email())->from(new Address("from@x.com"))
        ->to(new Address('to@example.com', 'John'))
        ->subject('Hello')
        ->html('<p>Body</p>');
    $sent = makeBrevoSentMessage($email);

    expect(fn () => invokeBrevoDoSend(new BrevoApiTransport($service), $sent))
        ->toThrow(TransportException::class, 'Brevo API: API down');
});

it('calls sendCampaignEmail with extracted to/name/subject/html', function () {
    $service = Mockery::mock(BrevoService::class);
    $service->shouldReceive('sendCampaignEmail')
        ->with('user@test.com', 'John', 'Hello', '<p>Body</p>')
        ->once()
        ->andReturn(['success' => true]);

    $email = (new Email())->from(new Address("from@x.com"))
        ->to(new Address('user@test.com', 'John'))
        ->subject('Hello')
        ->html('<p>Body</p>');
    $sent = makeBrevoSentMessage($email);

    invokeBrevoDoSend(new BrevoApiTransport($service), $sent);
});

it('falls back to nl2br escaped text body when html is empty', function () {
    $service = Mockery::mock(BrevoService::class);
    $service->shouldReceive('sendCampaignEmail')
        ->withArgs(fn ($to, $name, $subject, $html) => str_contains($html, '<br'))
        ->once()
        ->andReturn(['success' => true]);

    $email = (new Email())->from(new Address("from@x.com"))
        ->to(new Address('user@test.com', 'John'))
        ->subject('Hello')
        ->text("Line 1\nLine 2");
    $sent = makeBrevoSentMessage($email);

    invokeBrevoDoSend(new BrevoApiTransport($service), $sent);
});

it('adds X-Brevo-Message-Id header when message_id returned', function () {
    $service = Mockery::mock(BrevoService::class);
    $service->shouldReceive('sendCampaignEmail')
        ->once()
        ->andReturn(['success' => true, 'message_id' => 'abc-123']);

    $email = (new Email())->from(new Address("from@x.com"))
        ->to(new Address('user@test.com', 'John'))
        ->subject('Hello')
        ->html('<p>Body</p>');
    $sent = makeBrevoSentMessage($email);

    invokeBrevoDoSend(new BrevoApiTransport($service), $sent);

    expect($email->getHeaders()->has('X-Brevo-Message-Id'))->toBeTrue();
    expect($email->getHeaders()->get('X-Brevo-Message-Id')->getBody())->toBe('abc-123');
});

it('passes null name when address has no display name', function () {
    $service = Mockery::mock(BrevoService::class);
    $service->shouldReceive('sendCampaignEmail')
        ->withArgs(fn ($to, $name, $subject, $html) => $to === 'user@test.com' && $name === null)
        ->once()
        ->andReturn(['success' => true]);

    $email = (new Email())->from(new Address("from@x.com"))
        ->to(new Address('user@test.com'))
        ->subject('Hello')
        ->html('<p>Body</p>');
    $sent = makeBrevoSentMessage($email);

    invokeBrevoDoSend(new BrevoApiTransport($service), $sent);
});
