<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Booking\Models\Appointment;

class BookingStatusNotification extends Notification
{
    use Queueable;

    private const SUBJECTS = [
        'approved' => 'Rendez-vous confirmé',
        'rejected' => 'Rendez-vous refusé',
        'cancelled' => 'Rendez-vous annulé',
    ];

    private const MESSAGES = [
        'approved' => 'Votre rendez-vous a été confirmé.',
        'rejected' => 'Votre rendez-vous a été refusé.',
        'cancelled' => 'Votre rendez-vous a été annulé.',
    ];

    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $newStatus,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $subject = self::SUBJECTS[$this->newStatus] ?? 'Mise à jour de votre rendez-vous';
        $message = self::MESSAGES[$this->newStatus] ?? 'Le statut de votre rendez-vous a été modifié.';

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line('**Service :** '.$this->appointment->service->name)
            ->line('**Date :** '.$this->appointment->start_at->locale('fr')->isoFormat('dddd D MMMM YYYY à HH:mm'))
            ->action('Gérer mon rendez-vous', route('booking.manage', $this->appointment->cancel_token));
    }
}
