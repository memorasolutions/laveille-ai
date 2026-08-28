<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>On t'attend, {{ $name }}</title>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFB;font-family:'DM Sans',Arial,sans-serif;color:#064E5A;line-height:1.6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:40px auto;background-color:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
        <tr>
            <td style="padding:32px;background-color:#064E5A;color:#fff;text-align:center;">
                <h1 style="font-family:'Plus Jakarta Sans',Arial,sans-serif;font-size:24px;font-weight:700;margin:0;">On t'attend, {{ $name }}</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;">Ça fait 2 mois que tu n'as pas écrit sur ton mini-site laveille.ai. On espère que tout va bien.</p>
                <p style="margin:0 0 24px;">Tes anciens articles continuent d'attirer des lecteurs – il y a une audience qui attend ton prochain post.</p>
                <table cellpadding="0" cellspacing="0" style="width:100%;"><tr><td style="text-align:center;">
                    <a href="{{ url('/auteur/dashboard') }}" style="display:inline-block;padding:14px 32px;background-color:#C2410C;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;">✍️ Reprendre la rédaction</a>
                </td></tr></table>
            </td>
        </tr>
        <tr><td style="padding:20px 32px;background-color:#F8FAFB;border-top:1px solid #eaeaea;font-size:12px;color:#6b7280;text-align:center;">Reçu sur <a href="https://laveille.ai" style="color:#064E5A;">laveille.ai</a></td></tr>
    </table>
</body>
</html>
