<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Services\ICalService;

class BookingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function build(): static
    {
        $icsContent = app(ICalService::class)->generateCalendar(
            collect([$this->appointment->load(['service'])]),
            'Rendez-vous'
        );

        return $this->subject('Confirmation de votre rendez-vous')
            ->markdown('booking::emails.confirmation')
            ->with([
                'service_name' => $this->appointment->service->name,
                'customer_name' => $this->appointment->customer->first_name,
                'date' => $this->appointment->start_at->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                'time' => $this->appointment->start_at->format('H\hi').' - '.$this->appointment->end_at->format('H\hi'),
                'manage_url' => route('booking.manage', $this->appointment->cancel_token),
                'brand_name' => config('booking.brand.business_name', config('app.name')),
            ])
            ->attachData($icsContent, 'rendez-vous.ics', ['mime' => 'text/calendar']);
    }
}
