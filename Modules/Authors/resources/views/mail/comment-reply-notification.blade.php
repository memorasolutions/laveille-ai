<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle réponse à votre commentaire</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F8FAFB; color: #1F2937; margin: 0; padding: 0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFB;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; background: #FFFFFF; border-radius: 12px;">
                    <tr>
                        <td style="padding: 32px 24px;">
                            <h1 style="color: #0B7285; font-size: 24px; margin: 0 0 16px;">💬 Nouvelle réponse à votre commentaire</h1>
                            <p style="line-height: 1.6; margin: 16px 0; color: #1F2937;">
                                <strong>{{ $comment->author_name }}</strong> a répondu à votre commentaire :
                            </p>
                            <blockquote style="border-left: 4px solid #0B7285; padding: 12px 16px; margin: 24px 0; font-style: italic; color: #475569; background-color: #F1F9F9;">
                                {{ Str::limit($parent->body, 200) }}
                            </blockquote>
                            <p style="margin: 24px 0 8px; color: #1F2937;"><strong>Réponse :</strong></p>
                            <blockquote style="border-left: 4px solid #C2410C; padding: 12px 16px; margin: 8px 0 24px; color: #1F2937; background-color: #FFF8F4;">
                                {{ Str::limit($comment->body, 300) }}
                            </blockquote>
                            <p style="margin: 24px 0;">
                                <a href="{{ $commentUrl }}" style="display: inline-block; min-height: 44px; padding: 16px 32px; background-color: #0B7285; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600;">Lire la conversation</a>
                            </p>
                            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B;">
                                <a href="{{ url('/notification-preferences') }}" style="color: #64748B; text-decoration: underline;">Modifier vos préférences de notification</a><br>
                                MEMORA solutions · Québec, Canada
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
