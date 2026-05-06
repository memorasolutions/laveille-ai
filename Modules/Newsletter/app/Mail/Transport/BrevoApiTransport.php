<?php

declare(strict_types=1);

namespace Modules\Newsletter\Mail\Transport;

use Modules\Newsletter\Services\BrevoService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BrevoApiTransport extends AbstractTransport
{
    public function __construct(
        protected BrevoService $brevoService,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($dispatcher, $logger);
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new TransportException('BrevoApiTransport ne supporte que '.Email::class);
        }

        $toAddresses = $email->getTo();
        if (empty($toAddresses)) {
            throw new TransportException('BrevoApiTransport: aucune adresse destinataire.');
        }

        $toAddress = $toAddresses[0];
        $to = $toAddress->getAddress();
        $name = $toAddress->getName() ?: null;
        $subject = (string) $email->getSubject();

        $html = $email->getHtmlBody();
        if ($html === null || $html === '') {
            $text = $email->getTextBody();
            $html = $text !== null && $text !== '' ? nl2br(e($text)) : '';
        }

        $result = $this->brevoService->sendCampaignEmail($to, $name, $subject, $html);

        if (! ($result['success'] ?? false)) {
            throw new TransportException(
                'Brevo API: '.($result['error'] ?? 'erreur inconnue lors de l\'envoi.')
            );
        }

        $messageId = $result['message_id'] ?? null;
        if ($messageId) {
            $message->getOriginalMessage()->getHeaders()->addTextHeader('X-Brevo-Message-Id', (string) $messageId);
        }
    }

    public function __toString(): string
    {
        return 'brevo://api';
    }
}
