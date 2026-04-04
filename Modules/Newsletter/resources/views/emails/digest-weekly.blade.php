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
                        <p style="margin:0 0 10px;font-size:15px;color:#333;line-height:1.6;"><strong>laveilledestef.com</strong> est devenu <strong><a href="{{ config('app.url') }}" style="color:#0B7285;">laveille.ai</a></strong> ! J'ai complètement repensé le site pour vous offrir la meilleure expérience de veille en intelligence artificielle. Bienvenue dans cette nouvelle aventure !</p>
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

    {{-- W4. PREMIER DÉFI — prompt animal de feu --}}
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
    @if($weeklyPrompt ?? null)
    <tr>
        <td style="padding:25px 30px;background-color:#0c1427;" class="mobile-p">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td align="center" style="padding-bottom:14px;"><span style="font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:1.5px;color:#3dc9d8;">Défi de la quinzaine</span></td></tr>
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
            <h3 style="margin:0 0 16px;font-size:14px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;">Actualités de la semaine</h3>
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
            <p style="margin:8px 0 0;text-align:center;"><a href="{{ route('news.index') }}" style="color:#0B7285;font-weight:bold;font-size:13px;">Voir toutes les actualités &rarr;</a></p>
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
                    <a href="{{ route('directory.show', $toolOfWeek->slug) }}" target="_blank" style="display:inline-block;background-color:#0B7285;color:#fff;padding:10px 22px;border-radius:4px;font-weight:bold;font-size:14px;text-decoration:none;">Découvrir sur laveille.ai &rarr;</a>
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
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#0B7285;font-weight:bold;">À lire cette semaine</p>
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
            <p style="margin:0 0 14px;font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:#b45309;font-weight:bold;">Outil gratuit à essayer</p>
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
                        <a href="{{ config('app.url') }}/raccourcir" style="color:#3dc9d8;font-weight:bold;text-decoration:underline;">veille.la</a> est le domaine utilisé pour notre raccourcisseur de liens. Créez des liens courts avec code QR, statistiques de clics et aperçu social, le tout gratuitement et sans inscription.
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
