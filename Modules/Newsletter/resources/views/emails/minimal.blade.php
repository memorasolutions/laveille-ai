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
<body style="margin:0;padding:0;background-color:#ffffff;font-family:Georgia,'Times New Roman',serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#ffffff">
<tr><td align="center" style="padding:40px 20px;">

<table border="0" cellpadding="0" cellspacing="0" width="560" class="w-full" style="max-width:560px;">

<!-- Header -->
<tr><td style="padding-bottom:20px;border-bottom:2px solid #0ea5e9;">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
<td style="font-size:22px;font-weight:bold;color:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">La veille</td>
<td align="right" style="font-size:12px;color:#94a3b8;font-family:-apple-system,sans-serif;">{{ now()->translatedFormat('d F Y') }}</td>
</tr>
</table>
</td></tr>

<!-- Subject -->
<tr><td style="padding:30px 0 10px;">
<h1 style="margin:0;font-size:26px;line-height:1.3;color:#0f172a;font-weight:normal;">{{ $subject }}</h1>
</td></tr>

<!-- Content -->
<tr><td style="padding:10px 0 30px;color:#374151;font-size:17px;line-height:1.8;" class="mobile-p">
{!! $content !!}
</td></tr>

<!-- Separator -->
<tr><td style="border-top:1px solid #e5e7eb;padding-top:20px;font-size:13px;color:#6b7280;text-align:center;font-family:-apple-system,sans-serif;">
<p style="margin:0 0 5px;">{{ config('app.name') }} - {{ \Modules\Settings\Facades\Settings::get('seo.meta_description', 'Votre veille IA au Quebec') }}</p>
<p style="margin:0 0 10px;">{{ \Modules\Settings\Facades\Settings::get('contact.address', "L'Ancienne-Lorette, QC, Canada") }}</p>
<a href="{{ $unsubscribeUrl }}" style="color:#6b7280;text-decoration:underline;">Se desabonner</a>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
