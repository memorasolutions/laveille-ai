<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Booking\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;

class NewBookingAdminNotification extends TemplatedNotification
{
    public function __construct(
        public readonly int $appointmentId,
        public readonly string $serviceName,
        public readonly string $customerName,
        public readonly string $dateTime,
    ) {}

    protected function getTemplateSlug(): string
    {
        return 'booking_new_admin';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'booking' => [
                'appointment_id' => $this->appointmentId,
                'service_name' => $this->serviceName,
                'customer_name' => $this->customerName,
                'date' => $this->dateTime,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau rendez-vous')
            ->line('Un nouveau rendez-vous a ete reserve.')
            ->line('**Service :** '.$this->serviceName)
            ->line('**Client :** '.$this->customerName)
            ->line('**Date :** '.$this->dateTime)
            ->action('Voir le rendez-vous', route('admin.booking.appointments.show', $this->appointmentId));
    }

    public function toArray(object $notifiable): array
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
