<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Booking\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Appointment;
use Modules\Core\Contracts\MetricProviderInterface;
use Modules\Core\DataTransferObjects\MetricWidget;

class BookingMetricProvider implements MetricProviderInterface
{
    public function getMetricName(): string
    {
        return 'booking';
    }

    /** @return list<MetricWidget> */
    public function getWidgets(): array
    {
        $metrics = $this->getMetrics(now()->startOfMonth(), now()->endOfMonth());

        return [
            new MetricWidget(name: 'Rendez-vous ce mois', value: (string) $metrics['appointments'], type: 'number', icon: 'calendar'),
            new MetricWidget(name: 'Revenu confirme', value: $metrics['revenue'].' CAD', type: 'currency', icon: 'dollar-sign'),
            new MetricWidget(name: 'Taux no-show', value: $metrics['no_show_rate'].'%', type: 'percent', icon: 'user-x'),
        ];
    }

    /** @return array<string, mixed> */
    public function getMetrics(Carbon $from, Carbon $to): array
    {
        $total = Appointment::whereBetween('created_at', [$from, $to])->count();

        $revenue = (float) DB::table('booking_appointments')
            ->join('booking_services', 'booking_appointments.service_id', '=', 'booking_services.id')
            ->whereIn('booking_appointments.status', ['confirmed', 'completed'])
            ->whereBetween('booking_appointments.created_at', [$from, $to])
            ->sum('booking_services.price');

        $noShows = Appointment::where('status', 'no_show')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $noShowRate = $total > 0 ? round($noShows / $total * 100, 1) : 0.0;

        return [
            'appointments' => $total,
            'revenue' => number_format($revenue, 2),
            'no_show_rate' => number_format($noShowRate, 1),
        ];
    }
}
