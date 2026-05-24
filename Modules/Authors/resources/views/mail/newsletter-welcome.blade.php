<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bienvenue</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFB;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; background: #FFFFFF; border-radius: 12px;">
                    <tr>
                        <td style="background: #064E5A; color: #FFFFFF; padding: 32px 24px; text-align: center;">
                            <h1 style="margin: 0; font-size: 28px;">🎉 Bienvenue !</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 24px; color: #1F2937; line-height: 1.6;">
                            <p style="margin: 0 0 16px;">
                                Bonjour ! Tu es maintenant officiellement abonné·e à la newsletter de
                                <strong>{{ $author->display_name ?? $author->slug }}</strong>.
                                Tu recevras les billets directement dans ta boîte courriel.
                            </p>
                            <p style="margin: 24px 0; text-align: center;">
                                <a href="{{ url('/@'.$author->slug) }}" style="display: inline-block; min-height: 44px; padding: 16px 32px; background: #064E5A; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600;">🌐 Voir mon profil</a>
                            </p>
                            <p style="margin: 24px 0 8px; color: #064E5A; font-weight: 600;">Que peux-tu attendre ?</p>
                            <ul style="padding-left: 24px; margin: 0; color: #3A4050;">
                                <li style="margin-bottom: 8px;">📅 Fréquence : hebdomadaire</li>
                                <li style="margin-bottom: 8px;">🚫 Spam : zéro</li>
                                <li style="margin-bottom: 8px;">↩️ Désabo : 1-clic en bas de chaque email</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #3A4050; text-align: center;">
                            MEMORA solutions · Québec, Canada · Loi 25 + RGPD<br>
                            <a href="{{ $unsubscribeUrl }}" style="color: #064E5A; text-decoration: underline;">Se désabonner</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
