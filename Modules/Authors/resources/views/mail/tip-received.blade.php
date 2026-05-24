<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tip reçu</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFB;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #FFFFFF; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="background: #064E5A; color: #FFFFFF; padding: 32px 24px; text-align: center;">
                            <h1 style="margin: 0; font-size: 24px;">🎉 Bravo {{ $author->display_name ?? $author->slug }} !</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 24px;">
                            <p style="text-align: center; font-size: 48px; font-weight: 700; color: #9A2A06; margin: 0 0 16px;">{{ $amountFormatted }} $ {{ strtoupper($currency) }}</p>
                            <p style="font-size: 16px; line-height: 1.6; color: #1F2937; margin: 0 0 16px;">
                                Un lecteur t'a offert un café via Stripe.
                            </p>
                            @if($tipperEmail)
                                <p style="font-size: 14px; color: #3A4050; margin: 0 0 16px; padding: 12px; background: #F8FAFB; border-radius: 6px;">
                                    <strong>Email du donateur</strong> (pour remerciement) : <a href="mailto:{{ $tipperEmail }}" style="color: #064E5A;">{{ $tipperEmail }}</a>
                                </p>
                            @endif
                            <p style="font-size: 14px; color: #3A4050; margin: 16px 0;">
                                Le montant est crédité directement sur ton compte Stripe Connect.
                            </p>
                            <p style="margin: 24px 0; text-align: center;">
                                <a href="{{ url('/auteur/dashboard') }}" style="display: inline-block; min-height: 44px; padding: 16px 32px; background-color: #064E5A; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600;">Voir mon dashboard</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #3A4050; text-align: center;">
                            Tip reçu via laveille.ai · Conforme Loi 25 QC + RGPD EU
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
