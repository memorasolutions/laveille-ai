@php
    $firstName = $order->shipping_address['first_name'] ?? 'client';
    try {
        $ctaUrl = \Route::has('shop.checkout.resume')
            ? route('shop.checkout.resume', ['order' => $order->order_number])
            : url('/boutique');
    } catch (\Throwable $e) {
        $ctaUrl = url('/boutique');
    }
    try {
        $unsubscribeUrl = \Route::has('shop.unsubscribe-reminders')
            ? route('shop.unsubscribe-reminders', ['token' => md5($order->email)])
            : null;
    } catch (\Throwable $e) {
        $unsubscribeUrl = null;
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@if($variant === '24h')Votre panier vous attend !@else Dernière chance pour votre commande @endif</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333333;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <tr>
                        <td align="center" style="background-color: #0B7285; padding: 30px 20px;">
                            <img src="{{ asset('images/logo-email-white.png') }}?v={{ time() }}" width="200" alt="La veille de Stef" style="display: block; border: 0; outline: none; text-decoration: none;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px; font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333333;">
                            @if($variant === '24h')
                                <h1 style="margin: 0 0 20px 0; font-size: 24px; color: #0B7285; line-height: 1.3;">
                                    Votre panier vous attend, {{ $firstName }}&nbsp;!
                                </h1>
                                <p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
                                    Vous avez initié une commande il y a 24&nbsp;heures mais le paiement n'a pas été finalisé. Pas de souci, votre panier est toujours disponible&nbsp;!
                                </p>
                            @else
                                <h1 style="margin: 0 0 20px 0; font-size: 24px; color: #DC2626; line-height: 1.3;">
                                    Dernière chance, {{ $firstName }} – votre panier sera bientôt annulé
                                </h1>
                                <p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
                                    Votre commande est en attente depuis 72&nbsp;heures. Nous allons bientôt la retirer pour libérer l'inventaire.
                                </p>
                            @endif

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 20px 0; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
                                <tr>
                                    <td style="background-color: #f8fafc; padding: 12px 16px; font-size: 14px; font-weight: bold; color: #0B7285; border-bottom: 1px solid #e2e8f0;">
                                        Commande #{{ $order->order_number }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding: 10px 16px; font-size: 12px; font-weight: bold; color: #666666; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; width: 50%;">Produit</td>
                                                <td align="center" style="padding: 10px 16px; font-size: 12px; font-weight: bold; color: #666666; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; width: 15%;">Qté</td>
                                                <td align="right" style="padding: 10px 16px; font-size: 12px; font-weight: bold; color: #666666; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; width: 35%;">Prix</td>
                                            </tr>
                                            @foreach($order->items as $item)
                                                <tr>
                                                    <td style="padding: 12px 16px; font-size: 14px; color: #333333; border-bottom: 1px solid #f0f0f0;">
                                                        {{ $item->product?->name ?? 'Produit' }}
                                                        @if($item->variant_label)<br><span style="font-size: 12px; color: #888888;">{{ $item->variant_label }}</span>@endif
                                                    </td>
                                                    <td align="center" style="padding: 12px 16px; font-size: 14px; color: #333333; border-bottom: 1px solid #f0f0f0;">{{ $item->quantity }}</td>
                                                    <td align="right" style="padding: 12px 16px; font-size: 14px; color: #333333; border-bottom: 1px solid #f0f0f0;">{{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }}&nbsp;$</td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td colspan="2" align="right" style="padding: 14px 16px; font-size: 16px; font-weight: bold; color: #333333;">Total&nbsp;:</td>
                                                <td align="right" style="padding: 14px 16px; font-size: 16px; font-weight: bold; color: #0B7285;">{{ number_format($order->total, 2, ',', ' ') }}&nbsp;$</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $ctaUrl }}" target="_blank" style="display: inline-block; padding: 16px 40px; background-color: {{ $variant === '24h' ? '#0B7285' : '#DC2626' }}; color: #ffffff; font-family: Arial, Helvetica, sans-serif; font-size: 18px; font-weight: bold; text-decoration: none; border-radius: 6px; text-align: center;">
                                            @if($variant === '24h')Compléter ma commande →@else Finaliser maintenant →@endif
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            @if($variant === '24h')
                                <p style="margin: 0; font-size: 14px; color: #555555; line-height: 1.6;">
                                    Notre boutique québécoise offre des produits imprimés à la demande avec des délais rapides.
                                </p>
                            @else
                                <p style="margin: 0; font-size: 14px; color: #555555; line-height: 1.6;">
                                    Des questions&nbsp;? Répondez à ce courriel, nous sommes à Québec.
                                </p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px; text-align: center; font-family: Arial, Helvetica, sans-serif;">
                            <p style="margin: 0 0 10px 0; font-size: 13px; color: #888888; line-height: 1.5;">
                                Reçu par courtoisie — La veille de Stef
                            </p>
                            <p style="margin: 0 0 10px 0; font-size: 12px; line-height: 1.5;">
                                @if($unsubscribeUrl)
                                    <a href="{{ $unsubscribeUrl }}" target="_blank" style="color: #0B7285; text-decoration: underline; font-size: 12px;">Se désabonner des rappels</a>
                                @else
                                    <a href="mailto:info@memora.ca?subject=Désabonnement%20rappels%20panier" style="color: #0B7285; text-decoration: underline; font-size: 12px;">Se désabonner des rappels</a>
                                @endif
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #aaaaaa; line-height: 1.5;">
                                &copy; {{ date('Y') }} MEMORA solutions
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
