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

class BookingCancellation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment) {}

    public function build(): static
    {
        return $this->subject('Annulation de votre rendez-vous')
            ->markdown('booking::emails.cancellation')
            ->with([
                'service_name' => $this->appointment->service->name,
                'customer_name' => $this->appointment->customer->first_name,
                'date' => $this->appointment->start_at->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                'time' => $this->appointment->start_at->format('H\hi').' - '.$this->appointment->end_at->format('H\hi'),
                'cancel_reason' => $this->appointment->cancel_reason,
                'rebooking_url' => route('booking.wizard'),
                'brand_name' => config('booking.brand.business_name', config('app.name')),
            ]);
    }
}
