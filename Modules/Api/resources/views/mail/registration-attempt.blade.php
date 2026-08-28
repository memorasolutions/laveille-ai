{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Courriel transactionnel sécurité — tentative d'inscription sur compte existant (#254 v1.19.15) --}}
<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentative d'inscription sur {{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background:#F8FAFB;font-family:'Helvetica Neue',Arial,sans-serif;color:#1a1d23;line-height:1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F8FAFB;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#ffffff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden;">
                    <tr>
                        <td style="background:#064E5A;padding:24px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:600;line-height:1.3;">
                                Tentative d'inscription sur {{ $appName }}
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px;color:#1a1d23;font-size:16px;">
                            <p style="margin:0 0 16px;">Bonjour {{ $userName }},</p>

                            <p style="margin:0 0 16px;">
                                Quelqu'un (peut-être vous) a tenté de créer un compte sur
                                <strong>{{ $appName }}</strong> avec votre adresse courriel.
                            </p>

                            <p style="margin:0 0 16px;">
                                <strong>Vous avez déjà un compte chez nous.</strong>
                                Aucun second compte n'a été créé.
                            </p>

                            <p style="margin:0 0 24px;">
                                Si c'était vous et que vous vouliez vous connecter, utilisez le bouton ci-dessous :
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" style="background:#064E5A;border-radius:8px;">
                                        <a href="{{ $loginUrl }}"
                                           style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:16px;min-height:44px;line-height:44px;"
                                           role="button">
                                            Se connecter à mon compte
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px;color:#555;font-size:14px;">
                                Si ce n'était pas vous, vous pouvez ignorer ce courriel en toute sécurité.
                                Aucune action n'est requise – votre compte n'a pas été modifié.
                            </p>

                            <p style="margin:24px 0 0;color:#555;font-size:14px;">
                                Cordialement,<br>
                                L'équipe {{ $appName }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#F8FAFB;padding:16px 28px;text-align:center;color:#888;font-size:12px;border-top:1px solid #e5e7eb;">
                            Courriel transactionnel de sécurité – envoyé suite à une tentative d'inscription.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
