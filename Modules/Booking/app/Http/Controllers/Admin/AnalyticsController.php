<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = (int) $request->input('period', 30);
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        $appointments = Appointment::whereBetween('created_at', [$startDate, $endDate])->get();

        $total = $appointments->count();
        $stats = [
            'total_appointments' => $total,
            'confirmed_count' => $appointments->where('status', 'confirmed')->count(),
            'cancelled_count' => $appointments->where('status', 'cancelled')->count(),
            'revenue' => $appointments->where('payment_status', 'paid')->sum('amount_paid'),
            'cancellation_rate' => $total > 0
                ? round(($appointments->where('status', 'cancelled')->count() / $total) * 100, 1)
                : 0,
        ];

        $topServices = BookingService::select([
            'booking_services.id',
            'booking_services.name',
            DB::raw('COUNT(booking_appointments.id) as appointment_count'),
        ])
            ->leftJoin('booking_appointments', function ($join) use ($startDate, $endDate) {
                $join->on('booking_services.id', '=', 'booking_appointments.service_id')
                    ->whereBetween('booking_appointments.created_at', [$startDate, $endDate]);
            })
            ->groupBy('booking_services.id', 'booking_services.name')
            ->orderByDesc('appointment_count')
            ->limit(5)
            ->get();

        return view('booking::admin.analytics.index', compact('stats', 'topServices', 'period'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $from = $request->input('from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $to = $request->input('to', Carbon::now()->format('Y-m-d'));

        $query = Appointment::with(['service', 'customer'])
            ->whereBetween('start_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderByDesc('start_at');

        $filename = 'rendez-vous-export-'.date('Y-m-d').'.csv';

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Service', 'Client', 'Email', 'Date début', 'Date fin', 'Statut', 'Paiement', 'Montant'], ';');

            $query->chunk(500, function ($appointments) use ($handle) {
                foreach ($appointments as $apt) {
                    fputcsv($handle, [
                        $apt->id,
                        $apt->service?->name ?? 'N/A',
                        $apt->customer?->full_name ?? 'N/A',
                        $apt->customer?->email ?? 'N/A',
                        $apt->start_at?->format('d/m/Y H:i') ?? '',
                        $apt->end_at?->format('d/m/Y H:i') ?? '',
                        $this->translateStatus($apt->status),
                        $this->translatePaymentStatus($apt->payment_status ?? 'pending'),
                        number_format((float) ($apt->amount_paid ?? 0), 2, ',', ' '),
                    ], ';');
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'cancelled' => 'Annulé',
            default => $status,
        };
    }

    private function translatePaymentStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'paid' => 'Payé',
            'failed' => 'Échoué',
            default => $status,
        };
    }
}
