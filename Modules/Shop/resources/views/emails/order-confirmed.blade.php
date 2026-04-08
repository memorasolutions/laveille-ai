<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family:Arial,sans-serif; line-height:1.6; color:#333; background:#f4f4f4; margin:0; padding:0;">
<div style="max-width:600px; margin:20px auto; background:#fff; border:1px solid #ddd; border-radius:8px; overflow:hidden;">
    {{-- Header --}}
    <div style="background:#0B7285; padding:20px; text-align:center;">
        <img src="https://laveille.ai/images/logo-horizontal-white.png" alt="La veille" style="max-width:150px; height:auto;">
    </div>

    {{-- Contenu --}}
    <div style="padding:30px;">
        <h1 style="color:#0B7285; font-size:22px; margin:0 0 16px;">Confirmation de commande #{{ $order->id }}</h1>
        <p>Bonjour {{ $order->shipping_address['first_name'] ?? 'client' }},</p>
        <p>Votre commande a été confirmée et sera traitée sous peu.</p>

        {{-- Items --}}
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0; border-collapse:collapse;">
            <tr style="background:#f8fafc;">
                <th style="padding:10px; text-align:left; border-bottom:2px solid #e2e8f0; font-size:13px;">Produit</th>
                <th style="padding:10px; text-align:center; border-bottom:2px solid #e2e8f0; font-size:13px;">Qté</th>
                <th style="padding:10px; text-align:right; border-bottom:2px solid #e2e8f0; font-size:13px;">Prix</th>
            </tr>
            @foreach($order->items as $item)
            <tr>
                <td style="padding:10px; border-bottom:1px solid #f1f5f9; font-size:14px;">
                    <strong>{{ $item->product?->name ?? 'Produit' }}</strong>
                    @if($item->variant_label)<br><span style="color:#94a3b8; font-size:12px;">{{ $item->variant_label }}</span>@endif
                </td>
                <td style="padding:10px; text-align:center; border-bottom:1px solid #f1f5f9; font-size:14px;">{{ $item->quantity }}</td>
                <td style="padding:10px; text-align:right; border-bottom:1px solid #f1f5f9; font-size:14px;">{{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} $</td>
            </tr>
            @endforeach
        </table>

        {{-- Totaux --}}
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:20px;">
            <tr><td style="padding:6px 0; font-size:14px;">Sous-total</td><td style="padding:6px 0; text-align:right; font-size:14px;">{{ number_format($order->subtotal, 2, ',', ' ') }} $</td></tr>
            @if($order->tax_amount > 0)
            @php $tpsAmt = round($order->subtotal * config('shop.tax.tps', 5) / 100, 2); $tvqAmt = round($order->subtotal * config('shop.tax.tvq', 9.975) / 100, 2); @endphp
            <tr><td style="padding:4px 0; font-size:13px; color:#64748b;">TPS (5%) <span style="color:#94a3b8; font-size:11px;">839145984</span></td><td style="padding:4px 0; text-align:right; font-size:13px; color:#64748b;">{{ number_format($tpsAmt, 2, ',', ' ') }} $</td></tr>
            <tr><td style="padding:4px 0; font-size:13px; color:#64748b;">TVQ (9,975%) <span style="color:#94a3b8; font-size:11px;">1221788059</span></td><td style="padding:4px 0; text-align:right; font-size:13px; color:#64748b;">{{ number_format($tvqAmt, 2, ',', ' ') }} $</td></tr>
            @endif
            @if($order->shipping_cost > 0)
            <tr><td style="padding:6px 0; font-size:14px; color:#64748b;">Livraison</td><td style="padding:6px 0; text-align:right; font-size:14px; color:#64748b;">{{ number_format($order->shipping_cost, 2, ',', ' ') }} $</td></tr>
            @endif
            <tr><td style="padding:10px 0; font-size:18px; font-weight:700; border-top:2px solid #e2e8f0;">Total</td><td style="padding:10px 0; text-align:right; font-size:18px; font-weight:700; color:#0B7285; border-top:2px solid #e2e8f0;">{{ number_format($order->total, 2, ',', ' ') }} $</td></tr>
        </table>

        {{-- Bouton suivi --}}
        <div style="text-align:center; margin:24px 0;">
            <a href="{{ $trackingUrl }}" style="display:inline-block; background:#0B7285; color:#fff; padding:12px 30px; text-decoration:none; border-radius:6px; font-weight:700; font-size:15px;">Suivre ma commande</a>
        </div>

        <p style="font-size:12px; color:#94a3b8; text-align:center;">Sur votre relevé bancaire : MEMORA* LAVEILLE.AI</p>
    </div>

    {{-- Footer --}}
    <div style="background:#f8fafc; padding:16px; text-align:center; font-size:12px; color:#94a3b8;">
        La veille — <a href="https://laveille.ai" style="color:#0B7285; text-decoration:none;">laveille.ai</a>
    </div>
</div>
</body>
</html>
