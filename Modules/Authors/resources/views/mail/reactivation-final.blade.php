<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dernière chance avant archivage</title>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFB;font-family:'DM Sans',Arial,sans-serif;color:#064E5A;line-height:1.6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:40px auto;background-color:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
        <tr>
            <td style="padding:32px;background-color:#991B1B;color:#fff;text-align:center;">
                <h1 style="font-family:'Plus Jakarta Sans',Arial,sans-serif;font-size:24px;font-weight:700;margin:0;">Dernière chance avant archivage</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;">Bonjour {{ $name }},</p>
                <p style="margin:0 0 20px;">Ton mini-site est inactif depuis plus de 6 mois. Pour des raisons de performance, nous allons l'archiver dans <strong>30 jours</strong> sans action de ta part.</p>
                <div style="background-color:#FFF8F0;padding:16px;border-radius:6px;border-left:4px solid #C2410C;margin:0 0 24px;">
                    <strong>Important :</strong> Tes articles resteront accessibles (pas de suppression), avec un bandeau « Compte en pause ». Tu pourras réactiver à tout moment.
                </div>
                <table cellpadding="0" cellspacing="0" style="width:100%;"><tr><td style="text-align:center;">
                    <a href="{{ url('/auteur/dashboard?action=reactivate') }}" style="display:inline-block;padding:14px 32px;background-color:#C2410C;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;">↻ Réactiver maintenant</a>
                </td></tr></table>
            </td>
        </tr>
        <tr><td style="padding:20px 32px;background-color:#F8FAFB;border-top:1px solid #eaeaea;font-size:12px;color:#6b7280;text-align:center;">Reçu sur <a href="https://laveille.ai" style="color:#064E5A;">laveille.ai</a></td></tr>
    </table>
</body>
</html>
