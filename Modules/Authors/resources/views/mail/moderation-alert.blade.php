<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerte de modération</title>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFB;font-family:'DM Sans',Arial,sans-serif;color:#064E5A;line-height:1.6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:40px auto;background-color:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
        <tr>
            <td style="padding:32px;background-color:#064E5A;color:#fff;text-align:center;">
                <h1 style="font-family:'Plus Jakarta Sans',Arial,sans-serif;font-size:24px;font-weight:700;margin:0;">⚠️ Article flaggé</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;">Un article publié sur laveille.ai a été signalé par le bot de modération.</p>
                <p style="margin:0 0 8px;font-weight:bold;font-size:18px;color:#C2410C;">{{ $article->title ?? 'Sans titre' }}</p>
                <p style="margin:0 0 16px;font-size:14px;">Par : {{ $article->user->name ?? 'Inconnu' }}</p>
                <p style="margin:0 0 12px;"><strong>Statut :</strong> {{ $status }}</p>
                @if($summary)
                    <div style="background-color:#F8FAFB;border-left:4px solid #C2410C;padding:12px 16px;margin:16px 0;font-style:italic;">
                        {{ $summary }}
                    </div>
                @endif
                <div style="background-color:#FFF8F0;border:1px solid #E8C8A8;padding:12px 16px;margin:16px 0;font-size:13px;">
                    <strong>Extrait :</strong>
                    <p style="margin:8px 0 0;">{{ \Illuminate\Support\Str::limit(strip_tags($article->content ?? ''), 200, '…') }}</p>
                </div>
                <p style="margin:24px 0 16px;font-size:14px;">Actions :</p>
                <table cellpadding="0" cellspacing="0" style="width:100%;">
                    <tr>
                        <td style="width:33%;padding:0 4px;"><a href="{{ url('/admin/articles/'.$article->id.'?action=approve') }}" style="display:block;padding:12px 0;background-color:#064E5A;color:#fff;text-align:center;text-decoration:none;border-radius:6px;font-weight:bold;">✓ Approuver</a></td>
                        <td style="width:34%;padding:0 4px;"><a href="{{ url('/admin/articles/'.$article->id.'?action=depublish') }}" style="display:block;padding:12px 0;background-color:#C2410C;color:#fff;text-align:center;text-decoration:none;border-radius:6px;font-weight:bold;">✗ Dépublier</a></td>
                        <td style="width:33%;padding:0 4px;"><a href="{{ url('/admin/users/'.($article->user_id ?? 0).'?action=ban') }}" style="display:block;padding:12px 0;background-color:#991B1B;color:#fff;text-align:center;text-decoration:none;border-radius:6px;font-weight:bold;">⛔ Bannir</a></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding:20px 32px;background-color:#F8FAFB;border-top:1px solid #eaeaea;font-size:12px;color:#6b7280;text-align:center;">
                Reçu sur <a href="https://laveille.ai" style="color:#064E5A;text-decoration:underline;">laveille.ai</a>
            </td>
        </tr>
    </table>
</body>
</html>
