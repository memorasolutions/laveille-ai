<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $subject }}</title>
    <style type="text/css">
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .mobile-p { padding: 20px 15px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#0c1427;color:#cbd5e1;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;line-height:1.6;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#0c1427">
        <tr>
            <td align="center" valign="top" style="padding:20px 10px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" class="email-container" bgcolor="#1e293b" style="max-width:600px;border-radius:8px;overflow:hidden;">

                    {{-- HEADER --}}
                    <tr>
                        <td style="padding:30px 30px 20px 30px;" class="mobile-p">
                            <h1 style="font-size:24px;margin:0;font-weight:bold;color:#f1f5f9;">
                                La <span style="color:#0B7285;">veille</span><span style="color:#f97316;">.</span>
                            </h1>
                            <p style="margin:5px 0 0 0;font-size:14px;color:#94a3b8;">
                                Digest #{{ $weekNumber ?? '?' }} — {{ now()->translatedFormat('j F Y') }}
                            </p>
                        </td>
                    </tr>

                    <tr><td height="1" bgcolor="#0B7285"></td></tr>

                    {{-- 1. LE FAIT MARQUANT --}}
                    @if($highlight ?? null)
                    <tr>
                        <td style="padding:25px 30px;" class="mobile-p">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="border-left:4px solid #0B7285;padding-left:15px;">
                                        <p style="margin:0 0 6px 0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#0B7285;font-weight:bold;">Le fait marquant</p>
                                        <h2 style="font-size:20px;margin:0 0 10px 0;color:#f1f5f9;line-height:1.3;">{{ $highlight->title }}</h2>
                                        <p style="margin:0 0 12px 0;color:#cbd5e1;font-size:15px;">{{ Str::limit($highlight->summary ?? $highlight->seo_title ?? '', 200) }}</p>
                                        <a href="{{ $highlight->url ?? route('news.show', $highlight->slug ?? '') }}" target="_blank" style="color:#0B7285;font-weight:bold;text-decoration:none;">Lire &rarr;</a>
                                        @if($highlight->source_name ?? null)
                                        <p style="margin:8px 0 0 0;font-size:12px;color:#64748b;">{{ $highlight->source_name }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr><td height="1" bgcolor="#1a3a4a"></td></tr>
                    @endif

                    {{-- 2. ACTUALITES DE LA SEMAINE --}}
                    @if(($topNews ?? null) && $topNews->count())
                    <tr>
                        <td style="padding:25px 30px;" class="mobile-p">
                            <h3 style="font-size:16px;margin:0 0 15px 0;color:#f1f5f9;text-transform:uppercase;letter-spacing:1px;">Actualites de la semaine</h3>
                            @foreach($topNews as $news)
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:10px;">
                                <tr>
                                    <td width="16" valign="top" style="color:#0B7285;font-size:14px;padding-right:8px;">&#9679;</td>
                                    <td>
                                        <a href="{{ $news->url ?? route('news.show', $news->slug ?? '') }}" target="_blank" style="color:#e2e8f0;text-decoration:none;font-size:15px;">{{ $news->title ?? $news->seo_title ?? '' }}</a>
                                        @if($news->source_name ?? null)
                                        <span style="font-size:12px;color:#64748b;"> — {{ $news->source_name }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                            @endforeach
                        </td>
                    </tr>
                    <tr><td height="1" bgcolor="#1a3a4a"></td></tr>
                    @endif

                    {{-- 3. OUTIL DE LA SEMAINE --}}
                    @if($toolOfWeek ?? null)
                    <tr>
                        <td style="padding:25px 30px;" class="mobile-p">
                            <h3 style="font-size:16px;margin:0 0 15px 0;color:#f1f5f9;text-transform:uppercase;letter-spacing:1px;">Outil de la semaine</h3>
                            <p style="font-weight:bold;margin:0 0 6px 0;color:#f1f5f9;font-size:18px;">{{ $toolOfWeek->name }}</p>
                            <p style="margin:0 0 12px 0;color:#cbd5e1;font-size:14px;">{{ Str::limit(strip_tags($toolOfWeek->short_description ?? $toolOfWeek->description ?? ''), 150) }}</p>
                            @php
                                $pColor = match(strtolower($toolOfWeek->pricing ?? '')) {
                                    'free', 'gratuit' => '#10b981',
                                    'freemium' => '#f97316',
                                    default => '#64748b',
                                };
                            @endphp
                            <span style="display:inline-block;padding:3px 10px;background-color:{{ $pColor }};color:#fff;font-size:11px;border-radius:3px;margin-bottom:12px;">{{ ucfirst($toolOfWeek->pricing ?? 'N/A') }}</span><br/>
                            <a href="{{ route('directory.show', $toolOfWeek->slug) }}" target="_blank" style="display:inline-block;background-color:#0B7285;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;font-weight:bold;font-size:14px;">Decouvrir &rarr;</a>
                        </td>
                    </tr>
                    <tr><td height="1" bgcolor="#1a3a4a"></td></tr>
                    @endif

                    {{-- 4. ARTICLE A LIRE --}}
                    @if($featuredArticle ?? null)
                    <tr>
                        <td style="padding:25px 30px;" class="mobile-p">
                            <h3 style="font-size:16px;margin:0 0 15px 0;color:#f1f5f9;text-transform:uppercase;letter-spacing:1px;">A lire cette semaine</h3>
                            <a href="{{ route('blog.show', $featuredArticle->slug) }}" target="_blank" style="color:#f1f5f9;text-decoration:none;font-weight:bold;font-size:16px;line-height:1.4;display:block;margin-bottom:8px;">{{ $featuredArticle->title }}</a>
                            <p style="margin:0 0 12px 0;color:#94a3b8;font-size:14px;">{{ Str::limit(strip_tags($featuredArticle->excerpt ?? $featuredArticle->content ?? ''), 120) }}</p>
                            <a href="{{ route('blog.show', $featuredArticle->slug) }}" target="_blank" style="color:#0B7285;font-weight:bold;text-decoration:none;font-size:14px;">Lire l'article &rarr;</a>
                        </td>
                    </tr>
                    <tr><td height="1" bgcolor="#1a3a4a"></td></tr>
                    @endif

                    {{-- 5. LE SAVIEZ-VOUS? --}}
                    @if($didYouKnow ?? null)
                    <tr>
                        <td style="padding:25px 30px;" class="mobile-p">
                            <table role="presentation" border="0" cellpadding="15" cellspacing="0" width="100%" bgcolor="#0d3d4a" style="border-radius:6px;">
                                <tr>
                                    <td>
                                        <p style="margin:0 0 6px 0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#0B7285;font-weight:bold;">Le saviez-vous ?</p>
                                        <p style="margin:0;font-size:15px;color:#e2e8f0;"><strong style="color:#f1f5f9;">{{ $didYouKnow->term ?? $didYouKnow->name ?? '' }}</strong> — {{ Str::limit(strip_tags($didYouKnow->definition ?? $didYouKnow->description ?? ''), 200) }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- FOOTER --}}
                    <tr>
                        <td style="padding:30px 30px 20px 30px;text-align:center;" class="mobile-p">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-bottom:20px;">
                                        <a href="https://laveille.ai" target="_blank" style="display:inline-block;background-color:#0B7285;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;font-weight:bold;font-size:14px;">Visiter laveille.ai</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="font-size:13px;color:#64748b;">
                                        <a href="{{ \Modules\Settings\Facades\Settings::get('social.facebook_page_url', 'https://www.facebook.com/LaVeilleDeStef') }}" target="_blank" style="color:#64748b;text-decoration:none;">Facebook</a>
                                        &nbsp;|&nbsp;
                                        <a href="https://www.linkedin.com/in/lapointestephane/" target="_blank" style="color:#64748b;text-decoration:none;">LinkedIn</a>
                                        &nbsp;|&nbsp;
                                        <a href="https://laveille.ai" target="_blank" style="color:#64748b;text-decoration:none;">Site web</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding:10px 0;color:#475569;font-size:12px;">
                                        &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits reserves.<br/>
                                        {{ \Modules\Settings\Facades\Settings::get('contact.address', "L'Ancienne-Lorette, QC, Canada") }}
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <a href="{{ $unsubscribeUrl }}" target="_blank" style="color:#f97316;text-decoration:underline;font-size:12px;">Se desabonner</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
