<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bilan hebdo</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFB;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; background: #FFFFFF; border-radius: 12px;">
                    <tr>
                        <td style="background: #064E5A; color: #FFFFFF; padding: 32px 24px; text-align: center;">
                            <h1 style="margin: 0 0 8px; font-size: 24px;">📊 Bonjour {{ $author->display_name }}</h1>
                            <p style="margin: 0; opacity: 0.9; font-size: 14px;">Du {{ $weekStart->translatedFormat('d M') }} au {{ $weekEnd->translatedFormat('d M Y') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 24px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="padding: 8px; width: 50%;">
                                        <div style="background: #F8FAFB; padding: 16px; border-radius: 8px; text-align: center;">
                                            <div style="color: #064E5A; font-size: 24px; font-weight: 700;">✍️ {{ $stats['posts_published'] ?? 0 }}</div>
                                            <div style="color: #3A4050; font-size: 13px; margin-top: 4px;">articles publiés</div>
                                        </div>
                                    </td>
                                    <td style="padding: 8px; width: 50%;">
                                        <div style="background: #F8FAFB; padding: 16px; border-radius: 8px; text-align: center;">
                                            <div style="color: #064E5A; font-size: 24px; font-weight: 700;">💬 {{ $stats['comments_received'] ?? 0 }}</div>
                                            <div style="color: #3A4050; font-size: 13px; margin-top: 4px;">commentaires</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; width: 50%;">
                                        <div style="background: #F8FAFB; padding: 16px; border-radius: 8px; text-align: center;">
                                            <div style="color: #064E5A; font-size: 24px; font-weight: 700;">📬 {{ $stats['subscribers_gained'] ?? 0 }}</div>
                                            <div style="color: #3A4050; font-size: 13px; margin-top: 4px;">nouveaux abonnés</div>
                                        </div>
                                    </td>
                                    <td style="padding: 8px; width: 50%;">
                                        <div style="background: #F8FAFB; padding: 16px; border-radius: 8px; text-align: center;">
                                            <div style="color: #064E5A; font-size: 24px; font-weight: 700;">🔗 {{ $stats['webmentions_verified'] ?? 0 }}</div>
                                            <div style="color: #3A4050; font-size: 13px; margin-top: 4px;">mentions web</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px; width: 50%;">
                                        <div style="background: #F8FAFB; padding: 16px; border-radius: 8px; text-align: center;">
                                            <div style="color: #064E5A; font-size: 24px; font-weight: 700;">☕ {{ $stats['tips_count'] ?? 0 }}</div>
                                            <div style="color: #3A4050; font-size: 13px; margin-top: 4px;">tips reçus</div>
                                        </div>
                                    </td>
                                    <td style="padding: 8px; width: 50%;">
                                        <div style="background: #F8FAFB; padding: 16px; border-radius: 8px; text-align: center;">
                                            <div style="color: #064E5A; font-size: 24px; font-weight: 700;">🌐 {{ $stats['affiliate_clicks'] ?? 0 }}</div>
                                            <div style="color: #3A4050; font-size: 13px; margin-top: 4px;">clicks affiliés</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 32px 0 0; text-align: center;">
                                <a href="{{ url('/auteur/dashboard') }}" style="display: inline-block; background: #064E5A; color: #FFFFFF; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; min-height: 44px;">📊 Voir mon dashboard complet</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #3A4050; text-align: center;">
                            Bilan généré par laveille.ai · {{ $weekEnd->translatedFormat('d M Y') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
