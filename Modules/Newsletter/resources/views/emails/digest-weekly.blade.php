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
        /* 2026-05-26 #302 WCAG 2.2 AAA : liens lisibles dans les blocs dark (atelier #0c1427, code #1e293b) — cyan #3dc9d8 vs dark = ratio 8.7:1 AAA. Avant : teal #0B7285 invisible sur dark (1.6:1 FAIL). */
        td[style*="#0c1427"] a, td[style*="#1e293b"] a,
        td[style*="background-color:#0c1427"] a, td[style*="background-color:#1e293b"] a,
        td[style*="background:#0c1427"] a, td[style*="background:#1e293b"] a {
            color:#5eead4 !important;
            text-decoration:underline !important;
            text-underline-offset:2px;
        }
        /* 2026-06-02 fix contraste WCAG 2.2 AAA : les BOUTONS cyan (#3dc9d8) dans les blocs dark gardent leur texte foncé #0c1427 — la règle générique ci-dessus les rendait cyan-sur-cyan illisibles. Sélecteur plus spécifique (attribut background-color) → bat la règle générique. Ne touche PAS les liens texte (color:#3dc9d8) qui restent en #5eead4. */
        td[style*="#0c1427"] a[style*="background-color:#3dc9d8"],
        td[style*="#1e293b"] a[style*="background-color:#3dc9d8"] {
            color:#0c1427 !important;
            text-decoration:none !important;
        }
        @media only screen and (max-width:600px) {
            .email-container { width:100% !important; }
            .stack-col { display:block !important; width:100% !important; padding-right:0 !important; padding-left:0 !important; padding-bottom:12px !important; }
            .stack-col img { width:100% !important; height:auto !important; }
            .mobile-p { padding:20px 15px !important; }
        }
    </style>
</head>
@php
    // Garde-fou : éviter le fatal « Cannot redeclare » si la vue est rendue 2× dans le même process PHP.
    if (! function_exists('newsletterImg')) {
        function newsletterImg($path, $fallback = 'images/og-image.png') {
            if (!$path) return asset($fallback);
            if (str_starts_with($path, 'http')) return $path;
            return asset($path);
        }
    }
