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
use Modules\Booking\Services\PaymentService;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, PaymentService $paymentService): Response
    {
        try {
            $paymentService->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature', '')
            );
        } catch (SignatureVerificationException) {
            return response('Invalid signature', 400);
        }

        return response('OK', 200);
    }
}
