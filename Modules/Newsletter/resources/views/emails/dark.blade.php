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
<body style="margin:0;padding:0;background-color:#0c1427;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#0c1427">
<tr><td align="center" style="padding:30px 0;">

<table border="0" cellpadding="0" cellspacing="0" width="600" class="w-full" style="max-width:600px;">

<!-- Header -->
<tr><td align="center" style="padding:20px;">
<table border="0" cellpadding="0" cellspacing="0">
<tr><td style="font-size:28px;font-weight:800;color:#0ea5e9;">La veille<span style="color:#f97316;">.</span></td></tr>
</table>
</td></tr>

<!-- Subject bar -->
<tr><td style="padding:20px 30px;background-color:#1e293b;border-radius:8px 8px 0 0;border-left:4px solid #0ea5e9;" class="mobile-p">
<h1 style="margin:0;font-size:22px;color:#f1f5f9;font-weight:700;line-height:1.4;">{{ $subject }}</h1>
<p style="margin:8px 0 0;font-size:13px;color:#64748b;">{{ now()->translatedFormat('d F Y') }} - La veille de Stef</p>
</td></tr>

<!-- Content -->
<tr><td style="padding:30px;background-color:#1e293b;color:#cbd5e1;font-size:16px;line-height:1.7;border-radius:0 0 8px 8px;" class="mobile-p">
{!! $content !!}
</td></tr>

<!-- Spacer -->
<tr><td height="20"></td></tr>

<!-- CTA -->
<tr><td align="center" style="padding:0 30px;">
<table border="0" cellpadding="0" cellspacing="0">
<tr><td align="center" style="background:linear-gradient(135deg,#0ea5e9,#f97316);border-radius:6px;padding:14px 30px;">
<a href="https://laveille.ai" style="color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;">Visiter laveille.ai</a>
</td></tr>
</table>
</td></tr>

<!-- Spacer -->
<tr><td height="30"></td></tr>

<!-- Footer -->
<tr><td style="padding:20px;text-align:center;font-size:12px;color:#475569;">
<a href="https://www.facebook.com/LaVeilleDeStef" style="color:#64748b;text-decoration:none;">Facebook</a> &nbsp;|&nbsp; <a href="https://laveille.ai" style="color:#64748b;text-decoration:none;">Site web</a>
<p style="margin:10px 0 5px;">&copy; {{ date('Y') }} La veille. Tous droits reserves.</p>
<p style="margin:0 0 10px;">L'Ancienne-Lorette, QC, Canada</p>
<a href="{{ $unsubscribeUrl }}" style="color:#f97316;text-decoration:underline;">Se desabonner</a>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
