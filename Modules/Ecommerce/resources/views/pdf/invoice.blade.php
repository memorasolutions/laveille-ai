<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoice_prefix }}{{ $order->order_number }}</title>
    <style type="text/css">
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; line-height: 1.4; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #6b7280; }
        .small { font-size: 11px; }
        .title { font-size: 18px; font-weight: 700; color: #111827; margin: 0; }
        .subtitle { font-size: 12px; color: #374151; margin: 2px 0 0 0; }
        .section-title { font-size: 12px; font-weight: 700; color: #111827; margin: 0 0 6px 0; }
        .card { border: 1px solid #e5e7eb; padding: 12px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; }
        .items-table th, .items-table td { border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; font-size: 11.5px; }
        .items-table th { background: #f3f4f6; color: #111827; font-weight: 700; }
        .totals-table td { padding: 4px 0; }
        .totals-table .grand td { padding-top: 8px; border-top: 1px solid #e5e7eb; font-weight: 700; font-size: 13px; color: #111827; }
        .hr { border-top: 1px solid #e5e7eb; margin: 12px 0; }
        .footer { position: fixed; left: 28px; right: 28px; bottom: 18px; text-align: center; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>

@php
    $dateFormatted = $order->created_at?->format('d/m/Y') ?? now()->format('d/m/Y');
    $ship = $order->shippingAddress;
    $bill = $order->billingAddress;
@endphp

<table>
    <tr>
        <td style="width: 60%;">
            <p class="title">{{ config('app.name') }}</p>
            <p class="subtitle muted">Facture</p>
        </td>
        <td style="width: 40%;" class="text-right">
            <table>
                <tr><td class="muted small">N° Facture</td><td class="small" style="font-weight:700;">{{ $invoice_prefix }}{{ $order->order_number }}</td></tr>
                <tr><td class="muted small">Date</td><td class="small">{{ $dateFormatted }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="hr"></div>

<table>
    <tr>
        <td style="width: 50%; padding-right: 10px;">
            <div class="card">
                <p class="section-title">Client</p>
                <div class="small">
                    <div style="font-weight: 700;">{{ $order->user->name ?? '' }}</div>
                    <div class="muted">{{ $order->user->email ?? '' }}</div>
                </div>
            </div>
        </td>
        <td style="width: 50%; padding-left: 10px;">
            <div class="card">
                <p class="section-title">Adresse de livraison</p>
                <div class="small">
                    @if($ship)
                        <div>{{ $ship->name }}</div>
                        <div>{{ $ship->address_line1 }}</div>
                        @if($ship->address_line2)<div>{{ $ship->address_line2 }}</div>@endif
                        <div>{{ $ship->postal_code }} {{ $ship->city }}, {{ $ship->state }}</div>
                        <div>{{ $ship->country }}</div>
                    @else
                        <div class="muted">-</div>
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>

<div style="height: 12px;"></div>

<table class="items-table">
    <thead>
        <tr>
            <th style="width:40%;">Produit</th>
            <th style="width:18%;">Variante</th>
            <th style="width:10%;" class="text-right">Qté</th>
            <th style="width:16%;" class="text-right">Prix</th>
            <th style="width:16%;" class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
            <tr>
                <td style="font-weight: 700;">{{ $item->product_name }}</td>
                <td class="muted">{{ $item->variant_label ?? '-' }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format((float) $item->price, 2) }} {{ $currency }}</td>
                <td class="text-right">{{ number_format((float) $item->total, 2) }} {{ $currency }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div style="height: 12px;"></div>

<table>
    <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%;">
            <table class="totals-table">
                <tr><td class="small">Sous-total</td><td class="text-right small">{{ number_format((float) $order->subtotal, 2) }} {{ $currency }}</td></tr>
                <tr><td class="small">Livraison</td><td class="text-right small">{{ number_format((float) $order->shipping_cost, 2) }} {{ $currency }}</td></tr>
                <tr><td class="small">Taxes</td><td class="text-right small">{{ number_format((float) $order->tax_amount, 2) }} {{ $currency }}</td></tr>
                @if((float) $order->discount_amount > 0)
                    <tr><td class="small">Remise</td><td class="text-right small">-{{ number_format((float) $order->discount_amount, 2) }} {{ $currency }}</td></tr>
                @endif
                <tr class="grand"><td>Total</td><td class="text-right">{{ number_format((float) $order->total, 2) }} {{ $currency }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="footer">Merci de votre achat</div>

</body>
</html>
