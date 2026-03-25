<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ $subject }}</title>
<style type="text/css">
@media only screen and (max-width: 600px) { .w-full { width: 100% !important; } .mobile-p { padding: 20px !important; } }
@media (prefers-color-scheme: dark) { .dark-bg { background-color: #1e293b !important; } .dark-card { background-color: #0f172a !important; } .dark-text { color: #f1f5f9 !important; } }
</style>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f3f4f6" class="dark-bg">
<tr><td align="center" style="padding:20px 0;">

<table border="0" cellpadding="0" cellspacing="0" width="600" class="w-full" style="max-width:600px;background-color:#ffffff;border-radius:8px;overflow:hidden;">

<!-- Header -->
<tr><td align="center" style="padding:30px 20px;background-color:#ffffff;" class="dark-card">
<table border="0" cellpadding="0" cellspacing="0">
<tr><td align="center" style="font-size:28px;font-weight:800;letter-spacing:-0.5px;color:#0ea5e9;">La veille<span style="color:#f97316;">.</span></td></tr>
<tr><td align="center" style="font-size:13px;color:#64748b;padding-top:5px;text-transform:uppercase;letter-spacing:1px;">Votre veille IA au Quebec</td></tr>
</table>
</td></tr>

<!-- Hero gradient -->
<tr><td align="center" style="background:linear-gradient(135deg,#0ea5e9 0%,#f97316 100%);padding:40px 30px;" class="mobile-p">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr><td align="center" style="color:#ffffff;font-size:24px;font-weight:bold;line-height:1.4;">{{ $subject }}</td></tr>
</table>
</td></tr>

<!-- Content -->
<tr><td style="padding:40px;color:#334155;font-size:16px;line-height:1.7;background-color:#ffffff;" class="mobile-p dark-card dark-text">
{!! $content !!}
</td></tr>

<!-- Separator -->
<tr><td align="center" style="padding-bottom:30px;background-color:#ffffff;" class="dark-card">
<table border="0" cellpadding="0" cellspacing="0"><tr><td height="4" width="60" style="background-color:#f97316;border-radius:2px;"></td></tr></table>
</td></tr>

<!-- Footer -->
<tr><td style="padding:30px;background-color:#1e293b;color:#94a3b8;font-size:13px;text-align:center;">
<a href="https://www.facebook.com/LaVeilleDeStef" style="text-decoration:none;color:#94a3b8;">Facebook</a> &nbsp;|&nbsp; <a href="https://laveille.ai" style="text-decoration:none;color:#94a3b8;">laveille.ai</a>
<p style="margin:15px 0 5px;">&copy; {{ date('Y') }} La veille. Tous droits reserves.</p>
<p style="margin:0 0 15px;">L'Ancienne-Lorette, QC, Canada</p>
<a href="{{ $unsubscribeUrl }}" style="color:#f97316;text-decoration:underline;font-size:12px;">Se desabonner</a>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
