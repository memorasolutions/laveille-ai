<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Booking\Mail\BookingCancellation;
use Modules\Booking\Mail\BookingConfirmation;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService as ServiceModel;

class BookingService
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected SmsNotificationService $smsNotificationService,
        protected WebhookDispatchService $webhookDispatchService,
    ) {}

    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $service = ServiceModel::findOrFail($data['service_id']);

            if (! $this->availabilityService->isSlotAvailable(
                $data['date'],
                $data['start_time'],
                $service->duration_minutes
            )) {
                throw new \RuntimeException('Le créneau sélectionné n\'est plus disponible.');
            }

            $customer = $this->findOrCreateCustomer($data['customer']);

            $startAt = Carbon::parse($data['date'].' '.$data['start_time'], config('booking.timezone', 'America/Toronto'));
            $endAt = $startAt->copy()->addMinutes($service->duration_minutes);

            $appointment = Appointment::create([
                'service_id' => $service->id,
                'customer_id' => $customer->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => 'pending',
                'cancel_token' => Str::random(64),
                'notes' => $data['customer']['notes'] ?? null,
            ]);

            if (config('booking.email.enabled', true)) {
                Mail::to($customer->email)->queue(new BookingConfirmation($appointment));
            }

            $this->smsNotificationService->sendConfirmation($appointment);
            $this->webhookDispatchService->dispatchForAppointment('appointment.created', $appointment);

            return $appointment;
        });
    }

    public function confirm(Appointment $appointment): void
    {
        $appointment->update(['status' => 'confirmed']);
        $this->webhookDispatchService->dispatchForAppointment('appointment.confirmed', $appointment);
    }

    public function cancel(string $cancelToken, ?string $reason = null): Appointment
    {
        $appointment = Appointment::where('cancel_token', $cancelToken)->firstOrFail();

        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        if (config('booking.email.enabled', true)) {
            Mail::to($appointment->customer->email)->queue(new BookingCancellation($appointment));
        }

        $this->smsNotificationService->sendCancellation($appointment);
        $this->webhookDispatchService->dispatchForAppointment('appointment.cancelled', $appointment);

        return $appointment->fresh();
    }

    public function reschedule(Appointment $appointment, string $newDate, string $newStartTime): Appointment
    {
        $service = ServiceModel::findOrFail($appointment->service_id);

        if (! $this->availabilityService->isSlotAvailable($newDate, $newStartTime, $service->duration_minutes)) {
            throw new \RuntimeException('Le nouveau créneau n\'est pas disponible.');
        }

        $startAt = Carbon::parse($newDate.' '.$newStartTime, config('booking.timezone', 'America/Toronto'));
        $endAt = $startAt->copy()->addMinutes($service->duration_minutes);

        $appointment->update([
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        return $appointment->fresh();
    }

    protected function findOrCreateCustomer(array $customerData): BookingCustomer
    {
        return BookingCustomer::firstOrCreate(
            ['email' => $customerData['email']],
            [
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'phone' => $customerData['phone'] ?? null,
                'notes' => $customerData['notes'] ?? null,
            ]
        );
    }
}
