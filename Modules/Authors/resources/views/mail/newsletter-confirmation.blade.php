<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirme ton abonnement</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFB;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #FFFFFF; border-radius: 12px;">
                    <tr>
                        <td style="padding: 32px 32px 16px;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0B7285;">La veille de Stef</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <p style="margin: 0 0 16px; font-size: 16px; line-height: 1.5; color: #1F2937;">
                                Bonjour&nbsp;!
                            </p>
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.5; color: #475569;">
                                Tu viens de t'abonner à la newsletter de
                                <strong>{{ $author->display_name ?? $author->slug }}</strong>.
                                Clique sur le bouton ci-dessous pour confirmer ton abonnement&nbsp;:
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <a href="{{ $confirmUrl }}" target="_blank" style="display: inline-block; min-height: 44px; padding: 16px 32px; background-color: #0B7285; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                            Confirmer mon abonnement
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 24px 0 0; font-size: 14px; color: #64748B;">
                                Ce lien expire dans 7 jours. Si tu n'as pas demandé cet abonnement, ignore ce courriel.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px 32px; border-top: 1px solid #E2E8F0;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.4; color: #64748B;">
                                Hebdo · 0 spam · désabo 1-clic ·
                                <a href="{{ $unsubscribeUrl }}" target="_blank" style="color: #64748B; text-decoration: underline;">Se désabonner</a><br>
                                MEMORA solutions · Québec, Canada · <a href="https://laveille.ai/confidentialite" target="_blank" style="color: #64748B; text-decoration: underline;">Politique de confidentialité</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
