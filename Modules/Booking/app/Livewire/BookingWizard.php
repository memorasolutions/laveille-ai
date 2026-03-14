<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Livewire;

use Livewire\Component;
use Modules\Booking\Models\BookingService as ServiceModel;
use Modules\Booking\Services\AvailabilityService;
use Modules\Booking\Services\BookingService;

class BookingWizard extends Component
{
    public int $step = 1;

    public ?int $selectedServiceId = null;

    public ?string $selectedDate = null;

    public ?string $selectedTime = null;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    public ?array $appointmentData = null;

    public array $availableSlots = [];

    public array $availableDates = [];

    public function selectService(int $id): void
    {
        $this->selectedServiceId = $id;
        $this->availableDates = app(AvailabilityService::class)->getAvailableDates();
        $this->step = 2;
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $service = ServiceModel::findOrFail($this->selectedServiceId);
        $this->availableSlots = app(AvailabilityService::class)->getAvailableSlots(
            $date,
            $service->duration_minutes
        );
        $this->step = 3;
    }

    public function selectTime(string $time): void
    {
        $this->selectedTime = $time;
    }

    public function submitBooking(): void
    {
        $this->validate([
            'selectedTime' => 'required',
            'firstName' => 'required|min:2',
            'lastName' => 'required|min:2',
            'email' => 'required|email',
            'phone' => ['nullable', 'regex:/^[\d\s\-\+\(\)]+$/'],
        ]);

        $appointment = app(BookingService::class)->book([
            'service_id' => $this->selectedServiceId,
            'date' => $this->selectedDate,
            'start_time' => $this->selectedTime,
            'customer' => [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
                'phone' => $this->phone,
                'notes' => $this->notes,
            ],
        ]);

        $this->appointmentData = [
            'id' => $appointment->id,
            'service' => $appointment->service->name,
            'start_at' => $appointment->start_at->format('Y-m-d H:i'),
            'end_at' => $appointment->end_at->format('H:i'),
            'cancel_token' => $appointment->cancel_token,
        ];

        $this->step = 4;
    }

    public function goBack(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function render()
    {
        return view('booking::livewire.booking-wizard', [
            'services' => ServiceModel::active()->ordered()->get(),
        ]);
    }
}
