<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Booking\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Booking\Models\Appointment;
use Modules\Core\Notifications\TemplatedNotification;

class BookingStatusNotification extends TemplatedNotification
{
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

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    protected function getTemplateSlug(): string
    {
        return 'booking_status';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'booking' => [
                'status' => $this->newStatus,
                'service_name' => $this->appointment->service->name,
                'date' => $this->appointment->start_at->locale('fr')->isoFormat('dddd D MMMM YYYY a HH:mm'),
                'cancel_token' => $this->appointment->cancel_token,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $subject = self::SUBJECTS[$this->newStatus] ?? 'Mise à jour de votre rendez-vous';
        $message = self::MESSAGES[$this->newStatus] ?? 'Le statut de votre rendez-vous a été modifié.';

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line('**Service :** '.$this->appointment->service->name)
            ->line('**Date :** '.$this->appointment->start_at->locale('fr')->isoFormat('dddd D MMMM YYYY a HH:mm'))
            ->action('Gérer mon rendez-vous', route('booking.manage', $this->appointment->cancel_token));
    }
}
