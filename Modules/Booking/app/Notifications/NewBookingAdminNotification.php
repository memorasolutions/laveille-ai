<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $appointmentId,
        public readonly string $serviceName,
        public readonly string $customerName,
        public readonly string $dateTime,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau rendez-vous')
            ->line('Un nouveau rendez-vous a été réservé.')
            ->line('**Service :** '.$this->serviceName)
            ->line('**Client :** '.$this->customerName)
            ->line('**Date :** '.$this->dateTime)
            ->action('Voir le rendez-vous', route('admin.booking.appointments.show', $this->appointmentId));
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'new_booking',
            'appointment_id' => $this->appointmentId,
            'service_name' => $this->serviceName,
            'customer_name' => $this->customerName,
            'date' => $this->dateTime,
        ];
    }
}
