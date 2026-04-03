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
            .stack-col { display:block !important; width:100% !important; padding-right:0 !important; padding-left:0 !important; padding-bottom:12px !important; }
            .stack-col img { width:100% !important; height:auto !important; }
            .mobile-p { padding:20px 15px !important; }
        }
    </style>
</head>
@php
    function newsletterImg($path, $fallback = 'images/og-image.png') {
        if (!$path) return asset($fallback);
        if (str_starts_with($path, 'http')) return $path;
        return asset($path);
    }
@endphp
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f4f4f4">
<tr><td align="center" style="padding:20px 10px;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" class="email-container" style="max-width:600px;background-color:#ffffff;border-radius:8px;overflow:hidden;">

    {{-- 0. LIEN "VOIR DANS LE NAVIGATEUR" --}}
    <tr>
        <td align="center" style="padding:10px 30px;background-color:#f4f4f4;font-size:12px;color:#666;">
            <a href="{{ route('newsletter.web', ['year' => now()->year, 'week' => $weekNumber ?? now()->weekOfYear]) }}" style="color:#0B7285;text-decoration:underline;">Voir cette infolettre dans votre navigateur</a>
        </td>
    </tr>

    {{-- 1. HEADER DARK + EDITORIAL --}}
    <tr>
        <td style="background-color:#0c1427;padding:24px 30px;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td><img src="{{ asset('images/logo-horizontal-white.png') }}" width="130" alt="{{ config('app.name') }}" style="width:130px;height:auto;"/></td>
                    <td align="right" style="font-size:12px;color:#94a3b8;">La veille IA #{{ $weekNumber ?? '?' }}<br/>{{ now()->translatedFormat('j F Y') }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- 1.5. MINI-EDITORIAL --}}
    @if($editorial ?? null)
    <tr>
        <td style="padding:20px 30px 16px;background-color:#ffffff;border-bottom:1px solid #f0f0f0;" class="mobile-p">
            <p style="margin:0;font-size:15px;color:#333;line-height:1.6;font-style:italic;">{{ $editorial }}</p>
        </td>
    </tr>
    @endif

    {{-- 2. LE FAIT MARQUANT --}}
    @if($highlight ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <p style="margin:0 0 12px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">Le fait marquant</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="200" valign="top" class="stack-col" style="padding-right:20px;">
                        <img src="{{ newsletterImg($highlight->image_url ?? null) }}" width="200" alt="{{ $highlight->seo_title ?? $highlight->title ?? '' }}" style="border-radius:6px;width:200px;"/>
                    </td>
                    <td valign="top" class="stack-col">
                        <h2 style="margin:0 0 8px;font-size:20px;line-height:1.3;color:#1a1a2e;">{{ $highlight->seo_title ?? $highlight->title ?? '' }}</h2>
                        <p style="margin:0 0 10px;font-size:14px;color:#555;line-height:1.5;">{{ Str::limit($highlight->summary ?? strip_tags($highlight->content ?? ''), 150) }}</p>
                        <a href="{{ $highlight->url ?? route('news.show', $highlight->slug ?? '') }}" style="color:#0B7285;font-weight:bold;font-size:14px;">Lire &rarr;</a>
                        @if($highlight->source_name ?? null)
                        <p style="margin:8px 0 0;font-size:11px;color:#666;">{{ $highlight->source_name }}</p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- 3. DEFI DE LA QUINZAINE (semaines paires, position haute pour max engagement) --}}
    @if(($weeklyPrompt ?? null) && (($weekNumber ?? 0) % 2 === 0))
    <tr>
        <td style="padding:25px 30px;background-color:#0c1427;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td align="center" style="padding-bottom:14px;"><span style="font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:1.5px;color:#3dc9d8;">Defi de la quinzaine</span></td></tr>
                <tr><td align="center" style="padding-bottom:14px;font-size:16px;color:#e2e8f0;">Essayez ce prompt cette semaine :</td></tr>
                <tr><td style="padding-bottom:14px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr><td style="background-color:#1e293b;border:1px solid #3dc9d8;border-radius:6px;padding:15px;font-size:15px;color:#e2e8f0;font-style:italic;line-height:1.5;">
                            {{ is_array($weeklyPrompt) ? ($weeklyPrompt['prompt'] ?? '') : $weeklyPrompt }}
                        </td></tr>
                    </table>
                </td></tr>
                @if(is_array($weeklyPrompt) && ($weeklyPrompt['technique'] ?? null))
                <tr><td style="padding-bottom:14px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr><td style="border-left:3px solid #3dc9d8;padding-left:12px;font-size:13px;color:#94a3b8;line-height:1.6;">
                            <strong style="color:#3dc9d8;">Pourquoi ce prompt fonctionne :</strong> {{ $weeklyPrompt['technique'] }}<br/>
                            <span style="color:#94a3b8;">Astuce : réutilisez cette approche dans vos propres requêtes pour de meilleurs résultats.</span>
                        </td></tr>
                    </table>
                </td></tr>
                @endif
                <tr><td align="center" style="padding-bottom:8px;font-size:13px;color:#94a3b8;">Copiez ce prompt et collez-le dans ChatGPT, Claude ou Gemini pour voir le résultat.</td></tr>
                <tr><td align="center">
                    <a href="{{ config('app.url') }}/outils/constructeur-prompts" target="_blank" style="display:inline-block;background-color:#3dc9d8;color:#0c1427;padding:10px 22px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Construire mon prompt &rarr;</a>
                </td></tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- 4. ACTUALITES (5 avec miniatures alternees + résumés) --}}
    @if(($topNews ?? null) && $topNews->count())
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <h3 style="margin:0 0 16px;font-size:14px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;">Actualites de la semaine</h3>
            @foreach($topNews as $news)
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:14px;{{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;padding-bottom:14px;' : '' }}">
                <tr>
                    @if($loop->odd)
                    <td width="80" valign="top" class="stack-col" style="padding-right:12px;">
                        <img src="{{ newsletterImg($news->image_url ?? null) }}" width="80" height="80" alt="{{ $news->seo_title ?? $news->title ?? '' }}" style="border-radius:6px;width:80px;height:80px;object-fit:cover;"/>
                    </td>
                    <td valign="top" class="stack-col">
                        <a href="{{ $news->url ?? route('news.show', $news->slug ?? '') }}" style="color:#1a1a2e;font-size:14px;font-weight:bold;text-decoration:none;line-height:1.3;">{{ $news->seo_title ?? $news->title ?? '' }}</a>
                        @if($news->summary ?? null)<br/><span style="font-size:12px;color:#555;line-height:1.4;">{{ Str::limit(strip_tags($news->summary), 140) }}</span>@endif
                        @if($news->source_name ?? null)<br/><span style="font-size:11px;color:#666;">{{ $news->source_name }}</span>@endif
                    </td>
                    @else
                    <td valign="top" class="stack-col" style="padding-right:12px;">
                        <a href="{{ $news->url ?? route('news.show', $news->slug ?? '') }}" style="color:#1a1a2e;font-size:14px;font-weight:bold;text-decoration:none;line-height:1.3;">{{ $news->seo_title ?? $news->title ?? '' }}</a>
                        @if($news->summary ?? null)<br/><span style="font-size:12px;color:#555;line-height:1.4;">{{ Str::limit(strip_tags($news->summary), 140) }}</span>@endif
                        @if($news->source_name ?? null)<br/><span style="font-size:11px;color:#666;">{{ $news->source_name }}</span>@endif
                    </td>
                    <td width="80" valign="top" class="stack-col">
                        <img src="{{ newsletterImg($news->image_url ?? null) }}" width="80" height="80" alt="{{ $news->seo_title ?? $news->title ?? '' }}" style="border-radius:6px;width:80px;height:80px;object-fit:cover;"/>
                    </td>
                    @endif
                </tr>
            </table>
            @endforeach
            <p style="margin:8px 0 0;text-align:center;"><a href="{{ route('news.index') }}" style="color:#0B7285;font-weight:bold;font-size:13px;">Voir toutes les actualites &rarr;</a></p>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- 5. OUTIL DE LA SEMAINE (miniature gauche + titre droite + reste en dessous) --}}
    @if($toolOfWeek ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#f0fdfa;" class="mobile-p">
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">Outil de la semaine</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td valign="top" class="stack-col" style="padding-right:16px;">
                        <span style="font-size:20px;font-weight:bold;color:#1a1a2e;">{{ $toolOfWeek->name }}</span>
                        @php
                            $pColor = match(strtolower($toolOfWeek->pricing ?? '')) { 'free','gratuit' => '#10b981', 'freemium' => '#f97316', default => '#6b7280' };
                            $pLabel = match(strtolower($toolOfWeek->pricing ?? '')) { 'free','gratuit' => 'Gratuit', 'freemium' => 'Freemium', default => 'Payant' };
                        @endphp
                        <span style="display:inline-block;background-color:{{ $pColor }};color:#fff;font-size:10px;font-weight:bold;padding:3px 8px;border-radius:3px;margin-left:6px;vertical-align:middle;">{{ $pLabel }}</span>
                        <p style="margin:8px 0 0;font-size:14px;color:#555;line-height:1.5;">{{ Str::limit(strip_tags($toolOfWeek->short_description ?? $toolOfWeek->description ?? ''), 120) }}</p>
                    </td>
                    <td width="200" valign="top" class="stack-col" style="padding-left:16px;">
                        <img src="{{ newsletterImg($toolOfWeek->screenshot ?? null) }}" width="200" alt="{{ $toolOfWeek->name }}" style="border-radius:8px;width:200px;"/>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding-top:14px;">
                    @if($toolOfWeek->use_cases ?? null)
                    <p style="margin:0 0 4px;font-size:13px;font-weight:bold;color:#0B7285;">Pour qui ?</p>
                    <p style="margin:0 0 12px;font-size:14px;color:#555;">{{ Str::limit(strip_tags($toolOfWeek->use_cases), 100) }}</p>
                    @endif
                    @if($toolOfWeek->pros ?? null)
                    <p style="margin:0 0 4px;font-size:13px;font-weight:bold;color:#0B7285;">Pourquoi l'essayer ?</p>
                    <p style="margin:0 0 14px;font-size:14px;color:#555;">{{ Str::limit(strip_tags($toolOfWeek->pros), 100) }}</p>
                    @endif
                    <a href="{{ route('directory.show', $toolOfWeek->slug) }}" target="_blank" style="display:inline-block;background-color:#0B7285;color:#fff;padding:10px 22px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Decouvrir sur laveille.ai &rarr;</a>
                </td></tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- 6. A LIRE (image gauche, texte droite — alternance) --}}
    @if($featuredArticle ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">A lire cette semaine</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="180" valign="top" class="stack-col" style="padding-right:16px;">
                        <img src="{{ newsletterImg($featuredArticle->featured_image ?? null) }}" width="180" alt="{{ $featuredArticle->title }}" style="border-radius:6px;width:180px;"/>
                    </td>
                    <td valign="top" class="stack-col">
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

    {{-- 7. OUTIL GRATUIT (miniature gauche + titre droite + reste en dessous) --}}
    @if($interactiveTool ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#fffbeb;" class="mobile-p">
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#b45309;font-weight:bold;">Outil gratuit a essayer</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td valign="top" class="stack-col" style="padding-right:16px;">
                        <span style="font-size:20px;font-weight:bold;color:#1a1a2e;">{{ $interactiveTool->icon ?? '' }} {{ $interactiveTool->name }}</span>
                        <p style="margin:8px 0 0;font-size:14px;color:#555;line-height:1.5;">{{ Str::limit(strip_tags($interactiveTool->description ?? ''), 120) }}</p>
                    </td>
                    <td width="150" valign="top" class="stack-col" style="padding-left:16px;">
                        <img src="{{ newsletterImg($interactiveTool->featured_image ?? null) }}" width="150" alt="{{ $interactiveTool->name }}" style="border-radius:8px;width:150px;"/>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding-top:14px;">
                    <p style="margin:0 0 14px;font-size:13px;color:#555;">100% gratuit, dans votre navigateur, aucune inscription.</p>
                    <a href="{{ route('tools.show', $interactiveTool->slug) }}" target="_blank" style="display:inline-block;background-color:#d97706;color:#fff;padding:10px 22px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Essayer gratuitement &rarr;</a>
                </td></tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- 8. TERME IA DE LA SEMAINE (texte gauche, image droite — alternance) --}}
    @if($aiTerm ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#f8fafc;" class="mobile-p">
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">Terme IA de la semaine</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td valign="top" class="stack-col" style="padding-right:16px;">
                        <span style="font-size:20px;font-weight:bold;color:#1a1a2e;">{{ $aiTerm->name ?? '' }}</span>
                        <p style="margin:8px 0 0;font-size:14px;color:#555;line-height:1.5;">{{ Str::limit(strip_tags($aiTerm->definition ?? ''), 180) }}</p>
                    </td>
                    <td width="200" valign="top" class="stack-col" style="padding-left:16px;">
                        <img src="{{ newsletterImg($aiTerm->hero_image ?? null) }}" alt="{{ $aiTerm->name ?? '' }}" width="200" style="border-radius:8px;width:200px;"/>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding-top:14px;">
                    @if($aiTerm->analogy ?? null)
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:14px;">
                        <tr>
                            <td width="4" bgcolor="#0B7285" style="vertical-align:top;"></td>
                            <td style="padding-left:15px;">
                                <span style="color:#0B7285;font-size:12px;font-style:italic;">En d'autres mots...</span><br/>
                                <span style="color:#555;font-size:14px;line-height:1.5;">{{ Str::limit(strip_tags($aiTerm->analogy), 180) }}</span>
                            </td>
                        </tr>
                    </table>
                    @endif
                    @if($aiTerm->did_you_know ?? null)
                    <p style="margin:0 0 4px;font-size:12px;font-weight:bold;color:#b45309;">Le saviez-vous ?</p>
                    <p style="margin:0 0 14px;font-size:13px;color:#666;line-height:1.4;">{{ Str::limit(strip_tags($aiTerm->did_you_know), 150) }}</p>
                    @endif
                    @if(Route::has('dictionary.index'))
                    <p style="margin:0;text-align:center;"><a href="{{ route('dictionary.index') }}" target="_blank" style="color:#0B7285;font-size:13px;font-weight:bold;text-decoration:none;">Explorer le glossaire &rarr;</a></p>
                    @endif
                </td></tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- 9. LE SAVIEZ-VOUS? (promo veille.la) --}}
    <tr>
        <td style="padding:25px 30px;background-color:#0c1427;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td>
                    <p style="margin:0 0 8px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#3dc9d8;font-weight:bold;">Le saviez-vous ?</p>
                    <p style="margin:0 0 14px;font-size:16px;color:#e2e8f0;line-height:1.6;">
                        <a href="{{ config('app.url') }}/raccourcir" style="color:#3dc9d8;font-weight:bold;text-decoration:underline;">veille.la</a> est le domaine utilise pour notre raccourcisseur de liens. Creez des liens courts avec code QR, statistiques de clics et apercu social, le tout gratuitement et sans inscription.
                    </p>
                    <a href="{{ config('app.url') }}/raccourcir" style="display:inline-block;background-color:#3dc9d8;color:#0c1427;padding:10px 22px;border-radius:4px;font-weight:bold;font-size:13px;text-decoration:none;">Raccourcir un lien &rarr;</a>
                </td></tr>
            </table>
        </td>
    </tr>

    {{-- 10. FOOTER --}}
    <tr>
        <td style="padding:30px;text-align:center;background-color:#fafafa;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td align="center" style="padding-bottom:20px;">
                    <a href="https://laveille.ai" style="display:inline-block;background-color:#0B7285;color:#fff;padding:12px 28px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Visiter laveille.ai</a>
                </td></tr>
                <tr><td align="center" style="font-size:12px;color:#666;padding-bottom:10px;">
                    <a href="https://www.facebook.com/LaVeilleDeStef" style="color:#666;text-decoration:none;">Facebook</a>
                    &nbsp;&middot;&nbsp;
                    <a href="https://www.linkedin.com/in/lapointestephane/" style="color:#666;text-decoration:none;">LinkedIn</a>
                    &nbsp;&middot;&nbsp;
                    <a href="https://laveille.ai" style="color:#666;text-decoration:none;">Site web</a>
                    &nbsp;&middot;&nbsp;
                    <a href="https://laveille.ai/feed" style="color:#666;text-decoration:none;">RSS</a>
                </td></tr>
                <tr><td align="center" style="font-size:11px;color:#737373;padding-bottom:8px;">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits reserves.
                </td></tr>
                <tr><td align="center">
                    <a href="{{ $unsubscribeUrl }}" style="color:#f97316;text-decoration:underline;font-size:11px;">Se desabonner</a>
                </td></tr>
            </table>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
