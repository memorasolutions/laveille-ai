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
        'approved' => 'Rendez-vous confirme',
        'rejected' => 'Rendez-vous refuse',
        'cancelled' => 'Rendez-vous annule',
    ];

    private const MESSAGES = [
        'approved' => 'Votre rendez-vous a ete confirme.',
        'rejected' => 'Votre rendez-vous a ete refuse.',
        'cancelled' => 'Votre rendez-vous a ete annule.',
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
        $subject = self::SUBJECTS[$this->newStatus] ?? 'Mise a jour de votre rendez-vous';
        $message = self::MESSAGES[$this->newStatus] ?? 'Le statut de votre rendez-vous a ete modifie.';

        return (new MailMessage)
            ->subject($subject)
            ->line($message)
            ->line('**Service :** '.$this->appointment->service->name)
            ->line('**Date :** '.$this->appointment->start_at->locale('fr')->isoFormat('dddd D MMMM YYYY a HH:mm'))
            ->action('Gerer mon rendez-vous', route('booking.manage', $this->appointment->cancel_token));
    }
}
