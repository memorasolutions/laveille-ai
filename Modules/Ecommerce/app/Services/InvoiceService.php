<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Ecommerce\Models\Order;

class InvoiceService
{
    public function generatePdf(Order $order): string
    {
        return $this->createPdfBuilder($order)->output();
    }

    public function downloadResponse(Order $order): Response
    {
        return $this->createPdfBuilder($order)->download('facture-'.$order->order_number.'.pdf');
    }

    public function streamResponse(Order $order): Response
    {
        return $this->createPdfBuilder($order)->stream();
    }

    /**
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function createPdfBuilder(Order $order)
    {
        $order->loadMissing([
            'items.variant.product',
            'user',
            'shippingAddress',
            'billingAddress',
        ]);

        return Pdf::loadView('ecommerce::pdf.invoice', [
            'order' => $order,
            'invoice_prefix' => config('modules.ecommerce.invoices.prefix'),
            'currency' => config('modules.ecommerce.currency'),
        ])->setPaper('a4');
    }
}
