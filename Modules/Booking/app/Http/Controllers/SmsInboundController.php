<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;

class SmsInboundController extends Controller
{
    public function handle(Request $request): Response
    {
        $provider = config('booking.sms.provider');

        // Parse selon le provider
        if ($provider === 'vonage') {
            $text = $request->input('text', '');
            $from = $request->input('msisdn', '');
        } else {
            $text = $request->input('Body', '');
            $from = $request->input('From', '');
        }

        $phone = $this->normalizePhone($from);

        Log::info("SMS entrant de {$phone}: {$text}");

        // Trouver le client par téléphone
        $customer = BookingCustomer::where('phone', 'LIKE', '%'.substr($phone, -10).'%')->first();

        if (! $customer) {
            Log::warning("SMS entrant: aucun client trouvé pour {$phone}");

            return response()->noContent();
        }

        // Trouver le prochain rendez-vous
        $appointment = Appointment::where('customer_id', $customer->id)
            ->where('start_at', '>', now())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('start_at')
            ->first();

        if (! $appointment) {
            return response()->noContent();
        }

        $keyword = strtoupper(trim($text));

        if (in_array($keyword, ['OUI', 'YES', 'CONFIRM', 'O', 'Y', '1'])) {
            $appointment->update(['status' => 'confirmed']);
            Log::info("RDV #{$appointment->id} confirmé par SMS");
        } elseif (in_array($keyword, ['NON', 'NO', 'CANCEL', 'N', '0'])) {
            $appointment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => 'Annulé par SMS',
            ]);
            Log::info("RDV #{$appointment->id} annulé par SMS");
        }

        return response()->noContent();
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+'.$digits;
        }

        return '+'.$digits;
    }
}
