<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Spatie\Health\Enums\Status;

class CheckFailedNotification extends \Spatie\Health\Notifications\CheckFailedNotification
{
    public function toMail(): MailMessage
    {
        $site = (string) (config('app.name') ?: config('app.url'));
        $urgent = collect($this->results)->contains(fn ($result): bool => $result->status->equals(Status::failed()) || $result->status->equals(Status::crashed()));
        $gravity = $urgent ? 'URGENT' : 'AVERTISSEMENT';

        $mail = (new MailMessage)
            ->mailer('workspace')
            ->from(
                config('health.notifications.mail.from.address', config('mail.from.address')),
                config('health.notifications.mail.from.name', config('mail.from.name'))
            )
            ->subject("[{$gravity}] Santé de {$site}")
            ->line($urgent
                ? 'Un contrôle de santé du site a échoué et demande une intervention rapide.'
                : 'Un contrôle de santé du site approche d’une limite et doit être surveillé.')
            ->line($urgent
                ? 'Les visiteurs peuvent subir des ralentissements, des erreurs ou une indisponibilité si la situation persiste.'
                : 'Les visiteurs ne sont pas nécessairement touchés maintenant, mais les performances pourraient se dégrader sans intervention.')
            ->line('---')
            ->line('Détails techniques et marche à suivre :');

        foreach ($this->results as $result) {
            $mail->line($result->check->getLabel().' : '.$result->getNotificationMessage());
            $mail->line('Résumé : '.$result->getShortSummary());
            $mail->line('Mesures exactes : '.json_encode($result->meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $mail;
    }
}