@endphp
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f4f4f4">
<tr><td align="center" style="padding:20px 10px;">
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" class="email-container" style="max-width:600px;background-color:#ffffff;border-radius:8px;overflow:hidden;">

    {{-- 0. LIEN "VOIR DANS LE NAVIGATEUR" --}}
    <tr>
        <td align="center" style="padding:10px 30px;background-color:#f4f4f4;font-size:12px;color:#666;">
            @if($isWelcome ?? false)
                <a href="{{ config('app.url') }}/newsletter/bienvenue" style="color:#0B7285;text-decoration:underline;">Voir cette infolettre dans votre navigateur</a>
            @else
                <a href="{{ route('newsletter.web', ['year' => now()->year, 'week' => $weekNumber ?? now()->weekOfYear]) }}" style="color:#0B7285;text-decoration:underline;">Voir cette infolettre dans votre navigateur</a>
            @endif
        </td>
    </tr>

    {{-- 1. HEADER DARK + EDITORIAL --}}
    <tr>
        <td style="background-color:#0c1427;padding:24px 30px;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td><img src="{{ asset('images/logo-email-white.png') }}?v={{ time() }}" width="200" alt="{{ config('app.name') }}" style="width:200px;height:auto;"/></td>
                    <td align="right" style="font-size:12px;color:#94a3b8;">{{ ($isWelcome ?? false) ? 'Bienvenue !' : 'La veille IA #'.($weekNumber ?? '?') }}<br/>{{ now()->translatedFormat('j F Y') }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- SECTIONS BIENVENUE (welcome uniquement) --}}
    @if($isWelcome ?? false)

    {{-- W1. MOT DE STEF --}}
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">Mot de Stef</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td width="80" valign="top" class="stack-col" style="padding-right:20px;">
                        <img src="{{ asset('images/logo-avatar.png') }}" alt="La veille" width="80" height="80" style="border-radius:50%;width:80px;height:80px;object-fit:cover;"/>
                    </td>
                    <td valign="top" class="stack-col">
                        <p style="margin:0 0 10px;font-size:15px;color:#333;line-height:1.6;">Bonjour{{ ($subscriberName ?? null) ? ' '.$subscriberName : '' }},</p>
                        <p style="margin:0 0 10px;font-size:15px;color:#333;line-height:1.6;">C'est un plaisir de vous retrouver pour votre rendez-vous hebdomadaire sur <strong><a href="{{ config('app.url') }}" style="color:#0B7285;">laveille.ai</a></strong>. Voici les actualités technologiques et les innovations incontournables de la semaine.</p>
                        <p style="font-family:'Dancing Script','Brush Script MT','Segoe Script',cursive;font-size:24px;color:#0B7285;margin:12px 0 0;">Stef</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>

    {{-- W2. CHAQUE SEMAINE DANS VOTRE BOÎTE --}}
    <tr>
        <td style="padding:25px 30px;background-color:#f8fafc;" class="mobile-p">
            <p style="margin:0 0 16px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">Chaque semaine dans votre boîte</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;">&#x1F4E2; <strong>Le fait marquant</strong> — l'actualité IA incontournable</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;">&#x1F4F0; <strong>5 actualités</strong> — résumées et triées pour vous</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;">&#x1F3AF; <strong>Un défi prompt</strong> — un prompt à essayer immédiatement</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;">&#x1F527; <strong>L'outil de la semaine</strong> — testé et recommandé</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;">&#x1F4D6; <strong>Un terme IA expliqué</strong> — pour comprendre sans jargon</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;">&#x1F4DD; <strong>Un article approfondi</strong> — analyse ou tutoriel</td></tr>
                <tr><td style="padding:6px 0;font-size:14px;color:#333;line-height:1.5;">&#x1F381; <strong>Un outil gratuit</strong> — à essayer dans votre navigateur</td></tr>
            </table>
            <p style="margin:14px 0 0;font-size:14px;color:#555;font-style:italic;">Voici votre premier numéro. Bonne lecture !</p>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>

    {{-- W3. LE NOUVEAU LAVEILLE.AI --}}
    <tr>
        <td style="padding:25px 30px;background-color:#ffffff;" class="mobile-p">
            <p style="margin:0 0 16px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">Le nouveau laveille.ai</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td style="padding-bottom:12px;">
                    <strong style="font-size:15px;color:#1a1a2e;">Répertoire de 75+ outils IA</strong><br/>
                    <span style="font-size:13px;color:#555;">Fiches détaillées, screenshots, avis de la communauté</span>
                </td></tr>
                <tr><td style="padding-bottom:12px;">
                    <strong style="font-size:15px;color:#1a1a2e;">Glossaire IA interactif</strong><br/>
                    <span style="font-size:13px;color:#555;">140+ termes expliqués simplement avec analogies</span>
                </td></tr>
                <tr><td style="padding-bottom:12px;">
                    <strong style="font-size:15px;color:#1a1a2e;">Outils gratuits en ligne</strong><br/>
                    <span style="font-size:13px;color:#555;">Calculatrices, générateurs, constructeur de prompts</span>
                </td></tr>
                <tr><td style="padding-bottom:16px;">
                    <strong style="font-size:15px;color:#1a1a2e;">Acronymes en éducation</strong><br/>
                    <span style="font-size:13px;color:#555;">300+ acronymes du milieu éducatif québécois</span>
                </td></tr>
                <tr><td align="center">
                    <a href="{{ config('app.url') }}" target="_blank" style="display:inline-block;background-color:#0B7285;color:#fff;padding:10px 22px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Explorer le site &rarr;</a>
                </td></tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>

    @endif

    {{-- 1.5. MINI-EDITORIAL --}}
    @if($editorial ?? null)
    <tr>
        <td style="padding:20px 30px 16px;background-color:#ffffff;border-bottom:1px solid #f0f0f0;" class="mobile-p">
            <div style="margin:0;font-size:15px;color:#333;line-height:1.6;font-style:italic;">{!! $editorial !!}</div>
        </td>
    </tr>
    @endif

    {{-- 2. HERO — LE FAIT MARQUANT (vedette proéminente : 1 idée forte en tête, reste du digest condensé plus bas) --}}
    @if($highlight ?? null)
    <tr>
        <td style="padding:28px 30px 26px;background-color:#ffffff;" class="mobile-p">
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">Le fait marquant de la semaine</p>
            @if($highlight->image_url ?? null)
            <a href="{{ $highlight->url ?? route('news.show', $highlight->slug ?? '') }}" target="_blank" style="text-decoration:none;display:block;">
                <img src="{{ newsletterImg($highlight->image_url ?? null) }}" width="540" alt="{{ $highlight->seo_title ?? $highlight->title ?? '' }}" style="border-radius:8px;width:100%;max-width:540px;margin:0 0 18px;"/>
            </a>
            @endif
            <h1 style="margin:0 0 12px;font-size:26px;line-height:1.25;color:#1a1a2e;font-weight:bold;">{{ $highlight->seo_title ?? $highlight->title ?? '' }}</h1>
            <p style="margin:0 0 20px;font-size:16px;color:#444;line-height:1.6;">{{ Str::limit($highlight->summary ?? strip_tags($highlight->content ?? ''), 220) }}</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr><td style="border-radius:4px;background-color:#0B7285;">
                    <a href="{{ $highlight->url ?? route('news.show', $highlight->slug ?? '') }}" target="_blank" style="display:inline-block;background-color:#0B7285;color:#ffffff;padding:13px 30px;border-radius:4px;font-weight:bold;font-size:15px;text-decoration:none;">Lire l'article complet &rarr;</a>
                </td></tr>
            </table>
            @if($highlight->source_name ?? null)
            <p style="margin:14px 0 0;font-size:12px;color:#666;">Source&nbsp;: {{ $highlight->source_name }}</p>
            @endif
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- W4. PREMIER DÉFI — prompt animal de feu (welcome uniquement, après fait marquant) --}}
    @if($isWelcome ?? false)
    <tr>
        <td style="padding:25px 30px;background-color:#0c1427;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td align="center" style="padding-bottom:14px;"><span style="font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:1.5px;color:#3dc9d8;">Votre premier défi — création d'image IA</span></td></tr>
                <tr><td align="center" style="padding-bottom:14px;font-size:16px;color:#e2e8f0;">Copiez ce prompt dans <strong style="color:#3dc9d8;">Gemini</strong> (Google) ou <strong style="color:#3dc9d8;">ChatGPT</strong> (DALL-E) en mode création d'image — les deux meilleurs pour ce type de rendu. Vous pouvez aussi l'essayer dans d'autres outils !</td></tr>
                <tr><td style="padding-bottom:14px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr><td style="background-color:#1e293b;border:1px solid #3dc9d8;border-radius:6px;padding:15px;font-size:14px;color:#e2e8f0;font-style:italic;line-height:1.6;">
                            Un plan moyen cadré à la taille, filmé avec une focale de 85mm, profondeur de champ faible (f/1.8) avec un léger bokeh en arrière-plan. Rendu photoréaliste et cinématographique, qualité 8K, éclairage volumétrique. Un homme barbu dans la trentaine, vêtu d'une chemise sombre aux manches retroussées, se tient de profil gauche dans une pièce plongée dans l'obscurité. Son regard exprime un émerveillement mêlé de révérence alors qu'il contemple l'esprit complexe et ardent d'un(e) <strong style="color:#3dc9d8;font-style:normal;">__ANIMAL__</strong> qui se matérialise au-dessus de sa main droite tendue, paume ouverte vers le ciel. La créature éthérée est entièrement sculptée de flammes tourbillonnantes dorées, orangées et blanc incandescent. Des volutes de fumée ambrée s'élèvent en spirales douces, tandis que des étincelles et des braises flottent dans l'air comme des lucioles. L'éclairage principal provient exclusivement de l'esprit de feu, projetant une lueur chaude et dorée sur le visage et le torse de l'homme, créant un clair-obscur prononcé. L'arrière-plan révèle à peine les contours flous d'une chambre dans la pénombre. Atmosphère magique, intime et profondément contemplative.
                        </td></tr>
                    </table>
                </td></tr>
                <tr><td style="padding-bottom:14px;">
                    <p style="color:#f97316;font-size:14px;font-weight:bold;margin:0 0 6px;">&#x1F4A1; Remplacez __ANIMAL__ par votre animal préféré !</p>
                    <p style="color:#94a3b8;font-size:13px;margin:0;line-height:1.5;">Loup, cerf, phénix, dragon, raton laveur... chaque animal donne un résultat unique et spectaculaire.</p>
                </td></tr>
                <tr><td style="padding-bottom:14px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr><td style="border-left:3px solid #3dc9d8;padding-left:12px;">
                            <p style="color:#3dc9d8;font-size:12px;font-weight:bold;margin:0 0 6px;">Pourquoi ce prompt fonctionne :</p>
                            <p style="color:#94a3b8;font-size:13px;margin:0;line-height:1.5;">Ce prompt suit les bonnes pratiques 2026 : il commence par les instructions techniques de caméra (focale 85mm, ouverture f/1.8) pour un cadrage cinématographique précis. L'éclairage est décrit comme source unique — l'esprit de feu — ce qui crée un clair-obscur dramatique cohérent. Les détails sensoriels empilés (flammes, fumée ambrée, étincelles, bokeh) donnent de la richesse sans que l'IA doive deviner quoi ajouter.</p>
                        </td></tr>
                    </table>
                </td></tr>
                <tr><td align="center">
                    <a href="{{ config('app.url') }}/outils/constructeur-prompts" target="_blank" style="display:inline-block;background-color:#3dc9d8;color:#0c1427;padding:10px 22px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Construire mon prompt &rarr;</a>
                </td></tr>
            </table>
        </td>
    </tr>
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- 3. ATELIER DE LA SEMAINE — header unifié (Beehiiv 2026 : single-CTA unified +28% completion, Gestalt grouping) --}}
    @if(($wellnessChallenge ?? null) || (($weeklyPrompt ?? null) && !($isWelcome ?? false)))
    <tr>
        <td align="center" style="padding:28px 30px 4px;background-color:#ffffff;" class="mobile-p">
            <span style="font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;">Atelier de la semaine</span>
            <h2 style="margin:8px 0 4px;font-size:22px;line-height:1.25;color:#1a1a2e;">Pratiquez l'IA cette semaine</h2>
            <p style="margin:0;font-size:14px;color:#6b7280;">
                @php
                    $hasWellness = (bool) ($wellnessChallenge ?? null);
                    $hasPrompt = ($weeklyPrompt ?? null) && !($isWelcome ?? false);
                @endphp
                @if($hasWellness && $hasPrompt){{ 'Un défi IA et numérique à essayer + un prompt à tester' }}
                @elseif($hasWellness){{ 'Un défi IA et numérique à essayer' }}
                @else{{ 'Un prompt à tester' }}@endif
            </p>
        </td>
    </tr>
    @endif

    {{-- 3a-top. DEFI BIEN-ETRE — header + intro + STEPS (1-5) seulement (JIT learning : prompt suit IMMÉDIATEMENT après) --}}
    @if($wellnessChallenge ?? null)
    @php $wc = $wellnessChallenge; @endphp
    <tr>
        <td style="padding:20px 30px;background-color:#fef3e8;border-left:6px solid #f97316;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td style="padding-bottom:10px;">
                    <span style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#c2410c;font-weight:bold;">@if($hasPrompt ?? false){{ 'Étape 1 — Défi' }}@else{{ 'Défi IA et numérique' }}@endif</span>
                    @if(! empty($wc['score']))
                    <span style="display:inline-block;background-color:#fff;color:#c2410c;font-size:11px;font-weight:bold;padding:2px 8px;border-radius:10px;margin-left:8px;border:1px solid #fdba74;">★ {{ $wc['score'] }}% chez les abonnés</span>
                    @endif
                </td></tr>
                <tr><td style="padding-bottom:10px;">
                    <h3 style="margin:0;font-size:20px;line-height:1.2;color:#1a1a2e;">{{ $wc['title'] ?? '' }}</h3>
                    @if(! empty($wc['subtitle']))
                    <p style="margin:6px 0 0;font-size:14px;color:#7c2d12;font-style:italic;">{{ $wc['subtitle'] }}</p>
                    @endif
                </td></tr>
                @if(! empty($wc['hook']))
                <tr><td style="padding-bottom:14px;">
                    <p style="margin:0;font-size:15px;color:#1f2937;line-height:1.6;">{!! $wc['hook'] !!}</p>
                </td></tr>
                @endif
                @if(! empty($wc['steps']) && is_array($wc['steps']))
                <tr><td style="padding-bottom:0;">
                    <p style="margin:0 0 8px;font-size:13px;font-weight:bold;color:#c2410c;text-transform:uppercase;letter-spacing:1px;">Les étapes</p>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        @foreach($wc['steps'] as $idx => $step)
                        <tr><td style="padding:8px 0;border-bottom:{{ $loop->last ? '0' : '1px solid #fed7aa' }};">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td width="32" valign="top" style="font-size:18px;font-weight:bold;color:#f97316;">{{ $idx + 1 }}.</td>
                                    <td valign="top" style="font-size:14px;color:#1f2937;line-height:1.5;">{!! $step !!}</td>
                                </tr>
                            </table>
                        </td></tr>
                        @endforeach
                    </table>
                </td></tr>
                @endif
                {{-- Mode standalone (sans prompt) : CTA + privacy/tools/bonus restent ici --}}
                @if(! ($hasPrompt ?? false))
                    @if(! empty($wc['privacy']))
                    <tr><td style="padding-top:14px;padding-bottom:14px;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#fee2e2;border-radius:6px;">
                            <tr><td style="padding:12px 14px;">
                                <span style="font-size:12px;font-weight:bold;color:#991b1b;">🔒 Privacy / Loi 25</span><br/>
                                <span style="font-size:13px;color:#7f1d1d;line-height:1.5;">{!! $wc['privacy'] !!}</span>
                            </td></tr>
                        </table>
                    </td></tr>
                    @endif
                    @if(! empty($wc['tools']) && is_array($wc['tools']))
                    <tr><td style="padding-bottom:14px;">
                        <p style="margin:0 0 8px;font-size:13px;font-weight:bold;color:#c2410c;text-transform:uppercase;letter-spacing:1px;">Outils par profil</p>
                        @foreach($wc['tools'] as $tool)
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:6px;background-color:#fff;border-radius:4px;">
                            <tr><td style="padding:8px 12px;font-size:13px;color:#1f2937;line-height:1.5;">
                                <strong style="color:#c2410c;">{{ $tool['profile'] ?? '' }}</strong> — {!! $tool['description'] ?? '' !!}
                            </td></tr>
                        </table>
                        @endforeach
                    </td></tr>
                    @endif
                    @if(! empty($wc['bonus']))
                    <tr><td style="padding-bottom:14px;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#ecfdf5;border-radius:6px;border:1px dashed #6ee7b7;">
                            <tr><td style="padding:12px 14px;">
                                <span style="font-size:12px;font-weight:bold;color:#065f46;">💡 Bonus boucle 24h</span><br/>
                                <span style="font-size:13px;color:#064e3b;line-height:1.5;">{!! $wc['bonus'] !!}</span>
                            </td></tr>
                        </table>
                    </td></tr>
                    @endif
                    @if(! empty($wc['cta_url']) && ! empty($wc['cta_label']))
                    <tr><td align="center" style="padding-top:6px;">
                        @php $_wcCtaSuffix = preg_match('/[→»»]\s*$/u', $wc['cta_label']) ? '' : ' &rarr;'; @endphp
                        <a href="{{ $wc['cta_url'] }}" target="_blank" style="display:inline-block;background-color:#f97316;color:#fff;padding:11px 24px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">{{ $wc['cta_label'] }}{!! $_wcCtaSuffix !!}</a>
                    </td></tr>
                    @endif
                @endif
            </table>
        </td>
    </tr>
    @endif

    {{-- 3b. DEFI PROMPT (étape 2 : approfondir avec l'IA) — masqué dans welcome (W4 remplace) --}}
    @if(($weeklyPrompt ?? null) && !($isWelcome ?? false))
    <tr>
        <td style="padding:25px 30px;background-color:#0c1427;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td align="center" style="padding-bottom:14px;"><span style="font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:1.5px;color:#3dc9d8;">@if($hasWellness ?? false){{ 'Étape 2 — Prompt IA' }}@else{{ 'Défi de la semaine' }}@endif</span></td></tr>
                @if(is_array($weeklyPrompt) && ($weeklyPrompt['intro'] ?? null))
                <tr><td align="center" style="padding-bottom:14px;font-size:14px;color:#cbd5e1;line-height:1.5;font-style:italic;">{!! e($weeklyPrompt['intro']) !!}</td></tr>
                @endif
                {{-- Helper de rendu d'un bloc copy-paste plain text --}}
                @php
                    $renderCopyBlock = function($content) {
                        $escaped = e($content);
                        $bracketed = preg_replace('/\[([^\]]+)\]/', '<span style="color:#fbbf24;font-weight:bold;">[$1]</span>', $escaped);
                        return nl2br($bracketed, false);
                    };
                @endphp
                {{-- Option A 2026 : 2 sub-blocks "Partie 1 / Partie 2" copy-paste séparés, annotations hors bloc --}}
                @if(is_array($weeklyPrompt) && ! empty($weeklyPrompt['parts']) && is_array($weeklyPrompt['parts']))
                    <tr><td align="center" style="padding-bottom:14px;font-size:12px;color:#94a3b8;">Copiez chaque bloc dans ChatGPT, Claude ou Gemini. Remplacez les <span style="color:#fbbf24;font-weight:bold;">[textes en jaune]</span> par vos infos.</td></tr>
                    @foreach($weeklyPrompt['parts'] as $idx => $part)
                        @if(! empty($part['label']))
                        <tr><td style="padding-bottom:6px;">
                            <span style="display:inline-block;background-color:#3dc9d8;color:#0c1427;font-size:11px;font-weight:bold;padding:3px 10px;border-radius:4px;text-transform:uppercase;letter-spacing:.5px;">{{ $part['label'] }}</span>
                        </td></tr>
                        @endif
                        @if(! empty($part['pre_note']))
                        <tr><td style="padding-bottom:8px;font-size:13px;color:#cbd5e1;line-height:1.5;">{!! e($part['pre_note']) !!}</td></tr>
                        @endif
                        <tr><td style="padding-bottom:8px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr><td style="background-color:#1e293b;border:1px solid #3dc9d8;border-radius:6px;padding:15px;font-family:Consolas,'Liberation Mono',Menlo,Courier,monospace;font-size:13px;color:#e2e8f0;line-height:1.55;white-space:normal;">
                                    {!! $renderCopyBlock($part['content'] ?? '') !!}
                                </td></tr>
                            </table>
                        </td></tr>
                        @if(! empty($part['image_url']))
                        {{-- 2026-05-27 #303 : image illustrative optionnelle par étape (ex screenshot d'UI) --}}
                        <tr><td style="padding-bottom:8px;text-align:center;">
                            <img src="{{ str_starts_with($part['image_url'], 'http') ? $part['image_url'] : asset($part['image_url']) }}" alt="{{ e($part['image_alt'] ?? '') }}" style="max-width:100%;height:auto;border:1px solid #334155;border-radius:6px;background:#fff;padding:4px;" width="{{ $part['image_width'] ?? '320' }}"/>
                            @if(! empty($part['image_caption']))<br/><span style="display:inline-block;margin-top:6px;font-size:12px;color:#94a3b8;font-style:italic;">{!! e($part['image_caption']) !!}</span>@endif
                        </td></tr>
                        @endif
                        @if(! empty($part['post_note']))
                        <tr><td style="padding-bottom:14px;font-size:13px;color:#94a3b8;line-height:1.5;font-style:italic;">↳ {!! e($part['post_note']) !!}</td></tr>
                        @else
                        <tr><td style="padding-bottom:14px;"></td></tr>
                        @endif
                    @endforeach
                @else
                {{-- Backward compat : prompt string ou champ legacy --}}
                <tr><td align="center" style="padding-bottom:6px;font-size:16px;color:#e2e8f0;">Essayez ce prompt cette semaine :</td></tr>
                <tr><td align="center" style="padding-bottom:14px;font-size:12px;color:#94a3b8;">Remplacez les <span style="color:#fbbf24;font-weight:bold;">[textes en jaune]</span> par vos propres informations, puis copiez le tout.</td></tr>
                <tr><td style="padding-bottom:14px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr><td style="background-color:#1e293b;border:1px solid #3dc9d8;border-radius:6px;padding:15px;font-family:Consolas,'Liberation Mono',Menlo,Courier,monospace;font-size:13px;color:#e2e8f0;line-height:1.55;">
                            @php
                                $rawPrompt = is_array($weeklyPrompt) ? ($weeklyPrompt['prompt'] ?? '') : $weeklyPrompt;
                            @endphp
                            {!! $renderCopyBlock($rawPrompt) !!}
                        </td></tr>
                    </table>
                </td></tr>
                @endif
                @if(is_array($weeklyPrompt) && ($weeklyPrompt['technique'] ?? null))
                <tr><td style="padding-bottom:14px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr><td style="border-left:3px solid #3dc9d8;padding-left:12px;font-size:13px;color:#94a3b8;line-height:1.7;">
                            <strong style="color:#3dc9d8;">Pourquoi ce prompt fonctionne :</strong><br/>
                            {{-- 2026-05-26 #302 : whitelist HTML safe (a/strong/em/br) pour permettre liens vers glossaire dans la technique --}}
                            {!! nl2br(strip_tags((string) $weeklyPrompt['technique'], '<a><strong><em><br>')) !!}
                        </td></tr>
                    </table>
                </td></tr>
                @endif
                @if(is_array($weeklyPrompt) && ($weeklyPrompt['best_practices'] ?? null) && is_array($weeklyPrompt['best_practices']))
                @php $bp = $weeklyPrompt['best_practices']; @endphp
                <tr><td style="padding-bottom:14px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr><td align="center" style="padding-bottom:10px;"><strong style="color:#e2e8f0;font-size:14px;">{{ $bp['title'] ?? '📊 Algorithme LinkedIn 2026 — ce qui marche vraiment' }}</strong></td></tr>
                        <tr>
                            <td width="50%" valign="top" style="padding-right:6px;" class="stack-col">
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#1e3a2e;border-radius:6px;">
                                    <tr><td style="padding:14px;color:#d1fae5;font-size:13px;line-height:1.6;">
                                        <strong style="color:#34d399;font-size:14px;">✅ {{ $bp['to_do_label'] ?? 'À FAIRE' }}</strong>
                                        @foreach(($bp['to_do'] ?? []) as $idx => $item)
                                        <br/><br/>{{ $idx + 1 }}. {!! e($item) !!}
                                        @endforeach
                                    </td></tr>
                                </table>
                            </td>
                            <td width="50%" valign="top" style="padding-left:6px;" class="stack-col">
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#3a1e1e;border-radius:6px;">
                                    <tr><td style="padding:14px;color:#fecaca;font-size:13px;line-height:1.6;">
                                        <strong style="color:#f87171;font-size:14px;">❌ {{ $bp['to_avoid_label'] ?? 'À ÉVITER' }}</strong>
                                        @foreach(($bp['to_avoid'] ?? []) as $idx => $item)
                                        <br/><br/>{{ $idx + 1 }}. {!! e($item) !!}
                                        @endforeach
                                    </td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td></tr>
                @endif
                <tr><td align="center" style="padding-bottom:8px;font-size:13px;color:#94a3b8;">Copiez ce prompt et collez-le dans ChatGPT, Claude ou Gemini pour voir le résultat.</td></tr>
                @if(is_array($weeklyPrompt) && ($weeklyPrompt['cta_intro'] ?? null))
                <tr><td align="center" style="padding-bottom:10px;font-size:13px;color:#94a3b8;line-height:1.5;">{!! e($weeklyPrompt['cta_intro']) !!}</td></tr>
                @endif
                @php
                    // En mode synergie, prioriser le CTA du défi (lien outil dédié sur le site)
                    $promptCtaUrl = (($hasWellness ?? false) && ! empty($wellnessChallenge['cta_url']))
                        ? $wellnessChallenge['cta_url']
                        : config('app.url').'/outils/constructeur-prompts';
                    $promptCtaLabel = (($hasWellness ?? false) && ! empty($wellnessChallenge['cta_label']))
                        ? $wellnessChallenge['cta_label']
                        : ((is_array($weeklyPrompt) && ! empty($weeklyPrompt['cta_label'])) ? $weeklyPrompt['cta_label'] : 'Construire mon prompt →');
                @endphp
                <tr><td align="center">
                    @php $_ctaSuffix = preg_match('/[→»»]\s*$/u', $promptCtaLabel) ? '' : ' &rarr;'; @endphp
                    <a href="{{ $promptCtaUrl }}" target="_blank" style="display:inline-block;background-color:#3dc9d8;color:#0c1427;padding:10px 22px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">{{ $promptCtaLabel }}{!! $_ctaSuffix !!}</a>
                </td></tr>
            </table>
        </td>
    </tr>
    @endif

    {{-- 3a-bottom. RESSOURCES DU DEFI (annexes orange) — privacy + tools + bonus, après le prompt (JIT) --}}
    @if(($wellnessChallenge ?? null) && ($hasPrompt ?? false))
    @php $wc = $wellnessChallenge; @endphp
    @if(! empty($wc['privacy']) || ! empty($wc['tools']) || ! empty($wc['bonus']))
    <tr>
        <td style="padding:20px 30px;background-color:#fef3e8;border-left:6px solid #f97316;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td style="padding-bottom:14px;">
                    <span style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#c2410c;font-weight:bold;">Ressources du défi</span>
                </td></tr>
                @if(! empty($wc['privacy']))
                <tr><td style="padding-bottom:14px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#fee2e2;border-radius:6px;">
                        <tr><td style="padding:12px 14px;">
                            <span style="font-size:12px;font-weight:bold;color:#991b1b;">🔒 Privacy / Loi 25</span><br/>
                            <span style="font-size:13px;color:#7f1d1d;line-height:1.5;">{!! $wc['privacy'] !!}</span>
                        </td></tr>
                    </table>
                </td></tr>
                @endif
                @if(! empty($wc['tools']) && is_array($wc['tools']))
                <tr><td style="padding-bottom:14px;">
                    <p style="margin:0 0 8px;font-size:13px;font-weight:bold;color:#c2410c;text-transform:uppercase;letter-spacing:1px;">Outils par profil</p>
                    @foreach($wc['tools'] as $tool)
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:6px;background-color:#fff;border-radius:4px;">
                        <tr><td style="padding:8px 12px;font-size:13px;color:#1f2937;line-height:1.5;">
                            <strong style="color:#c2410c;">{{ $tool['profile'] ?? '' }}</strong> — {!! $tool['description'] ?? '' !!}
                        </td></tr>
                    </table>
                    @endforeach
                </td></tr>
                @endif
                @if(! empty($wc['bonus']))
                <tr><td>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#ecfdf5;border-radius:6px;border:1px dashed #6ee7b7;">
                        <tr><td style="padding:12px 14px;">
                            <span style="font-size:12px;font-weight:bold;color:#065f46;">💡 Bonus boucle 24h</span><br/>
                            <span style="font-size:13px;color:#064e3b;line-height:1.5;">{!! $wc['bonus'] !!}</span>
                        </td></tr>
                    </table>
                </td></tr>
                @endif
            </table>
        </td>
    </tr>
    @endif
    @endif
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>

    {{-- 3.5 supprimé : fusionné dans §3 atelier-de-la-semaine (S92 #182 unification single-CTA Beehiiv 2026) --}}

    {{-- 4-8. EN BREF — digest condensé (secondaire au hero) : actualités, outil, article, outil gratuit, terme.
         Chaque sous-bloc garde sa propre condition d'affichage : aucune donnée/section retirée, présentation compacte (titres + 1 ligne). --}}
    @php
        $hasDigest = (($topNews ?? null) && $topNews->count())
            || ($toolOfWeek ?? null)
            || ($featuredArticle ?? null)
            || ($interactiveTool ?? null)
            || ($aiTerm ?? null);
    @endphp
    @if($hasDigest)
    <tr>
        <td style="padding:26px 30px 8px;background-color:#f8fafc;" class="mobile-p">
            <p style="margin:0 0 4px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">En bref cette semaine</p>
            <p style="margin:0;font-size:14px;color:#6b7280;line-height:1.5;">Le reste de la veille, en version courte.</p>
        </td>
    </tr>
    @endif

    {{-- 4. ACTUALITES (condensé : titres + source) --}}
    @if(($topNews ?? null) && $topNews->count())
    <tr>
        <td style="padding:14px 30px 6px;background-color:#f8fafc;" class="mobile-p">
            <p style="margin:0 0 8px;font-size:12px;font-weight:bold;color:#0B7285;text-transform:uppercase;letter-spacing:1px;">&#x1F4F0; Actualités</p>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                @foreach($topNews as $news)
                <tr><td style="padding:8px 0;border-bottom:{{ $loop->last ? '0' : '1px solid #e5e7eb' }};">
                    <a href="{{ $news->url ?? route('news.show', $news->slug ?? '') }}" style="color:#1a1a2e;font-size:14px;font-weight:bold;text-decoration:none;line-height:1.4;">{{ $news->seo_title ?? $news->title ?? '' }}</a>
                    @if($news->source_name ?? null)<br/><span style="font-size:11px;color:#888;">{{ $news->source_name }}</span>@endif
                </td></tr>
                @endforeach
            </table>
            <p style="margin:10px 0 0;"><a href="{{ route('news.index') }}" style="color:#0B7285;font-weight:bold;font-size:13px;">Voir toutes les actualités &rarr;</a></p>
        </td>
    </tr>
    @endif

    {{-- 5. OUTIL DE LA SEMAINE (condensé : nom + badge + 1 ligne + lien) --}}
    @if($toolOfWeek ?? null)
    @php
        $pColor = match(strtolower($toolOfWeek->pricing ?? '')) { 'free','gratuit' => '#10b981', 'freemium' => '#f97316', default => '#6b7280' };
        $pLabel = match(strtolower($toolOfWeek->pricing ?? '')) { 'free','gratuit' => 'Gratuit', 'freemium' => 'Freemium', default => 'Payant' };
        $tutoCount = method_exists($toolOfWeek, 'resources') ? (int) $toolOfWeek->resources()->where('is_approved', true)->count() : 0;
    @endphp
    <tr>
        <td style="padding:14px 30px 6px;background-color:#f8fafc;border-top:1px solid #e5e7eb;" class="mobile-p">
            <p style="margin:0 0 6px;font-size:12px;font-weight:bold;color:#0B7285;text-transform:uppercase;letter-spacing:1px;">&#x1F527; Outil de la semaine</p>
            <p style="margin:0 0 4px;">
                <a href="{{ route('directory.show', $toolOfWeek->slug) }}" target="_blank" style="color:#1a1a2e;font-size:15px;font-weight:bold;text-decoration:none;">{{ $toolOfWeek->name }}</a>
                <span style="display:inline-block;background-color:{{ $pColor }};color:#fff;font-size:10px;font-weight:bold;padding:2px 7px;border-radius:3px;margin-left:6px;vertical-align:middle;">{{ $pLabel }}</span>
            </p>
            <p style="margin:0 0 4px;font-size:13px;color:#555;line-height:1.5;">{{ Str::limit(strip_tags($toolOfWeek->short_description ?? $toolOfWeek->description ?? ''), 120) }}</p>
            @if($tutoCount > 0)
            <p style="margin:0 0 4px;font-size:13px;color:#0B7285;font-weight:bold;">🎓 {{ $tutoCount }} {{ $tutoCount === 1 ? 'tutoriel' : 'tutoriels' }} pour bien démarrer.</p>
            @endif
            <p style="margin:2px 0 0;"><a href="{{ route('directory.show', $toolOfWeek->slug) }}" target="_blank" style="color:#0B7285;font-weight:bold;font-size:13px;">Découvrir sur laveille.ai &rarr;</a></p>
        </td>
    </tr>
    @endif

    {{-- 6. A LIRE (condensé : titre + 1 ligne + lien) --}}
    @if($featuredArticle ?? null)
    <tr>
        <td style="padding:14px 30px 6px;background-color:#f8fafc;border-top:1px solid #e5e7eb;" class="mobile-p">
            <p style="margin:0 0 6px;font-size:12px;font-weight:bold;color:#0B7285;text-transform:uppercase;letter-spacing:1px;">&#x1F4DD; À lire</p>
            <p style="margin:0 0 4px;"><a href="{{ route('blog.show', $featuredArticle->slug) }}" style="color:#1a1a2e;font-size:15px;font-weight:bold;text-decoration:none;line-height:1.4;">{{ $featuredArticle->title }}</a></p>
            <p style="margin:0 0 4px;font-size:13px;color:#555;line-height:1.5;">{{ Str::limit(strip_tags($featuredArticle->excerpt ?? $featuredArticle->content ?? ''), 120) }}</p>
            <p style="margin:2px 0 0;"><a href="{{ route('blog.show', $featuredArticle->slug) }}" style="color:#0B7285;font-weight:bold;font-size:13px;">Lire l'article &rarr;</a></p>
        </td>
    </tr>
    @endif

    {{-- 7. OUTIL GRATUIT (condensé : nom + 1 ligne + lien) --}}
    @if($interactiveTool ?? null)
    <tr>
        <td style="padding:14px 30px 6px;background-color:#f8fafc;border-top:1px solid #e5e7eb;" class="mobile-p">
            <p style="margin:0 0 6px;font-size:12px;font-weight:bold;color:#b45309;text-transform:uppercase;letter-spacing:1px;">&#x1F381; Outil gratuit à essayer</p>
            <p style="margin:0 0 4px;"><a href="{{ route('tools.show', $interactiveTool->slug) }}" target="_blank" style="color:#1a1a2e;font-size:15px;font-weight:bold;text-decoration:none;">{{ $interactiveTool->icon ?? '' }} {{ $interactiveTool->name }}</a></p>
            <p style="margin:0 0 4px;font-size:13px;color:#555;line-height:1.5;">{{ Str::limit(strip_tags($interactiveTool->description ?? ''), 120) }}</p>
            <p style="margin:2px 0 0;font-size:12px;color:#888;">100% gratuit, dans ton navigateur, aucune inscription. <a href="{{ route('tools.show', $interactiveTool->slug) }}" target="_blank" style="color:#b45309;font-weight:bold;text-decoration:none;">Essayer &rarr;</a></p>
        </td>
    </tr>
    @endif

    {{-- 8. TERME IA DE LA SEMAINE (condensé : terme + définition courte + lien glossaire) --}}
    @if($aiTerm ?? null)
    <tr>
        <td style="padding:14px 30px 20px;background-color:#f8fafc;border-top:1px solid #e5e7eb;" class="mobile-p">
            <p style="margin:0 0 6px;font-size:12px;font-weight:bold;color:#0B7285;text-transform:uppercase;letter-spacing:1px;">&#x1F4D6; Terme IA de la semaine</p>
            <p style="margin:0 0 4px;font-size:15px;font-weight:bold;color:#1a1a2e;">{{ $aiTerm->name ?? '' }}</p>
            <p style="margin:0 0 4px;font-size:13px;color:#555;line-height:1.5;">{{ Str::limit(strip_tags($aiTerm->definition ?? ''), 140) }}</p>
            @if(Route::has('dictionary.index'))
            <p style="margin:2px 0 0;"><a href="{{ route('dictionary.index') }}" target="_blank" style="color:#0B7285;font-weight:bold;font-size:13px;text-decoration:none;">Explorer le glossaire &rarr;</a></p>
            @endif
        </td>
    </tr>
    @endif

    {{-- Fin du digest condensé --}}
    @if($hasDigest ?? false)
    <tr><td height="1" bgcolor="#e5e7eb"></td></tr>
    @endif

    {{-- 9. LE SAVIEZ-VOUS? (promo raccourcisseur de liens — domaines au choix) --}}
    <tr>
        <td style="padding:25px 30px;background-color:#0c1427;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td>
                    <p style="margin:0 0 8px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#3dc9d8;font-weight:bold;">Le saviez-vous ?</p>
                    <p style="margin:0 0 14px;font-size:16px;color:#e2e8f0;line-height:1.6;">
                        Notre <a href="{{ config('app.url') }}/raccourcir" style="color:#3dc9d8;font-weight:bold;text-decoration:underline;">raccourcisseur de liens</a> te laisse choisir ton domaine : lurl.ca, veille.la, 1lien.ca, unlien.ca ou go3.ca. Un même lien fonctionne sur tous ces domaines, et comme 1lien.ca et unlien.ca s'écrivent presque pareil, ton contact le retrouve même s'il l'écrit autrement. Crée des liens courts avec code QR, statistiques de clics et aperçu social, gratuitement et sans inscription.
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
                    <a href="{{ lv_social('facebook') }}" style="color:#666;text-decoration:none;">Facebook</a>
                    &nbsp;&middot;&nbsp;
                    <a href="https://www.linkedin.com/in/lapointestephane/" style="color:#666;text-decoration:none;">LinkedIn</a>
                    &nbsp;&middot;&nbsp;
                    <a href="https://laveille.ai" style="color:#666;text-decoration:none;">Site web</a>
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
