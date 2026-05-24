<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nouvelle mention reçue</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFB;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; background: #FFFFFF; border-radius: 12px;">
                    <tr>
                        <td style="padding: 32px 24px;">
                            <h1 style="color: #064E5A; font-size: 24px; margin: 0 0 16px;">🔗 Nouvelle mention reçue !</h1>
                            <p style="line-height: 1.6; color: #1F2937;">
                                Bonne nouvelle <strong>{{ $author->display_name ?? $author->slug }}</strong>, ton article a été cité par un autre site :
                            </p>
                            <blockquote style="background: #F8FAFB; border-left: 4px solid #064E5A; padding: 16px; margin: 24px 0; font-style: italic; color: #475569; border-radius: 0 8px 8px 0;">
                                <p style="margin: 0 0 8px;"><strong>{{ $webmention->source_author_name ?? 'Anonyme' }}</strong> · <a href="{{ e($webmention->source_url) }}" style="color: #064E5A;">{{ \Illuminate\Support\Str::limit($webmention->source_url, 60) }}</a></p>
                                @if($webmention->source_excerpt)
                                    <p style="margin: 8px 0 0;">« {{ \Illuminate\Support\Str::limit($webmention->source_excerpt, 200) }} »</p>
                                @endif
                            </blockquote>
                            <p style="line-height: 1.6; color: #1F2937;">
                                Article cité : <strong>{{ $post->title }}</strong>
                            </p>
                            <p style="margin: 24px 0;">
                                <a href="{{ url('/@'.$author->slug.'/'.$post->slug) }}" style="display: inline-block; background: #064E5A; color: #FFFFFF; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; min-height: 44px;">Voir mon article</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #3A4050; text-align: center;">
                            Reçu via webmention IndieWeb · Conforme Loi 25 + RGPD
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
