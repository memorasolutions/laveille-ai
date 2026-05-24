<!DOCTYPE html>
<html lang="fr-CA" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $author->user?->name ?? $author->slug }} · laveille.ai</title>
    <meta name="description" content="{{ $author->bio ?? 'Auteur sur laveille.ai' }}">
    <meta property="og:title" content="{{ $author->user?->name ?? $author->slug }} · laveille.ai">
    <meta property="og:description" content="{{ $author->bio ?? '' }}">
    <meta property="og:image" content="{{ $author->profile_image ? asset('storage/'.$author->profile_image) : url('/images/default-avatar.svg') }}">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @php
        $__authorJsonLd = isset($jsonLd) ? json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
    @endphp
    @if($__authorJsonLd)
        <script type="application/ld+json">{!! $__authorJsonLd !!}</script>
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400..800&family=Fraunces:opsz,wght@9..144,300..900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --c-primary: #064E5A; --c-primary-hover: #043C45; --c-primary-light: #F0FAFB;
            --c-accent: #9A2A06; --c-accent-hover: #7A2105; --c-accent-light: #FDF5ED;
            --c-dark: #1A1D23; --c-text-secondary: #3A4050; --c-text-muted: #3F4554;
            --c-surface: #F8FAFB;
        }
        /* WCAG 2.2 AAA skip link — premier focusable, hidden tant que pas focus */
        .lv-skip-link {
            position: absolute; top: -100px; left: 0; z-index: 9999;
            background: #064E5A; color: #FFFFFF; padding: 12px 24px;
            font-weight: 700; text-decoration: none; border-radius: 0 0 8px 0;
            min-height: 44px; display: inline-flex; align-items: center;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }
        .lv-skip-link:focus, .lv-skip-link:focus-visible {
            top: 0;
            outline: 3px solid #9A2A06; outline-offset: 2px;
        }
        /* WCAG 2.5.5 AAA target size 44x44 minimum */
        .read-link {
            display: inline-flex; align-items: center; min-height: 44px;
            padding: 10px 12px; margin: -6px -8px;
            border-radius: 0.5rem;
        }
        .read-link:hover, .read-link:focus-visible {
            background: var(--c-primary-light);
        }
        .read-link:focus-visible {
            outline: 3px solid var(--c-accent); outline-offset: 2px;
        }
        [data-theme="dark"] {
            --c-primary: #4FB3C6; --c-primary-hover: #2A8DAE; --c-primary-light: #0F1419;
            --c-accent: #F97316; --c-accent-hover: #EA580C; --c-accent-light: #1E1B18;
            --c-dark: #E2E8F0; --c-text-secondary: #B8BDC9; --c-text-muted: #6B7280;
            --c-surface: #0F1419;
        }
        @view-transition { navigation: auto; }
        ::view-transition-old(root), ::view-transition-new(root) { animation-duration: 400ms; }
        .profile-img { view-transition-name: author-portrait; }
        body { background: var(--c-surface); color: var(--c-dark); font-family: 'Plus Jakarta Sans', system-ui, sans-serif; margin: 0; transition: background 300ms, color 300ms; }
        h1, h2, h3, h4, h5, h6 { color: var(--c-dark); margin: 0; }
        .hero-title { font-family: 'Fraunces', serif; font-variation-settings: 'opsz' 60, 'wght' 600; font-size: clamp(2.5rem, 6vw, 4rem); line-height: 1.05; letter-spacing: -0.02em; color: var(--c-primary); }
        .tagline { font-family: 'Plus Jakarta Sans'; font-variant: small-caps; font-weight: 600; letter-spacing: 0.08em; font-size: 14px; color: var(--c-text-muted); text-transform: lowercase; }
        .bento-hero { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        @media (min-width: 1024px) { .bento-hero { grid-template-columns: 3fr 2fr; } .bento-hero.single-col { grid-template-columns: 1fr; } }
        .bento-sub { display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-top: 1.5rem; }
        @media (min-width: 768px) { .bento-sub { grid-template-columns: repeat(3, 1fr); } }
        .bento-articles { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        @media (min-width: 768px) { .bento-articles { grid-template-columns: repeat(4, 1fr); grid-auto-rows: minmax(200px, auto); } .bento-articles > .featured { grid-column: span 2; grid-row: span 2; } }
        .glass-card { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%); border: 1px solid rgba(11, 114, 133, 0.12); border-radius: 1.25rem; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06); padding: 24px; transition: transform 200ms cubic-bezier(0.4, 0, 0.2, 1), box-shadow 200ms; }
        [data-theme="dark"] .glass-card { background: rgba(26, 32, 38, 0.7); border-color: rgba(79, 179, 198, 0.15); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); }
        .glass-card:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(11, 114, 133, 0.1); }
        .now-card { background: linear-gradient(135deg, rgba(11, 114, 133, 0.12) 0%, rgba(240, 250, 251, 0.7) 100%); border: 1px solid rgba(11, 114, 133, 0.2); }
        [data-theme="dark"] .now-card { background: linear-gradient(135deg, rgba(79, 179, 198, 0.15) 0%, rgba(26, 32, 38, 0.7) 100%); }
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; font-weight: 600; color: #fff; background: var(--c-accent); border: none; border-radius: 0.75rem; text-decoration: none; min-height: 44px; min-width: 44px; transition: background 200ms, transform 200ms; cursor: pointer; }
        .btn-primary:hover { background: var(--c-accent-hover); transform: scale(1.02); }
        .btn-primary:focus-visible { outline: 3px solid var(--c-accent); outline-offset: 2px; }
        .btn-secondary { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; font-weight: 600; color: var(--c-primary); background: transparent; border: 2px solid var(--c-primary); border-radius: 0.75rem; text-decoration: none; min-height: 44px; min-width: 44px; transition: background 200ms, transform 200ms; cursor: pointer; }
        .btn-secondary:hover { background: var(--c-primary-light); transform: scale(1.02); }
        .profile-img { width: 160px; height: 160px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12); }
        [data-theme="dark"] .profile-img { border-color: var(--c-card-dark, #1A2026); }
        .topbar { position: sticky; top: 0; z-index: 50; backdrop-filter: blur(12px); background: rgba(248, 250, 251, 0.85); border-bottom: 1px solid rgba(11, 114, 133, 0.1); }
        [data-theme="dark"] .topbar { background: rgba(15, 20, 25, 0.85); border-bottom-color: rgba(79, 179, 198, 0.15); }
        .theme-toggle { width: 44px; height: 44px; border-radius: 50%; background: var(--c-primary-light); color: var(--c-primary); border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: background 200ms; }
        .theme-toggle:hover { background: var(--c-primary); color: #fff; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 16px; }
        @media (min-width: 768px) { .container { padding: 0 24px; } }
        .fade-up { opacity: 0; transform: translateY(20px); transition: opacity 600ms cubic-bezier(0.4, 0, 0.2, 1), transform 600ms cubic-bezier(0.4, 0, 0.2, 1); }
        .fade-up.in-view { opacity: 1; transform: translateY(0); }
        @media (prefers-reduced-motion: reduce) { .fade-up { opacity: 1; transform: none; transition: none; } * { animation: none !important; transition: none !important; } }
        .article-title { font-family: 'Plus Jakarta Sans'; font-weight: 700; color: var(--c-dark); line-height: 1.25; }
        .article-meta { font-size: 13px; color: var(--c-text-muted); font-weight: 500; letter-spacing: 0.02em; }
        .read-link { color: var(--c-accent); font-weight: 600; text-decoration: none; transition: color 150ms; font-size: 14px; }
        .read-link:hover { color: var(--c-accent-hover); }
        .abandon-banner { background: var(--c-accent-light); border-left: 4px solid var(--c-accent); padding: 16px 24px; margin: 16px 0; border-radius: 0.5rem; color: var(--c-accent-hover); font-weight: 600; }
        .footer { padding: 48px 16px; text-align: center; color: var(--c-text-muted); font-size: 14px; }
        .footer a { color: var(--c-primary); font-weight: 600; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }
        .pill { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; border-radius: 999px; }
        .pill-now { background: var(--c-accent-light); color: var(--c-accent); }
        .pill-featured { background: var(--c-primary-light); color: var(--c-primary); }
        .social-link { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; min-width: 44px; min-height: 44px; border-radius: 50%; background: #FFFFFF; color: var(--c-primary); text-decoration: none; font-weight: 700; transition: background 200ms, color 200ms; font-size: 13px; border: 2px solid var(--c-primary); }
        .social-link:hover, .social-link:focus-visible { background: var(--c-primary); color: #fff; outline: 3px solid var(--c-accent); outline-offset: 2px; }
        [data-theme="dark"] .social-link { background: var(--c-primary-light); border-color: var(--c-primary); }
        section { padding: 32px 0; }
        @media (min-width: 768px) { section { padding: 48px 0; } }
    </style>
</head>
<body>
    <a href="#lv-main-content" class="lv-skip-link">Aller au contenu principal</a>

    <svg aria-hidden="true" focusable="false" style="position: fixed; inset: 0; pointer-events: none; opacity: 0.04; mix-blend-mode: multiply; z-index: -1; width: 100%; height: 100%;">
        <filter id="grain"><feTurbulence type="fractalNoise" baseFrequency="0.85" numOctaves="3"/></filter>
        <rect width="100%" height="100%" filter="url(#grain)"></rect>
    </svg>

    <nav class="topbar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; padding-bottom: 12px;">
            <a href="{{ url('/') }}" style="color: var(--c-primary); font-weight: 600; text-decoration: none; font-size: 14px;">← Retour à laveille.ai</a>
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="#articles" style="color: var(--c-primary); font-weight: 600; text-decoration: none;" class="hidden md:inline">Articles</a>
                <a href="#about" style="color: var(--c-primary); font-weight: 600; text-decoration: none;" class="hidden md:inline">À propos</a>
                <button id="theme-toggle" class="theme-toggle" aria-label="Basculer mode sombre">
                    <svg id="theme-icon" role="img" aria-label="Icône thème" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    @if($showAbandonBanner)
        <div class="container"><div class="abandon-banner fade-up">🌱 Pas publié depuis {{ $author->daysSinceLastPublish() }} jours. Le contenu reste accessible.</div></div>
    @endif

    <header class="container" style="padding-top: 32px;">
        <div class="bento-hero {{ $author->isModuleVisible('now') ? '' : 'single-col' }}">
            <div class="glass-card fade-up" style="text-align: center; padding: 48px 32px;">
                @php
                    $authorName = $author->user?->name ?? $author->slug;
                    $words = preg_split('/\s+/', trim($authorName));
                    $initials = strtoupper(mb_substr($words[0] ?? 'A', 0, 1) . (isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));
                    $hue = (crc32($author->slug) % 360);
                @endphp
                @if($author->profile_image)
                    <img src="{{ asset('storage/'.$author->profile_image) }}" alt="{{ $authorName }}" class="profile-img" style="margin: 0 auto 24px;">
                @else
                    <div class="profile-img" style="margin: 0 auto 24px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, hsl({{ $hue }}, 65%, 35%) 0%, hsl({{ ($hue + 40) % 360 }}, 65%, 55%) 100%); color: #fff; font-family: 'Fraunces', serif; font-weight: 700; font-size: 56px; letter-spacing: -0.02em;" role="img" aria-label="Avatar de {{ $authorName }}">
                        {{ $initials }}
                    </div>
                @endif
                <h1 class="hero-title">{{ $author->user?->name ?? $author->slug }}</h1>
                @if($author->manifesto || $author->bio)
                    <p style="font-size: 18px; color: var(--c-text-secondary); margin: 24px 0; line-height: 1.6; max-width: 540px; margin-left: auto; margin-right: auto;">{{ $author->manifesto ?: $author->bio }}</p>
                @endif
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 24px;">
                    <a href="#newsletter" class="btn-primary">📬 S'abonner</a>
                    <a href="#about" class="btn-secondary">À propos</a>
                </div>
                @if(!empty($author->social_links) && $author->isModuleVisible('social_links'))
                    <div style="display: flex; gap: 8px; justify-content: center; margin-top: 24px;">
                        @foreach($author->social_links as $platform => $url)
                            <a href="{{ $url }}" class="social-link" target="_blank" rel="noopener" aria-label="{{ ucfirst($platform) }}">{{ strtoupper(substr($platform, 0, 2)) }}</a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($author->isModuleVisible('now'))
            <div class="glass-card now-card fade-up" style="display: flex; flex-direction: column; justify-content: space-between; gap: 20px;">
                @php
                    $latestStatus = $timeline->first(function ($item) { return isset($item->content_type); });
                    $latestArticle = $timeline->first(function ($item) { return ! isset($item->content_type); });
                    $daysSince = $author->daysSinceLastPublish();
                @endphp

                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                        <span class="pill pill-now">🔥 En ce moment</span>
                        @if($latestStatus && $latestStatus->published_at)
                            <span style="font-size: 11px; color: var(--c-text-muted); font-weight: 500;">· {{ $latestStatus->published_at->diffForHumans() }}</span>
                        @elseif($daysSince > 0)
                            <span style="font-size: 11px; color: var(--c-text-muted); font-weight: 500;">· il y a {{ $daysSince }} jours</span>
                        @endif
                    </div>

                    @if($latestStatus)
                        <p style="font-size: 17px; line-height: 1.55; color: var(--c-dark); margin: 0 0 12px; font-weight: 500;">{{ \Illuminate\Support\Str::limit(strip_tags($latestStatus->content ?? ''), 180) }}</p>
                    @else
                        <p style="font-size: 17px; line-height: 1.55; color: var(--c-dark); margin: 0 0 12px; font-weight: 500;">📝 Prépare la prochaine publication. Reste à l'écoute.</p>
                    @endif
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; padding-top: 16px; border-top: 1px dashed rgba(11, 114, 133, 0.18);">
                    @if($latestArticle && !empty($latestArticle->slug))
                        <a href="{{ url('/blog/'.$latestArticle->slug) }}" style="display: flex; align-items: center; gap: 8px; color: var(--c-primary); text-decoration: none; font-size: 14px; font-weight: 600; transition: color 150ms;">
                            <span style="font-size: 16px;">📖</span>
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ \Illuminate\Support\Str::limit($latestArticle->title ?? 'Dernier article', 50) }}</span>
                            <span style="margin-left: auto; color: var(--c-accent);">→</span>
                        </a>
                    @endif

                    <div style="display: flex; align-items: center; gap: 16px; font-size: 12px; color: var(--c-text-muted); font-weight: 500;">
                        <span style="display: inline-flex; align-items: center; gap: 4px;">
                            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: {{ $daysSince < 30 ? '#22C55E' : ($daysSince < 90 ? '#F59E0B' : '#94A3B8') }}; animation: pulse 2s ease-in-out infinite;"></span>
                            {{ $daysSince < 7 ? 'Actif cette semaine' : ($daysSince < 30 ? 'Actif ce mois-ci' : ($daysSince < 90 ? 'Activité ralentie' : 'En veille')) }}
                        </span>
                        <span>· {{ $timeline->count() }} publications</span>
                    </div>
                </div>
            </div>

            <style>
                @keyframes pulse {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.5; transform: scale(1.3); }
                }
            </style>
            @endif
        </div>

        @php
            $visibleSubModules = collect(['stats', 'newsletter', 'about'])->filter(fn ($m) => $author->isModuleVisible($m));
        @endphp
        @if($visibleSubModules->isNotEmpty())
        <div class="bento-sub" style="grid-template-columns: repeat({{ min($visibleSubModules->count(), 3) }}, 1fr);">
            @if($author->isModuleVisible('stats'))
            <div class="glass-card fade-up">
                <h2 style="font-size: 13px; font-weight: 700; color: var(--c-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">📊 Statistiques</h2>
                <p style="font-size: 32px; font-weight: 800; color: var(--c-primary); font-family: 'Fraunces', serif; line-height: 1; margin: 0;">{{ $timeline->count() }}</p>
                <p style="color: var(--c-text-muted); font-size: 14px; margin-top: 4px;">publications</p>
                @if(!empty($author->qualifications))
                    <p style="margin-top: 12px; font-size: 14px; color: var(--c-text-secondary);">{{ count($author->qualifications) }} qualifications · membre depuis {{ $author->created_at?->format('Y') }}</p>
                @endif
            </div>
            @endif

            @if($author->isModuleVisible('newsletter'))
            <div id="newsletter" class="fade-up">
                <x-authors::newsletter-optin :author="$author" variant="inline" />
            </div>
            @endif

            @if($author->isModuleVisible('about'))
            <div id="about" class="glass-card fade-up">
                <h2 style="font-size: 13px; font-weight: 700; color: var(--c-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">À propos</h2>
                @if($author->bio)
                    <p style="font-size: 14px; line-height: 1.6; color: var(--c-text-secondary);">{{ \Illuminate\Support\Str::limit($author->bio, 220) }}</p>
                @endif
                @if(!empty($author->qualifications) && $author->isModuleVisible('qualifications_list'))
                    <ul style="margin-top: 12px; font-size: 13px; color: var(--c-text-muted); list-style: none; padding: 0;">
                        @foreach($author->qualifications as $q)
                            <li style="margin-bottom: 4px;">• {{ $q }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            @endif
        </div>
        @endif
    </header>

    <nav aria-label="Fil d'Ariane" class="container" style="padding-top: 8px; padding-bottom: 8px; font-size: 13px; color: var(--c-text-muted);">
        <ol style="list-style: none; padding: 0; margin: 0; display: flex; gap: 8px; flex-wrap: wrap;">
            <li><a href="{{ url('/') }}" style="color: var(--c-primary); text-decoration: none;">Accueil</a></li>
            <li aria-hidden="true">›</li>
            <li><a href="{{ url('/auteurs') }}" style="color: var(--c-primary); text-decoration: none;">Auteurs</a></li>
            <li aria-hidden="true">›</li>
            <li aria-current="page" style="color: var(--c-text-secondary);">{{ $author->display_name ?? $author->slug }}</li>
        </ol>
    </nav>

    <main class="container" id="lv-main-content" role="main" style="max-width: 1200px;">
        @php
            $articles = $timeline->filter(function ($i) { return ! isset($i->content_type); })->values();
        @endphp

        @if($articles->isNotEmpty() && $author->isModuleVisible('recent_articles'))
            <section id="articles">
                <h2 style="font-family: 'Fraunces', serif; font-variation-settings: 'opsz' 48, 'wght' 600; font-size: clamp(28px, 4vw, 40px); color: var(--c-primary); margin-bottom: 24px;" class="fade-up">⭐ Publications récentes</h2>
                <div class="bento-articles">
                    @php $featured = $articles->first(); @endphp
                    @if($featured && $author->isModuleVisible('featured'))
                        <article class="glass-card featured fade-up" style="display: flex; flex-direction: column; justify-content: space-between; padding: 32px;">
                            <div>
                                <span class="pill pill-featured" style="margin-bottom: 16px;">⭐ À lire d'abord</span>
                                <h3 class="article-title" style="font-size: 26px; margin-bottom: 16px;">{{ $featured->title ?? \Illuminate\Support\Str::limit(strip_tags($featured->content ?? ''), 80) }}</h3>
                                @if(!empty($featured->excerpt))
                                    <p style="color: var(--c-text-secondary); font-size: 16px; line-height: 1.6; margin-bottom: 16px;">{{ \Illuminate\Support\Str::limit($featured->excerpt, 200) }}</p>
                                @endif
                            </div>
                            <div>
                                <div class="article-meta" style="margin-bottom: 8px;">{{ optional($featured->published_at)->format('j F Y') }}</div>
                                @if(!empty($featured->slug))
                                    <a href="{{ url('/blog/'.$featured->slug) }}" class="read-link">Lire l'article complet →</a>
                                @endif
                            </div>
                        </article>
                    @endif

                    @foreach($articles->skip(1)->take(4) as $article)
                        <article class="glass-card fade-up" style="display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div class="article-meta" style="margin-bottom: 8px;">{{ optional($article->published_at)->format('j M Y') }}</div>
                                <h3 class="article-title" style="font-size: 16px; margin-bottom: 12px;">{{ \Illuminate\Support\Str::limit($article->title ?? '', 70) }}</h3>
                            </div>
                            @if(!empty($article->slug))
                                <a href="{{ url('/blog/'.$article->slug) }}" class="read-link" style="margin-top: 12px;">Lire →</a>
                            @endif
                        </article>
                    @endforeach
                </div>

                @if($articles->count() > 5)
                    <div style="text-align: center; margin-top: 32px;" class="fade-up">
                        <a href="#" class="btn-secondary">Voir les {{ $articles->count() }} publications →</a>
                    </div>
                @endif
            </section>
        @endif
    </main>

    <footer class="footer">
        <p>Publié sur <a href="https://laveille.ai">laveille.ai</a> · <span style="font-family: monospace;">{{ '@'.$author->slug }}</span></p>
    </footer>

    <script>
        // Dark mode toggle premium
        const html = document.documentElement;
        const toggle = document.getElementById('theme-toggle');
        const icon = document.getElementById('theme-icon');
        const sunIcon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>';
        const moonIcon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>';

        function setTheme(t) {
            html.setAttribute('data-theme', t);
            localStorage.setItem('theme', t);
            icon.innerHTML = t === 'dark' ? moonIcon : sunIcon;
        }
        const saved = localStorage.getItem('theme');
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        setTheme(saved || (systemDark ? 'dark' : 'light'));
        toggle.addEventListener('click', () => setTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));

        // Scroll storytelling Intersection Observer
        if ('IntersectionObserver' in window) {
            const obs = new IntersectionObserver(entries => {
                entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('in-view'); });
            }, { threshold: 0.1 });
            document.querySelectorAll('.fade-up').forEach(el => obs.observe(el));
        } else {
            document.querySelectorAll('.fade-up').forEach(el => el.classList.add('in-view'));
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</body>
</html>
