<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Privacy\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Privacy\Models\RightsRequest;

class RightsRequestController extends Controller
{
    /**
     * POST /api/privacy/rights-request - Soumet une demande d'exercice de droits
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'request_type' => 'required|in:access,rectification,erasure,portability,opposition,limitation,withdrawal',
            'description' => 'required|string|max:5000',
            'file' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('rights-requests', 'local');
        }

        $rightsRequest = RightsRequest::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'request_type' => $validated['request_type'],
            'description' => $validated['description'],
            'file_path' => $filePath,
            'jurisdiction' => session('privacy_jurisdiction', 'pipeda'),
            'deadline_at' => now()->addDays(config('privacy.rights.response_delay_days', 30)),
        ]);

        // Notification au DPO
        $dpoEmail = config('privacy.rights.notification_email');
        if ($dpoEmail) {
            try {
                Mail::raw(
                    "Nouvelle demande d'exercice de droits\n\n"
                    ."Reference : {$rightsRequest->reference}\n"
                    ."Type : {$rightsRequest->request_type}\n"
                    ."De : {$rightsRequest->name} ({$rightsRequest->email})\n"
                    ."Juridiction : {$rightsRequest->jurisdiction}\n"
                    ."Date limite : {$rightsRequest->deadline_at->format('Y-m-d')}\n\n"
                    ."Description :\n{$rightsRequest->description}",
                    function ($message) use ($dpoEmail, $rightsRequest) {
                        $message->to($dpoEmail)
                            ->subject("[Droits] {$rightsRequest->reference} - {$rightsRequest->request_type}");
                    }
                );
            } catch (\Exception $e) {
                Log::error('Rights request notification failed: '.$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'reference' => $rightsRequest->reference,
            'message' => __('Votre demande a ete enregistree. Reference : ').$rightsRequest->reference,
            'deadline_at' => $rightsRequest->deadline_at->toIso8601String(),
        ], 201);
    }
}
