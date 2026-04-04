<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bienvenue sur La veille IA</title>
    <style type="text/css">
        body { margin:0 !important; padding:0 !important; background-color:#f4f4f4; }
        table, td { border-collapse:collapse; }
        img { display:block; max-width:100%; height:auto; border:0; }
        a { color:#0B7285; text-decoration:none; }
        @media only screen and (max-width:600px) {
            .email-container { width:100% !important; }
            .stack-col { display:block !important; width:100% !important; padding-bottom:12px !important; }
            .mobile-p { padding:20px 15px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f4f4f4">
<tr><td align="center" style="padding:20px 10px;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" class="email-container" style="max-width:600px;background-color:#ffffff;border-radius:8px;overflow:hidden;">

    {{-- 1. HEADER DARK --}}
    <tr>
        <td style="background-color:#0c1427;padding:30px;text-align:center;" class="mobile-p">
            <img src="{{ asset('images/logo-horizontal-white.png') }}" width="130" alt="{{ config('app.name') }}" style="width:130px;height:auto;margin:0 auto;"/>
            <h1 style="color:#ffffff;font-size:24px;margin:15px 0 0;font-weight:bold;">Bienvenue sur La veille IA !</h1>
        </td>
    </tr>

    {{-- 2. MOT DE STÉPHANE --}}
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="80" valign="top" class="stack-col" style="padding-right:20px;">
                        <img src="{{ asset('images/stephane.jpg') }}" alt="Stéphane Lapointe" width="80" height="80" style="border-radius:50%;width:80px;height:80px;"/>
                    </td>
                    <td valign="top" class="stack-col">
                        <p style="margin:0 0 12px;font-size:15px;color:#333;line-height:1.6;">Bonjour{{ ($subscriberName ?? null) ? ' '.$subscriberName : '' }},</p>
                        <p style="margin:0 0 12px;font-size:15px;color:#333;line-height:1.6;">Je suis Stéphane Lapointe, créateur de La veille. Merci de rejoindre notre communauté ! Le nouveau site <strong>laveille.ai</strong> regorge de nouveautés que j'ai hâte de vous faire découvrir.</p>
                        <p style="margin:0 0 12px;font-size:15px;color:#333;line-height:1.6;">Chaque semaine, je vous envoie une sélection personnalisée de l'essentiel en intelligence artificielle : actualités, outils, défis et ressources pour rester à jour sans y passer des heures.</p>
                        <p style="margin:0;font-size:15px;color:#333;font-weight:bold;">- Stéphane</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>

    {{-- 3. CE QUE VOUS RECEVREZ CHAQUE SEMAINE --}}
    <tr>
        <td style="padding:25px 30px;background-color:#f8fafc;" class="mobile-p">
            <p style="margin:0 0 16px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">Chaque semaine dans votre boîte</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;"><strong style="color:#0B7285;">&#x1F4E2;</strong> <strong>Le fait marquant</strong> — l'actualité IA incontournable</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;"><strong style="color:#0B7285;">&#x1F4F0;</strong> <strong>5 actualités</strong> — résumées et triées pour vous</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;"><strong style="color:#0B7285;">&#x1F3AF;</strong> <strong>Un défi prompt</strong> — un prompt à essayer immédiatement</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;"><strong style="color:#0B7285;">&#x1F527;</strong> <strong>L'outil de la semaine</strong> — testé et recommandé</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;"><strong style="color:#0B7285;">&#x1F4D6;</strong> <strong>Un terme IA expliqué</strong> — pour comprendre sans jargon</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;"><strong style="color:#0B7285;">&#x1F4DD;</strong> <strong>Un article approfondi</strong> — analyse ou tutoriel</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;"><strong style="color:#0B7285;">&#x1F381;</strong> <strong>Un outil gratuit</strong> — à essayer dans votre navigateur</td></tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>

    {{-- 4. NOUVEAUTÉS DU SITE --}}
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <p style="margin:0 0 16px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">Le nouveau laveille.ai</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td style="padding-bottom:14px;">
                    <strong style="font-size:16px;color:#1a1a2e;">Répertoire de 75+ outils IA</strong><br/>
                    <span style="font-size:14px;color:#555;">Fiches détaillées, screenshots, avis de la communauté</span>
                </td></tr>
                <tr><td style="padding-bottom:14px;">
                    <strong style="font-size:16px;color:#1a1a2e;">Glossaire IA interactif</strong><br/>
                    <span style="font-size:14px;color:#555;">140+ termes expliqués simplement avec analogies</span>
                </td></tr>
                <tr><td style="padding-bottom:20px;">
                    <strong style="font-size:16px;color:#1a1a2e;">Outils gratuits en ligne</strong><br/>
                    <span style="font-size:14px;color:#555;">Calculatrices, générateurs, constructeur de prompts</span>
                </td></tr>
                <tr><td align="center">
                    <a href="{{ config('app.url') }}" target="_blank" style="display:inline-block;background-color:#0B7285;color:#fff;padding:12px 28px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Explorer le site &rarr;</a>
                </td></tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>

    {{-- 5. PREMIER DÉFI --}}
    <tr>
        <td style="padding:25px 30px;background-color:#0c1427;" class="mobile-p">
            <p style="margin:0 0 12px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#3dc9d8;font-weight:bold;">Votre premier défi</p>
            <p style="color:#e2e8f0;font-size:16px;margin:0 0 14px;line-height:1.5;">Essayez ce prompt avec votre IA préférée :</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td style="background-color:#1e293b;border:1px solid #3dc9d8;border-radius:6px;padding:15px;margin-bottom:12px;">
                    <p style="color:#e2e8f0;font-style:italic;font-size:15px;margin:0;line-height:1.5;">Explique-moi [un concept de ton domaine] comme si j'avais 10 ans, puis comme si j'étais un expert. Compare les deux réponses et dis-moi ce que chaque version perd ou gagne en précision.</p>
                </td></tr>
            </table>
            <p style="color:#94a3b8;font-size:13px;margin:14px 0;text-align:center;">Copiez ce prompt et collez-le dans ChatGPT, Claude ou Gemini.</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr><td align="center">
                    <a href="{{ config('app.url') }}/outils/constructeur-prompts" target="_blank" style="display:inline-block;background-color:#3dc9d8;color:#0c1427;padding:10px 22px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Construire mon prompt &rarr;</a>
                </td></tr>
            </table>
        </td>
    </tr>

    {{-- 6. FOOTER --}}
    <tr>
        <td style="padding:30px;text-align:center;background-color:#fafafa;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td align="center" style="padding-bottom:20px;">
                    <a href="{{ config('app.url') }}" style="display:inline-block;background-color:#0B7285;color:#fff;padding:12px 28px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Visiter laveille.ai</a>
                </td></tr>
                <tr><td align="center" style="font-size:12px;color:#666;padding-bottom:10px;">
                    <a href="https://www.facebook.com/LaVeilleDeStef" style="color:#666;text-decoration:none;">Facebook</a>
                    &nbsp;&middot;&nbsp;
                    <a href="https://www.linkedin.com/in/lapointestephane/" style="color:#666;text-decoration:none;">LinkedIn</a>
                    &nbsp;&middot;&nbsp;
                    <a href="{{ config('app.url') }}" style="color:#666;text-decoration:none;">Site web</a>
                    &nbsp;&middot;&nbsp;
                    <a href="{{ config('app.url') }}/feed" style="color:#666;text-decoration:none;">RSS</a>
                </td></tr>
                <tr><td align="center" style="font-size:11px;color:#737373;padding-bottom:8px;">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
                </td></tr>
                <tr><td align="center">
                    <a href="{{ $unsubscribeUrl }}" style="color:#f97316;text-decoration:underline;font-size:11px;">Se désabonner</a>
                </td></tr>
            </table>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
