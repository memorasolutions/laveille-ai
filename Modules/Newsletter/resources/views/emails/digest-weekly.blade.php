<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $subject }}</title>
    <style type="text/css">
        body { margin:0 !important; padding:0 !important; background-color:#f4f4f4; }
        table, td { border-collapse:collapse; }
        img { display:block; max-width:100%; height:auto; border:0; }
        a { color:#0B7285; text-decoration:none; }
        @media only screen and (max-width:600px) {
            .email-container { width:100% !important; }
            .stack-col { display:block !important; width:100% !important; padding-right:0 !important; padding-bottom:15px !important; }
            .stack-col img { width:100% !important; }
            .mobile-p { padding:20px 15px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f4f4f4">
<tr><td align="center" style="padding:20px 10px;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" class="email-container" style="max-width:600px;background-color:#ffffff;border-radius:8px;overflow:hidden;">

    {{-- ============================================================ --}}
    {{-- 1. HEADER DARK                                                --}}
    {{-- ============================================================ --}}
    <tr>
        <td style="background-color:#0c1427;padding:24px 30px;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="font-family:Arial,sans-serif;font-size:22px;font-weight:bold;color:#f1f5f9;">
                        La <span style="color:#0B7285;">veille</span><span style="color:#f97316;">.</span>
                    </td>
                    <td align="right" style="font-family:Arial,sans-serif;font-size:12px;color:#94a3b8;">
                        Digest #{{ $weekNumber ?? '?' }}<br/>{{ now()->translatedFormat('j F Y') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ============================================================ --}}
    {{-- 2. LE FAIT MARQUANT (image + texte)                           --}}
    {{-- ============================================================ --}}
    @if($highlight ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <p style="margin:0 0 12px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;font-family:Arial,sans-serif;">Le fait marquant</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="200" valign="top" class="stack-col" style="padding-right:20px;">
                        @php $hlImg = $highlight->image_path ?? null; @endphp
                        <img src="{{ $hlImg ? asset($hlImg) : asset('images/og-image.png') }}" width="200" alt="" style="border-radius:6px;width:200px;"/>
                    </td>
                    <td valign="top" class="stack-col" style="font-family:Arial,sans-serif;">
                        <h2 style="margin:0 0 8px;font-size:20px;line-height:1.3;color:#1a1a2e;">{{ $highlight->seo_title ?? $highlight->title ?? '' }}</h2>
                        <p style="margin:0 0 10px;font-size:14px;color:#555;line-height:1.5;">{{ Str::limit($highlight->summary ?? strip_tags($highlight->content ?? ''), 150) }}</p>
                        <a href="{{ $highlight->url ?? route('news.show', $highlight->slug ?? '') }}" style="color:#0B7285;font-weight:bold;font-size:14px;">Lire &rarr;</a>
                        @if($highlight->source_name ?? null)
                        <p style="margin:8px 0 0;font-size:11px;color:#999;">{{ $highlight->source_name }}</p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- ============================================================ --}}
    {{-- 3. ACTUALITES DE LA SEMAINE (miniatures 80x80)                --}}
    {{-- ============================================================ --}}
    @if(($topNews ?? null) && $topNews->count())
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <h3 style="margin:0 0 16px;font-size:14px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-family:Arial,sans-serif;">Actualites de la semaine</h3>
            @foreach($topNews as $news)
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:12px;">
                <tr>
                    <td width="80" valign="top" style="padding-right:12px;">
                        @php $nImg = $news->image_path ?? null; @endphp
                        <img src="{{ $nImg ? asset($nImg) : asset('images/og-image.png') }}" width="80" height="80" alt="" style="border-radius:6px;width:80px;height:80px;object-fit:cover;"/>
                    </td>
                    <td valign="middle" style="font-family:Arial,sans-serif;">
                        <a href="{{ $news->url ?? route('news.show', $news->slug ?? '') }}" style="color:#1a1a2e;font-size:14px;font-weight:bold;text-decoration:none;line-height:1.3;">{{ $news->seo_title ?? $news->title ?? '' }}</a>
                        @if($news->source_name ?? null)
                        <br/><span style="font-size:11px;color:#999;">{{ $news->source_name }}</span>
                        @endif
                    </td>
                </tr>
            </table>
            @endforeach
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- ============================================================ --}}
    {{-- 4. OUTIL DE LA SEMAINE (screenshot + badge pricing)           --}}
    {{-- ============================================================ --}}
    @if($toolOfWeek ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#f0fdfa;" class="mobile-p">
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;font-family:Arial,sans-serif;">Outil de la semaine</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="220" valign="top" class="stack-col" style="padding-right:20px;">
                        @php $tImg = $toolOfWeek->screenshot ?? null; @endphp
                        <img src="{{ $tImg ? (str_starts_with($tImg, 'http') ? $tImg : asset($tImg)) : asset('images/og-image.png') }}" width="220" alt="" style="border-radius:6px;width:220px;"/>
                    </td>
                    <td valign="top" class="stack-col" style="font-family:Arial,sans-serif;">
                        <h3 style="margin:0 0 6px;font-size:18px;color:#0B7285;">{{ $toolOfWeek->name }}</h3>
                        <p style="margin:0 0 12px;font-size:14px;color:#555;line-height:1.5;">{{ Str::limit(strip_tags($toolOfWeek->short_description ?? $toolOfWeek->description ?? ''), 150) }}</p>
                        @php
                            $pColor = match(strtolower($toolOfWeek->pricing ?? '')) {
                                'free', 'gratuit' => '#10b981', 'freemium' => '#f97316', default => '#6b7280',
                            };
                            $pLabel = match(strtolower($toolOfWeek->pricing ?? '')) {
                                'free', 'gratuit' => 'Gratuit', 'freemium' => 'Freemium', default => 'Payant',
                            };
                        @endphp
                        <span style="display:inline-block;padding:3px 10px;background-color:{{ $pColor }};color:#fff;font-size:11px;border-radius:3px;font-weight:bold;">{{ $pLabel }}</span>
                        <br/><br/>
                        <a href="{{ route('directory.show', $toolOfWeek->slug) }}" style="display:inline-block;background-color:#0B7285;color:#fff;padding:10px 20px;border-radius:4px;font-weight:bold;font-size:13px;text-decoration:none;">Decouvrir &rarr;</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- ============================================================ --}}
    {{-- 5. ARTICLE A LIRE (image + texte)                             --}}
    {{-- ============================================================ --}}
    @if($featuredArticle ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;font-family:Arial,sans-serif;">A lire cette semaine</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="180" valign="top" class="stack-col" style="padding-right:20px;">
                        @php $aImg = $featuredArticle->featured_image ?? null; @endphp
                        <img src="{{ $aImg ? asset($aImg) : asset('images/og-image.png') }}" width="180" alt="" style="border-radius:6px;width:180px;"/>
                    </td>
                    <td valign="top" class="stack-col" style="font-family:Arial,sans-serif;">
                        <a href="{{ route('blog.show', $featuredArticle->slug) }}" style="color:#1a1a2e;font-size:16px;font-weight:bold;text-decoration:none;line-height:1.3;">{{ $featuredArticle->title }}</a>
                        <p style="margin:8px 0 12px;font-size:14px;color:#555;line-height:1.5;">{{ Str::limit(strip_tags($featuredArticle->excerpt ?? $featuredArticle->content ?? ''), 120) }}</p>
                        <a href="{{ route('blog.show', $featuredArticle->slug) }}" style="color:#0B7285;font-weight:bold;font-size:14px;">Lire l'article &rarr;</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- ============================================================ --}}
    {{-- 6. LE SAVIEZ-VOUS? (bandeau teal)                             --}}
    {{-- ============================================================ --}}
    @if($didYouKnow ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#0B7285;" class="mobile-p">
            <p style="margin:0 0 6px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,0.7);font-weight:bold;font-family:Arial,sans-serif;">Le saviez-vous ?</p>
            <p style="margin:0;font-size:16px;color:#ffffff;line-height:1.5;font-family:Arial,sans-serif;">
                <strong>{{ $didYouKnow->term ?? $didYouKnow->name ?? '' }}</strong> — {{ Str::limit(strip_tags($didYouKnow->definition ?? $didYouKnow->description ?? ''), 200) }}
            </p>
        </td>
    </tr>
    @endif

    {{-- ============================================================ --}}
    {{-- 7. FOOTER                                                      --}}
    {{-- ============================================================ --}}
    <tr>
        <td style="padding:30px;text-align:center;background-color:#fafafa;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td align="center" style="padding-bottom:20px;">
                    <a href="https://laveille.ai" style="display:inline-block;background-color:#0B7285;color:#fff;padding:12px 28px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;font-family:Arial,sans-serif;">Visiter laveille.ai</a>
                </td></tr>
                <tr><td align="center" style="font-family:Arial,sans-serif;font-size:12px;color:#999;padding-bottom:10px;">
                    <a href="https://www.facebook.com/LaVeilleDeStef" style="color:#999;text-decoration:none;">Facebook</a>
                    &nbsp;&middot;&nbsp;
                    <a href="https://www.linkedin.com/in/lapointestephane/" style="color:#999;text-decoration:none;">LinkedIn</a>
                    &nbsp;&middot;&nbsp;
                    <a href="https://laveille.ai" style="color:#999;text-decoration:none;">Site web</a>
                </td></tr>
                <tr><td align="center" style="font-family:Arial,sans-serif;font-size:11px;color:#bbb;padding-bottom:8px;">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits reserves.
                </td></tr>
                <tr><td align="center">
                    <a href="{{ $unsubscribeUrl }}" style="color:#f97316;text-decoration:underline;font-size:11px;font-family:Arial,sans-serif;">Se desabonner</a>
                </td></tr>
            </table>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
