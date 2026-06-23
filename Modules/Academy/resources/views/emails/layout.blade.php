{{--
  V5-c - Gabarit de base des notifications courriel de l'Académie (charte teal).
  Réutilisé par chaque type via @extends('academy::emails.layout'). Le lien de
  gestion des préférences (conformité Loi 25 / LCAP) est présent dans le pied.
  Variables attendues : $subject, $recipientName, $preferencesUrl + section content.
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $subject }}</title>
<style type="text/css">
@media only screen and (max-width: 600px) { .w-full { width: 100% !important; } .mobile-p { padding: 20px !important; } }
</style>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f1f5f9">
<tr><td align="center" style="padding:32px 16px;">

<table border="0" cellpadding="0" cellspacing="0" width="560" class="w-full" style="max-width:560px;background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">

<!-- En-tête -->
<tr><td style="background-color:#0d9488;padding:20px 28px;">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td style="font-size:18px;font-weight:bold;color:#ffffff;">La veille - Académie</td>
<td align="right" style="font-size:12px;color:#ccfbf1;">{{ now()->translatedFormat('d F Y') }}</td>
</tr>
</table>
</td></tr>

<!-- Contenu -->
<tr><td style="padding:28px;color:#334155;font-size:16px;line-height:1.7;" class="mobile-p">
<p style="margin:0 0 16px;">Bonjour {{ $recipientName ?: 'à vous' }},</p>

@yield('content')

@isset($ctaUrl)
<table border="0" cellpadding="0" cellspacing="0" style="margin:24px 0 8px;"><tr>
<td style="border-radius:8px;background-color:#0d9488;">
<a href="{{ $ctaUrl }}" style="display:inline-block;padding:12px 24px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;border-radius:8px;">{{ $ctaLabel ?? 'Ouvrir' }}</a>
</td>
</tr></table>
@endisset
</td></tr>

<!-- Pied -->
<tr><td style="border-top:1px solid #e2e8f0;padding:18px 28px;font-size:12px;color:#64748b;text-align:center;">
<p style="margin:0 0 8px;">Vous recevez ce courriel parce que vous participez à un cours de l'Académie de La veille.</p>
<p style="margin:0;"><a href="{{ $preferencesUrl }}" style="color:#0d9488;text-decoration:underline;">Gérer mes préférences de notification</a></p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
