<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veux-tu garder ton espace ?</title>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFB;font-family:'DM Sans',Arial,sans-serif;color:#064E5A;line-height:1.6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:40px auto;background-color:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
        <tr>
            <td style="padding:32px;background-color:#064E5A;color:#fff;text-align:center;">
                <h1 style="font-family:'Plus Jakarta Sans',Arial,sans-serif;font-size:24px;font-weight:700;margin:0;">Veux-tu garder ton espace ?</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;">Salut {{ $name }}, ton mini-site sur laveille.ai est inactif depuis plus de 3 mois.</p>
                <p style="margin:0 0 24px;">On veut t'offrir le choix : garder ton espace actif, ou prendre une pause temporaire sans rien perdre.</p>
                <table cellpadding="0" cellspacing="0" style="width:100%;margin-top:16px;">
                    <tr>
                        <td style="width:50%;padding:0 8px;"><a href="{{ url('/auteur/dashboard') }}" style="display:block;padding:14px 0;background-color:#064E5A;color:#fff;text-align:center;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;">✓ Garder actif</a></td>
                        <td style="width:50%;padding:0 8px;"><a href="{{ url('/auteur/dashboard?action=pause') }}" style="display:block;padding:14px 0;background-color:#C2410C;color:#fff;text-align:center;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;">⏸ Pause temporaire</a></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td style="padding:20px 32px;background-color:#F8FAFB;border-top:1px solid #eaeaea;font-size:12px;color:#6b7280;text-align:center;">Reçu sur <a href="https://laveille.ai" style="color:#064E5A;">laveille.ai</a></td></tr>
    </table>
</body>
</html>
