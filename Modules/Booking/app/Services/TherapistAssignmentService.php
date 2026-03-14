<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Booking\Models\Appointment;

class TherapistAssignmentService
{
    /**
     * Get users who have the manage_booking permission (therapists).
     */
    public function getTherapists(): Collection
    {
        return User::permission('manage_booking')->orderBy('name')->get();
    }

    /**
     * Assign a therapist to an appointment.
     */
    public function assign(Appointment $appointment, int $userId): void
    {
        $appointment->update(['assigned_admin_id' => $userId]);
    }

    /**
     * Unassign therapist from an appointment.
     */
    public function unassign(Appointment $appointment): void
    {
        $appointment->update(['assigned_admin_id' => null]);
    }

    /**
     * Auto-assign using round-robin: pick the therapist with the fewest
     * upcoming appointments on the same day.
     */
    public function autoAssign(Appointment $appointment): ?int
    {
        $therapists = $this->getTherapists();

        if ($therapists->isEmpty()) {
            return null;
        }

        $date = $appointment->start_at->toDateString();

        $counts = Appointment::whereIn('assigned_admin_id', $therapists->pluck('id'))
            ->whereDate('start_at', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->selectRaw('assigned_admin_id, count(*) as total')
            ->groupBy('assigned_admin_id')
            ->pluck('total', 'assigned_admin_id');

        // Find therapist with fewest appointments that day
        $bestId = $therapists
            ->sortBy(fn (User $t) => $counts->get($t->id, 0))
            ->first()
            ->id;

        $this->assign($appointment, $bestId);

        return $bestId;
    }

    /**
     * Get appointments for a specific therapist.
     */
    public function getAppointmentsForTherapist(int $userId, ?string $status = null)
    {
        $query = Appointment::with(['service', 'customer'])
            ->where('assigned_admin_id', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('start_at')->get();
    }
}
