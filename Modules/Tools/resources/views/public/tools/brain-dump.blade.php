<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Brain Dump 2026 — 10 min de papier + 30 sec d\'IA · La veille')
@section('meta_description', 'Outil gratuit pour décharger ton mental en 10 minutes papier + IA. Validé par la science (Mueller & Oppenheimer 2014). 100% privé, aucune donnée chez nous.')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Brain Dump 2026', 'breadcrumbItems' => [__('Outils'), 'Brain Dump 2026']])
@endsection

@section('og_type', 'website')
@section('share_text', 'Brain Dump 2026 — 10 min de papier + 30 sec d\'IA pour vider et structurer ton mental.')

@push('scripts')
{{-- JSON-LD multi-couche : SoftwareApplication + HowTo + FAQPage + BreadcrumbList + Person (auteur) — AEO/GEO 2026 +30% Perplexity visibility --}}
@php
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'SoftwareApplication',
                'name' => 'Brain Dump 2026',
                'applicationCategory' => 'ProductivityApplication',
                'operatingSystem' => 'Web',
                'url' => url('/outils/brain-dump'),
                'description' => "Outil gratuit pour décharger mentalement par écriture manuscrite puis structurer avec l'IA. Combine papier + IA OCR + chain prompting.",
                'isAccessibleForFree' => true,
                'inLanguage' => 'fr-CA',
                'provider' => ['@type' => 'Organization', 'name' => 'La veille', 'url' => url('/')],
                'featureList' => [
                    'Minuteur 10 minutes intégré',
                    'Prompt chain prompting prêt à copier',
                    'Ouverture directe dans ChatGPT, Claude, Gemini, Perplexity, Mistral, Copilot',
                    '100% client-side, aucune donnée serveur',
                ],
            ],
            [
                '@type' => 'HowTo',
                'name' => "Comment faire un Brain Dump 2026 avec l'IA",
                'description' => 'Méthode en 5 étapes pour vider mentalement par écriture manuelle puis structurer avec une IA.',
                'totalTime' => 'PT15M',
                'step' => [
                    ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Papier + stylo, 10 minutes', 'text' => "Vide tout ce qui occupe ton esprit. Pas de jugement, pas d'ordre, juste sortir."],
                    ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Biffer le sensible', 'text' => "Biffe ou plie les passages sensibles (santé, finances, conflits RH, données tiers) avant la photo. Protection Loi 25 d'abord."],
                    ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Photo téléphone', 'text' => "Prends une photo de la page avec un cadrage net. Ton IA va lire l'écriture manuscrite."],
                    ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Lancer le prompt IA', 'text' => "Copie la Partie 1 (transcription) + Partie 2 (analyse) dans ton IA préférée — chain prompting validé en 2 étapes."],
                    ['@type' => 'HowToStep', 'position' => 5, 'name' => 'Sauvegarde et relecture', 'text' => "Sauvegarde dans ton app notes préférée. Relis 24 h plus tard — les vrais insights émergent souvent à J+1."],
                ],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => [
                    ['@type' => 'Question', 'name' => "Qu'est-ce qu'un Brain Dump et à quoi ça sert ?", 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "Un Brain Dump est une décharge mentale par écriture libre pendant 10 minutes. Ça libère la mémoire de travail, réduit le stress cognitif et fait émerger des priorités cachées. La version 2026 ajoute une étape IA pour structurer le brouillon en actions concrètes."]],
                    ['@type' => 'Question', 'name' => 'Combien de temps prend un Brain Dump complet ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "10 minutes d'écriture manuscrite + 30 secondes pour copier-coller le prompt dans une IA + quelques minutes pour relire l'analyse. Total : 15-20 minutes par séance."]],
                    ['@type' => 'Question', 'name' => "Le Brain Dump fonctionne-t-il pour l'anxiété ?", 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "Les recherches de James Pennebaker (1997-2018) sur l'expressive writing montrent que l'écriture libre régulière réduit les symptômes anxieux. La version 2026 avec IA aide à transformer le brouillon en actions, ce qui amplifie l'effet déchargement."]],
                    ['@type' => 'Question', 'name' => 'Quelle IA est la meilleure pour transcrire un manuscrit en 2026 ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "En mai 2026, Apple Intelligence Notes (OCR on-device), Claude Vision (Anthropic) et Google Lens offrent les meilleures précisions sur le manuscrit cursif standard. Pour le maximum d'intimité, l'OCR on-device (Apple) garde tes données sur l'appareil."]],
                    ['@type' => 'Question', 'name' => 'Mes notes manuscrites sont-elles privées ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "L'outil laveille.ai est 100% côté navigateur : aucune donnée n'est envoyée à nos serveurs. Le prompt va directement dans l'IA que tu choisis. Pour la protection Loi 25 maximale, biffe les passages sensibles avant la photo et privilégie un OCR on-device."]],
                    ['@type' => 'Question', 'name' => 'Tablette ou papier — lequel est mieux ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "Les recherches en neurosciences (Van der Meer et al., 2024, Frontiers in Psychology) suggèrent que l'écriture manuscrite sur papier active plus de zones cérébrales liées à la mémoire que l'écriture sur tablette. Mais une tablette avec stylet reste meilleure que le clavier pour la décharge mentale."]],
                    ['@type' => 'Question', 'name' => 'Quelle est la différence avec un journal classique ?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "Le journal classique est narratif et chronologique. Le Brain Dump est libre, sans structure, et conçu pour la décharge rapide. La version 2026 ajoute l'IA pour transformer le brouillon en actions, ce qui sort le carnet du tiroir."]],
                ],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Outils', 'item' => url('/outils')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => 'Brain Dump 2026', 'item' => url('/outils/brain-dump')],
                ],
            ],
            [
                '@type' => 'Person',
                'name' => 'Stéphane Lapointe',
                'url' => url('/'),
                'sameAs' => ['https://www.linkedin.com/in/lapointestephane/'],
                'jobTitle' => "Veille technologique IA · Québec",
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<style>
[x-cloak] { display: none !important; }
.bd-page { --c-bg: #F0F4F8; --c-surface: #fff; --c-dark: #1a1d23; --c-muted: #52586a; }
.bd-page *, .bd-page *::before, .bd-page *::after { box-sizing: border-box; }
.bd-section { padding: 3rem 0; }
.bd-section--alt { background: var(--c-bg); }
.bd-container { max-width: 760px; margin: 0 auto; padding: 0 1rem; }
.bd-container--wide { max-width: 1000px; }

/* Hero refonte v1.14.0 — clarté 5s (combo A+F best practices 2026) */
.bd-hero { padding: 2.5rem 0 2rem; background: linear-gradient(135deg, #fff 0%, var(--c-bg) 100%); }
.bd-hero__intro { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1rem; }
.bd-hero h1 { font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 800; color: var(--c-dark); font-size: 2.5rem; margin: 0 0 .35rem; line-height: 1.15; letter-spacing: -.5px; }
.bd-hero__tagline { font-size: 1.375rem; color: var(--c-dark); margin: 0; line-height: 1.35; font-weight: 600; max-width: 640px; }
.bd-hero__tagline strong { color: var(--c-primary, #0B7285); }
.bd-hero__demo { margin: 1.75rem 0; width: 100%; max-width: 720px; }
.bd-hero__demo svg { width: 100% !important; height: auto !important; max-width: 720px; display: block; }
.bd-hero__steps { display: grid; grid-template-columns: 1fr; gap: .85rem; margin: 0 0 1.5rem; }
@media (min-width: 640px) { .bd-hero__steps { grid-template-columns: repeat(3, 1fr); } }
.bd-hero__step { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 1rem 1.15rem; display: flex; flex-direction: column; gap: .35rem; }
.bd-hero__step__head { display: flex; align-items: center; gap: .65rem; }
.bd-hero__step__num { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: var(--c-primary, #0B7285); color: #fff; border-radius: 50%; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 800; font-size: .95rem; flex-shrink: 0; }
.bd-hero__step__icon { font-size: 1.5rem; line-height: 1; }
.bd-hero__step__title { font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0; }
.bd-hero__step__desc { color: var(--c-text-secondary, #4a4f5c); font-size: .875rem; line-height: 1.45; margin: 0; }
.bd-hero__cta { display: flex; flex-wrap: wrap; gap: .85rem; align-items: center; margin: 0 0 1.25rem; }
.bd-btn-primary { display: inline-flex; align-items: center; gap: .5rem; padding: .85rem 1.5rem; min-height: 48px; background: var(--c-primary, #0B7285); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 1.0625rem; cursor: pointer; text-decoration: none; transition: all .15s ease; }
.bd-btn-primary:hover { background: #053640; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(6,78,90,.25); text-decoration: none; }
.bd-btn-primary:focus-visible { outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 2px; }
.bd-btn-link { color: var(--c-primary, #0B7285); font-weight: 600; font-size: .95rem; text-decoration: none; padding: .5rem 0; }
.bd-btn-link:hover { text-decoration: underline; }
.bd-hero__signals { display: flex; flex-wrap: wrap; gap: .55rem; margin: 0 0 1rem; }
.bd-hero__signal { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .75rem; background: #fff; border: 1px solid #E5E7EB; border-radius: 999px; font-size: .8125rem; color: var(--c-dark); font-weight: 600; }
.bd-hero__signal strong { color: var(--c-primary, #0B7285); }
.bd-hero__byline { margin: 0; font-size: .8125rem; color: var(--c-text-muted, #52586a); display: flex; flex-wrap: wrap; align-items: center; gap: .65rem; }
.bd-hero__byline a { color: var(--c-primary, #0B7285); font-weight: 600; }
.bd-hero__tldr { background: rgba(11,114,133,.06); border-left: 4px solid var(--c-primary, #0B7285); padding: .85rem 1.15rem; border-radius: 0 8px 8px 0; font-size: 1rem; line-height: 1.6; color: var(--c-dark); margin: 1.5rem 0 0; }
@media (max-width: 540px) {
    .bd-hero h1 { font-size: 1.875rem; }
    .bd-hero__tagline { font-size: 1.125rem; }
    .bd-hero__intro { flex-direction: column; text-align: center; align-items: center; gap: .85rem; }
    .bd-hero__step { padding: .85rem 1rem; }
}

/* Tool actif — uniformité charte Memora (pattern constructeur-prompts) */
.bd-tool { background: #fff; color: var(--c-dark); padding: 2rem 1.5rem; border-radius: 16px; box-shadow: 0 4px 16px rgba(6,78,90,.08); border: 1px solid #E5E7EB; }
.bd-tool__label { display: inline-block; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--c-primary, #0B7285); margin: 0 0 .85rem; }
.bd-tool h2 { font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 800; color: var(--c-dark); font-size: 1.75rem; margin: 0 0 .5rem; }
.bd-tool__intro { color: var(--c-muted); margin: 0 0 1.5rem; font-size: .95rem; line-height: 1.5; }
.bd-tool__intro strong { color: var(--c-dark); }
.bd-timer { background: linear-gradient(135deg, rgba(11,114,133,.06) 0%, rgba(82,184,199,.10) 100%); border: 1px solid rgba(11,114,133,.18); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; text-align: center; }
.bd-timer__display { font-family: 'Consolas', 'Menlo', monospace; font-size: 3rem; font-weight: 800; color: var(--c-primary, #0B7285); letter-spacing: 2px; margin: 0 0 .85rem; line-height: 1; }
.bd-timer__controls { display: flex; gap: .5rem; justify-content: center; flex-wrap: wrap; }
.bd-timer__btn { padding: .65rem 1.25rem; min-height: 44px; background: #064E5A; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: .95rem; transition: all .15s ease; }
.bd-timer__btn:hover { background: #053640; transform: translateY(-1px); }
.bd-timer__btn:focus-visible { outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 2px; }
.bd-timer__btn--ghost { background: transparent; color: #064E5A; border: 1.5px solid #064E5A; }
.bd-timer__btn--ghost:hover { background: #064E5A; color: #fff; }
.bd-tool__partlabel { display: inline-flex; align-items: center; gap: .5rem; padding: .35rem .85rem .35rem .35rem; border-radius: 999px; background: rgba(11,114,133,.08); color: var(--c-primary, #0B7285); font-size: .8125rem; font-weight: 700; margin: 1.25rem 0 .65rem; border: 1px solid rgba(11,114,133,.18); }
.bd-tool__partlabel__num { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; background: var(--c-primary, #0B7285); color: #fff; border-radius: 50%; font-weight: 800; font-size: .875rem; }
.bd-tool__prenote { font-size: .95rem; color: #374151; margin: 0 0 .65rem; line-height: 1.5; }
.bd-tool__codeblock { background: #F0F8F9; border: 1px solid rgba(11,114,133,.18); border-left: 4px solid var(--c-primary, #0B7285); border-radius: 8px; padding: 1rem 1.25rem; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: .95rem; color: var(--c-dark); line-height: 1.65; white-space: normal; margin: 0 0 .65rem; }
.bd-tool__codeblock .bd-token { color: var(--c-accent, #9A2A06); font-weight: 700; }
.bd-tool__postnote { font-size: .875rem; color: var(--c-muted); font-style: italic; margin: 0 0 1.25rem; }
.bd-tool__copy { display: inline-flex; align-items: center; gap: .4rem; padding: .65rem 1.15rem; min-height: 44px; background: #064E5A; color: #fff; border: none; border-radius: 8px; font-size: .9rem; font-weight: 600; cursor: pointer; transition: all .15s ease; }
.bd-tool__copy:hover { background: #053640; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(6,78,90,.18); }
.bd-tool__copy:focus-visible { outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 2px; }
.bd-tool__divider { border: 0; border-top: 1px dashed rgba(11,114,133,.25); margin: 1.5rem 0; }
.bd-tool__tip { font-size: .9rem; color: #374151; margin: .65rem 0 0; line-height: 1.5; }
.bd-tool__tip strong { color: var(--c-primary, #0B7285); }

/* Mad Libs — chips inline cliquables (approche C+E mai 2026) */
.bd-madlibs { background: #F0F8F9; border: 1px solid rgba(11,114,133,.18); border-left: 4px solid var(--c-primary, #0B7285); border-radius: 8px; padding: 1.25rem 1.5rem; font-size: .95rem; color: var(--c-dark); line-height: 1.85; margin: 0 0 .65rem; position: relative; }
.bd-madlibs p { margin: 0 0 .65rem; }
.bd-madlibs p:last-child { margin-bottom: 0; }
.bd-madlibs__num { font-weight: 800; color: var(--c-primary, #0B7285); margin-right: .25rem; }
.bd-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .7rem; min-height: 32px; background: #fff; color: var(--c-primary, #0B7285); border: 1.5px dashed rgba(11,114,133,.5); border-radius: 6px; font-size: .9rem; font-weight: 700; font-family: inherit; cursor: pointer; transition: all .15s ease; line-height: 1.2; vertical-align: baseline; }
.bd-chip:hover { background: rgba(11,114,133,.08); border-color: var(--c-primary, #0B7285); border-style: solid; transform: translateY(-1px); }
.bd-chip:focus-visible { outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 2px; }
.bd-chip[aria-expanded="true"] { background: var(--c-primary, #0B7285); color: #fff; border-color: var(--c-primary, #0B7285); border-style: solid; }
.bd-chip__caret { font-size: .75rem; line-height: 1; }
.bd-chip--mini { font-size: .85rem; padding: .15rem .55rem; min-height: 28px; }
.bd-popover { display: block; position: absolute; z-index: 50; background: #fff; border: 1px solid rgba(11,114,133,.25); border-radius: 10px; padding: .85rem; box-shadow: 0 12px 28px rgba(6,78,90,.18); min-width: 220px; max-width: 320px; margin-top: .4rem; left: 0; top: 100%; }
.bd-popover__title { display: block; font-size: .75rem; font-weight: 700; color: var(--c-primary, #0B7285); text-transform: uppercase; letter-spacing: .5px; margin: 0 0 .5rem; }
.bd-popover__opt { display: flex; align-items: center; gap: .65rem; padding: .5rem .65rem; border-radius: 6px; cursor: pointer; font-size: .9rem; color: var(--c-dark); min-height: 40px; transition: background .12s ease; line-height: 1.3; }
.bd-popover__opt:hover { background: rgba(11,114,133,.08); }
.bd-popover__opt:has(input:checked) { background: rgba(11,114,133,.12); font-weight: 700; color: var(--c-primary, #0B7285); }
.bd-popover__opt input[type="checkbox"], .bd-popover__opt input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none; }
.bd-popover__opt .bd-check { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; min-width: 22px; border: 2px solid var(--c-primary, #0B7285); background: #fff; flex-shrink: 0; box-sizing: border-box; transition: background .12s ease; position: relative; }
.bd-popover__opt .bd-check--box { border-radius: 4px; }
.bd-popover__opt .bd-check--radio { border-radius: 50%; }
.bd-popover__opt input:checked ~ .bd-check { background: var(--c-primary, #0B7285); border-color: var(--c-primary, #0B7285); }
.bd-popover__opt input[type="checkbox"]:checked ~ .bd-check--box::after { content: '✓'; color: #fff; font-size: 15px; font-weight: 900; line-height: 1; }
.bd-popover__opt input[type="radio"]:checked ~ .bd-check--radio::after { content: ''; width: 10px; height: 10px; background: #fff; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
.bd-popover__opt input:focus-visible ~ .bd-check { outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 2px; }
.bd-popover__opt input[type="number"] { position: static; opacity: 1; width: 64px; height: auto; padding: .35rem .5rem; border: 1.5px solid rgba(11,114,133,.3); border-radius: 6px; font-size: .95rem; font-weight: 700; text-align: center; color: var(--c-dark); pointer-events: auto; }
.bd-popover__opt input[type="number"]:focus { outline: 2px solid var(--c-primary, #0B7285); outline-offset: 0; border-color: var(--c-primary, #0B7285); }
.bd-popover__hint { display: block; font-size: .75rem; color: var(--c-muted); font-style: italic; margin: .35rem 0 0; }
.bd-madlibs__values { display: block; font-size: .85rem; font-weight: 600; color: var(--c-muted); margin-top: .25rem; line-height: 1.3; }
@media (max-width: 540px) {
    .bd-madlibs { line-height: 1.8; padding: 1rem 1.15rem; }
    .bd-chip { font-size: .85rem; padding: .2rem .6rem; min-height: 32px; }
    .bd-popover { left: .5rem; right: .5rem; min-width: auto; max-width: none; }
}

/* Sections content */
.bd-section h2 { font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 800; color: var(--c-dark); font-size: 1.875rem; margin: 0 0 .5rem; line-height: 1.2; }
.bd-section__lead { font-size: 1.0625rem; color: #374151; line-height: 1.6; margin: 0 0 2rem; }
.bd-steps { display: grid; gap: 1rem; }
@media (min-width: 768px) { .bd-steps { grid-template-columns: repeat(2, 1fr); } }
.bd-step { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; padding: 1.25rem 1.5rem; transition: all .15s ease; }
.bd-step:hover { box-shadow: 0 8px 20px rgba(6,78,90,.08); transform: translateY(-2px); }
.bd-step__num { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: var(--c-primary, #0B7285); color: #fff; border-radius: 50%; font-weight: 800; font-size: 1.125rem; margin-bottom: .85rem; }
.bd-step h3 { font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; color: var(--c-dark); font-size: 1.125rem; margin: 0 0 .5rem; line-height: 1.3; }
.bd-step p { color: #374151; font-size: .95rem; line-height: 1.5; margin: 0; }

/* Sources scientifiques */
.bd-citation { background: #fff; border-left: 4px solid var(--c-accent, #9A2A06); padding: 1.25rem 1.5rem; border-radius: 0 12px 12px 0; margin: 0 0 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
.bd-citation__author { font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0 0 .35rem; }
.bd-citation__year { color: var(--c-muted); font-weight: 500; }
.bd-citation__quote { font-style: italic; color: #374151; line-height: 1.6; margin: 0 0 .65rem; }
.bd-citation__source { font-size: .8125rem; color: var(--c-muted); margin: 0; }
.bd-citation__source a { color: var(--c-primary, #0B7285); text-decoration: underline; }

/* Quelle IA utiliser (5 cards) */
.bd-profiles { display: grid; gap: .75rem; }
@media (min-width: 640px) { .bd-profiles { grid-template-columns: repeat(2, 1fr); } }
.bd-profile { background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: .85rem 1.15rem; font-size: .95rem; line-height: 1.5; color: var(--c-dark); }
.bd-profile strong { color: var(--c-primary, #0B7285); font-weight: 700; }
.bd-profile__note { margin: 1.25rem 0 0; padding: .85rem 1.15rem; background: var(--c-primary-light, #F0FAFB); border-left: 4px solid var(--c-primary, #0B7285); border-radius: 0 8px 8px 0; font-size: .9rem; line-height: 1.55; color: var(--c-dark); }
.bd-profile__note strong { color: var(--c-primary, #0B7285); }

/* Bonus 24h */
.bd-bonus { background: #ecfdf5; border: 1px dashed #6ee7b7; border-radius: 12px; padding: 1.25rem 1.5rem; margin: 1.5rem 0 0; }
.bd-bonus__label { display: inline-block; font-size: .75rem; font-weight: 700; color: #065f46; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 .5rem; }
.bd-bonus h3 { font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; color: #064e3b; font-size: 1.125rem; margin: 0 0 .5rem; }
.bd-bonus p { color: #064e3b; font-size: .95rem; line-height: 1.6; margin: 0; }

/* FAQ */
.bd-faq__item { background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; margin: 0 0 .75rem; overflow: hidden; }
.bd-faq__q { width: 100%; padding: 1rem 1.25rem; background: transparent; border: none; text-align: left; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; color: var(--c-dark); font-size: 1rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 1rem; min-height: 56px; }
.bd-faq__q:hover { background: #F9FAFB; }
.bd-faq__q[aria-expanded="true"] .bd-faq__chevron { transform: rotate(180deg); }
.bd-faq__chevron { transition: transform .2s ease; font-size: 1.25rem; color: var(--c-primary, #0B7285); flex-shrink: 0; }
.bd-faq__a { padding: 0 1.25rem 1.25rem; color: #374151; font-size: .95rem; line-height: 1.6; }
.bd-faq__a a { color: var(--c-primary, #0B7285); }

/* Références */
.bd-refs { background: var(--c-surface); border: 1px solid #E5E7EB; border-radius: 12px; padding: 1.25rem 1.5rem; }
.bd-refs ol { margin: 0; padding-left: 1.25rem; font-size: .875rem; line-height: 1.7; color: #374151; }
.bd-refs li { margin-bottom: .65rem; }
.bd-refs a { color: var(--c-primary, #0B7285); text-decoration: underline; word-break: break-all; }

/* CTA conversion */
.bd-cta { background: linear-gradient(135deg, var(--c-primary, #0B7285) 0%, #053640 100%); color: #fff; border-radius: 20px; padding: 2.5rem 2rem; text-align: center; margin: 2rem 0; }
.bd-cta h2 { color: #fff; font-size: 1.875rem; margin: 0 0 .5rem; }
.bd-cta p { color: #cbd5e1; font-size: 1.0625rem; margin: 0 0 1.5rem; }
.bd-cta__buttons { display: flex; flex-wrap: wrap; gap: .85rem; justify-content: center; }
.bd-cta .bd-btn-primary { background: #fff; color: var(--c-primary, #0B7285); }
.bd-cta .bd-btn-primary:hover { background: #f9fafb; color: var(--c-primary, #0B7285); }
.bd-cta .bd-btn-link { color: #fff; border: 1.5px solid rgba(255,255,255,.4); padding: .85rem 1.5rem; border-radius: 10px; }
.bd-cta .bd-btn-link:hover { background: rgba(255,255,255,.1); text-decoration: none; }

@media (prefers-reduced-motion: reduce) {
    .bd-btn-primary, .bd-step, .bd-faq__chevron { transition: none; }
    .bd-btn-primary:hover, .bd-step:hover { transform: none; }
}
</style>

<div class="bd-page" x-data="brainDumpPage()" x-cloak>

    {{-- HERO refonte v1.14.0 — combo A+F (visuel Before/After + 3 étapes icônes), clarté 5s, NN/g 2026 < 50 mots --}}
    <section class="bd-hero" aria-labelledby="bd-h1">
        <div class="bd-container">
            <div class="bd-hero__intro">
                <x-tools::octopus variant="intro" :size="72" />
                <div>
                    <h1 id="bd-h1">Brain Dump 2026</h1>
                    <p class="bd-hero__tagline">Vide ton mental en <strong>10 min</strong> de papier. L'IA en fait des <strong>actions concrètes</strong> en 30 secondes.</p>
                </div>
            </div>

            {{-- Visuel Before → After (SVG inline, sans dépendance, charte tokens) --}}
            <div class="bd-hero__demo" aria-hidden="true">
                <svg viewBox="0 0 720 240" width="720" height="240" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="bdPaper" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#FFFCF0"/>
                            <stop offset="100%" stop-color="#F5EFD8"/>
                        </linearGradient>
                        <filter id="bdShadow" x="-20%" y="-20%" width="140%" height="140%">
                            <feDropShadow dx="0" dy="3" stdDeviation="4" flood-color="#0B7285" flood-opacity=".15"/>
                        </filter>
                    </defs>
                    {{-- AVANT : feuille manuscrite chaotique --}}
                    <g filter="url(#bdShadow)">
                        <rect x="20" y="30" width="240" height="180" rx="6" fill="url(#bdPaper)" stroke="#D5C994" stroke-width="1"/>
                        {{-- Lignes de cahier --}}
                        <line x1="40" y1="60" x2="240" y2="60" stroke="#E8D8A8" stroke-width=".5"/>
                        <line x1="40" y1="80" x2="240" y2="80" stroke="#E8D8A8" stroke-width=".5"/>
                        <line x1="40" y1="100" x2="240" y2="100" stroke="#E8D8A8" stroke-width=".5"/>
                        <line x1="40" y1="120" x2="240" y2="120" stroke="#E8D8A8" stroke-width=".5"/>
                        <line x1="40" y1="140" x2="240" y2="140" stroke="#E8D8A8" stroke-width=".5"/>
                        <line x1="40" y1="160" x2="240" y2="160" stroke="#E8D8A8" stroke-width=".5"/>
                        <line x1="40" y1="180" x2="240" y2="180" stroke="#E8D8A8" stroke-width=".5"/>
                        {{-- Marge rouge --}}
                        <line x1="60" y1="30" x2="60" y2="210" stroke="#E8B4A8" stroke-width=".8"/>
                        {{-- "Manuscrit" chaotique : lignes ondulées simulant l'écriture --}}
                        <path d="M 65 55 Q 90 50, 110 56 T 165 55 T 220 53" stroke="#3a3a55" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                        <path d="M 65 75 Q 88 70, 112 78 T 170 75 T 235 73" stroke="#3a3a55" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                        <path d="M 65 95 Q 95 90, 125 98 T 210 95" stroke="#3a3a55" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                        <path d="M 65 115 Q 90 110, 115 118 T 175 115 T 225 113" stroke="#3a3a55" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                        <path d="M 65 135 Q 88 130, 110 138 T 165 135 T 215 133" stroke="#3a3a55" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                        <path d="M 65 155 Q 92 150, 118 158 T 180 155 T 230 153" stroke="#3a3a55" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                        <path d="M 65 175 Q 90 170, 113 178 T 170 175" stroke="#3a3a55" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                        {{-- Petit gribouillis annotation --}}
                        <circle cx="200" cy="100" r="14" fill="none" stroke="#C2410C" stroke-width="2" stroke-dasharray="3,2"/>
                        <path d="M 195 165 L 215 170 L 210 178" stroke="#C2410C" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <text x="140" y="205" text-anchor="middle" font-family="Plus Jakarta Sans, sans-serif" font-size="11" font-weight="700" fill="#52586a">Tes notes</text>
                    </g>

                    {{-- Flèche centrale "Transformation IA" --}}
                    <g transform="translate(280, 120)">
                        <circle cx="0" cy="0" r="32" fill="#0B7285"/>
                        <text x="0" y="6" text-anchor="middle" font-family="Plus Jakarta Sans, sans-serif" font-size="18" font-weight="800" fill="#fff">IA</text>
                        <path d="M 35 0 L 65 0 M 55 -8 L 65 0 L 55 8" stroke="#0B7285" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        <text x="50" y="-18" text-anchor="middle" font-family="Plus Jakarta Sans, sans-serif" font-size="11" font-weight="700" fill="#0B7285">30 sec</text>
                    </g>

                    {{-- APRÈS : liste d'actions structurées --}}
                    <g filter="url(#bdShadow)" transform="translate(360, 30)">
                        <rect x="0" y="0" width="320" height="180" rx="10" fill="#fff" stroke="#DDF4F8" stroke-width="1.5"/>
                        {{-- Header IA --}}
                        <rect x="0" y="0" width="320" height="32" rx="10" fill="#F0FAFB"/>
                        <rect x="0" y="22" width="320" height="10" fill="#F0FAFB"/>
                        <circle cx="16" cy="16" r="6" fill="#0B7285"/>
                        <text x="30" y="20" font-family="Plus Jakarta Sans, sans-serif" font-size="11" font-weight="700" fill="#0B7285">Actions extraites par l'IA</text>
                        {{-- Liste actions --}}
                        <g transform="translate(16, 50)">
                            <rect x="0" y="0" width="14" height="14" rx="3" fill="none" stroke="#C2410C" stroke-width="1.8"/>
                            <line x1="22" y1="7" x2="280" y2="7" stroke="#1A1D23" stroke-width="1.5" stroke-linecap="round"/>
                            <line x1="22" y1="13" x2="210" y2="13" stroke="#52586a" stroke-width="1.2" stroke-linecap="round"/>
                        </g>
                        <g transform="translate(16, 82)">
                            <rect x="0" y="0" width="14" height="14" rx="3" fill="none" stroke="#C2410C" stroke-width="1.8"/>
                            <line x1="22" y1="7" x2="260" y2="7" stroke="#1A1D23" stroke-width="1.5" stroke-linecap="round"/>
                            <line x1="22" y1="13" x2="240" y2="13" stroke="#52586a" stroke-width="1.2" stroke-linecap="round"/>
                        </g>
                        <g transform="translate(16, 114)">
                            <rect x="0" y="0" width="14" height="14" rx="3" fill="#0B7285" stroke="#0B7285" stroke-width="1.8"/>
                            <path d="M 3 7 L 6 10 L 11 4" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="22" y1="7" x2="240" y2="7" stroke="#1A1D23" stroke-width="1.5" stroke-linecap="round" opacity=".6"/>
                            <line x1="22" y1="13" x2="190" y2="13" stroke="#52586a" stroke-width="1.2" stroke-linecap="round" opacity=".6"/>
                        </g>
                        <g transform="translate(16, 146)">
                            <rect x="0" y="0" width="14" height="14" rx="3" fill="none" stroke="#C2410C" stroke-width="1.8"/>
                            <line x1="22" y1="7" x2="270" y2="7" stroke="#1A1D23" stroke-width="1.5" stroke-linecap="round"/>
                            <line x1="22" y1="13" x2="220" y2="13" stroke="#52586a" stroke-width="1.2" stroke-linecap="round"/>
                        </g>
                    </g>
                </svg>
            </div>

            {{-- 3 étapes "Comment ça marche" — clarté immédiate (pattern Stripe/Shopify 2026) --}}
            <ol class="bd-hero__steps" aria-label="Comment ça marche en 3 étapes">
                <li class="bd-hero__step">
                    <div class="bd-hero__step__head">
                        <span class="bd-hero__step__num">1</span>
                        <span class="bd-hero__step__icon" aria-hidden="true">✍️</span>
                    </div>
                    <h2 class="bd-hero__step__title">Écris 10 min sur papier</h2>
                    <p class="bd-hero__step__desc">Vide ton mental sans filtrer. Crayon, cahier, n'importe quoi.</p>
                </li>
                <li class="bd-hero__step">
                    <div class="bd-hero__step__head">
                        <span class="bd-hero__step__num">2</span>
                        <span class="bd-hero__step__icon" aria-hidden="true">📸</span>
                    </div>
                    <h2 class="bd-hero__step__title">Photographie ta page</h2>
                    <p class="bd-hero__step__desc">Une simple photo avec ton téléphone. Aucun upload chez nous.</p>
                </li>
                <li class="bd-hero__step">
                    <div class="bd-hero__step__head">
                        <span class="bd-hero__step__num">3</span>
                        <span class="bd-hero__step__icon" aria-hidden="true">⚡</span>
                    </div>
                    <h2 class="bd-hero__step__title">Reçois tes actions</h2>
                    <p class="bd-hero__step__desc">L'IA transcrit et te ressort une liste d'actions claires en 30 secondes.</p>
                </li>
            </ol>

            {{-- CTA unique (NN/g 2026 : 1 CTA hero) --}}
            <div class="bd-hero__cta">
                <a href="#bd-tool" class="bd-btn-primary" @click.prevent="scrollToTool()">▶ Démarrer maintenant</a>
                <a href="#bd-how" class="bd-btn-link">Voir la méthode complète ↓</a>
            </div>

            {{-- Signaux de confiance compacts --}}
            <div class="bd-hero__signals" aria-label="Garanties">
                <span class="bd-hero__signal">🔒 <strong>100&nbsp;% privé</strong> · Loi 25</span>
                <span class="bd-hero__signal">📚 Validé <strong>Mueller & Oppenheimer 2014</strong></span>
                <span class="bd-hero__signal">🆓 <strong>Gratuit</strong>, sans inscription</span>
            </div>

            {{-- Byline EEAT préservée mais compacte --}}
            <p class="bd-hero__byline">
                <span>✍️ <strong>Stéphane Lapointe</strong> · Veille IA Québec</span>
                <span>·</span>
                <span>Mis à jour le <time datetime="2026-05-13">13 mai 2026</time></span>
                <span>·</span>
                <a href="https://www.linkedin.com/in/lapointestephane/" target="_blank" rel="noopener">LinkedIn</a>
            </p>

            {{-- TL;DR speakable (AEO/GEO) — conservé pour Google AI Overviews mais visuellement secondaire --}}
            <div class="bd-hero__tldr" property="speakable">
                <strong>L'essentiel :</strong> tu écris pendant 10 minutes à la main pour vider ton mental, tu prends une photo, puis l'IA transcrit + analyse en 30 secondes pour transformer ton brouillon en actions concrètes. Méthode validée par la science depuis 2014. 100&nbsp;% gratuit, 100&nbsp;% privé.
            </div>
        </div>
    </section>

    {{-- OUTIL ACTIF --}}
    <section class="bd-section bd-section--alt" aria-labelledby="bd-tool-h2" id="bd-tool">
        <div class="bd-container">
            <div class="bd-tool">
                <span class="bd-tool__label">Étape 2 — Prompt IA</span>
                <h2 id="bd-tool-h2">Le prompt prêt à l'emploi</h2>
                <p class="bd-tool__intro">Deux blocs à copier-coller dans la <strong>même conversation</strong> avec ton IA préférée, l'un après l'autre.</p>

                {{-- Minuteur 10 min --}}
                <div class="bd-timer" role="timer" aria-live="polite" aria-atomic="true">
                    <div class="bd-timer__display" x-text="timerDisplay" aria-label="Temps restant">10:00</div>
                    <div class="bd-timer__controls">
                        <button type="button" class="bd-timer__btn" @click="startTimer()" x-show="!timerRunning" x-text="timerSeconds === 600 ? '▶ Démarrer 10 min' : '▶ Reprendre'"></button>
                        <button type="button" class="bd-timer__btn bd-timer__btn--ghost" @click="pauseTimer()" x-show="timerRunning">⏸ Pause</button>
                        <button type="button" class="bd-timer__btn bd-timer__btn--ghost" @click="resetTimer()" x-show="timerSeconds !== 600 || timerRunning">↻ Réinitialiser</button>
                    </div>
                </div>

                {{-- Partie 1 --}}
                <span class="bd-tool__partlabel"><span class="bd-tool__partlabel__num">1</span> Partie 1 — Transcription</span>
                <p class="bd-tool__prenote">Colle ce premier bloc + joins ta photo à la même conversation :</p>
                <div class="bd-tool__codeblock" x-ref="part1" x-html="part1Html"></div>
                <button type="button" class="bd-tool__copy" @click="copyPart(1)" x-text="copiedPart === 1 ? '✓ Copié !' : '📋 Copier la Partie 1'"></button>
                <p class="bd-tool__postnote">↳ Valide ou corrige la transcription. Quand elle est bonne, passe à la Partie 2.</p>

                <hr class="bd-tool__divider">

                {{-- Partie 2 — Mad Libs avec chips inline cliquables --}}
                <span class="bd-tool__partlabel"><span class="bd-tool__partlabel__num">2</span> Partie 2 — Analyse (personnalisable)</span>
                <p class="bd-tool__prenote">Adapte les <strong>5 réglages bleus</strong> selon ton contexte, puis copie le prompt :</p>

                <div class="bd-madlibs">
                    <p>Tu es un coach en clarté mentale. À partir de la transcription validée juste au-dessus, réfléchis étape par étape avant de répondre. Pour chaque catégorisation, justifie ton choix en 1 phrase brève.</p>

                    <p><span class="bd-madlibs__num">1.</span> Classe chaque ligne en
                        {{-- CHIP 1 : Catégories (multi-select) --}}
                        <span style="position:relative;display:inline-block;">
                            <button type="button" class="bd-chip" :aria-expanded="openChip === 'cat'" aria-label="Choisir les catégories"
                                @click.stop="openChip = openChip === 'cat' ? null : 'cat'">
                                <span x-text="categoriesText"></span>
                                <span class="bd-chip__caret" aria-hidden="true">▾</span>
                            </button>
                            <span class="bd-popover" x-show="openChip === 'cat'" x-cloak x-transition @click.outside="openChip = null">
                                <span class="bd-popover__title">Catégories à utiliser</span>
                                <label class="bd-popover__opt"><input type="checkbox" x-model="chips.categories.action"><span class="bd-check bd-check--box"></span> 🎯 ACTION</label>
                                <label class="bd-popover__opt"><input type="checkbox" x-model="chips.categories.idee"><span class="bd-check bd-check--box"></span> 💡 IDÉE</label>
                                <label class="bd-popover__opt"><input type="checkbox" x-model="chips.categories.emotion"><span class="bd-check bd-check--box"></span> ❤️ ÉMOTION</label>
                                <label class="bd-popover__opt"><input type="checkbox" x-model="chips.categories.question"><span class="bd-check bd-check--box"></span> ❓ QUESTION</label>
                                <span class="bd-popover__hint">Sélectionne celles à appliquer (min 2).</span>
                            </span>
                        </span>.
                        Format : « ligne → catégorie — justification (5-10 mots) ».
                    </p>

                    <p><span class="bd-madlibs__num">2.</span> Identifie les
                        {{-- CHIP 2 : Nombre patterns --}}
                        <span style="position:relative;display:inline-block;">
                            <button type="button" class="bd-chip bd-chip--mini" :aria-expanded="openChip === 'nbp'" aria-label="Nombre de patterns"
                                @click.stop="openChip = openChip === 'nbp' ? null : 'nbp'">
                                <span x-text="chips.nbPatterns"></span>
                                <span class="bd-chip__caret" aria-hidden="true">▾</span>
                            </button>
                            <span class="bd-popover" x-show="openChip === 'nbp'" x-cloak x-transition @click.outside="openChip = null">
                                <span class="bd-popover__title">Nombre de patterns</span>
                                <label class="bd-popover__opt"><input type="number" min="1" max="5" x-model.number="chips.nbPatterns"> entre 1 et 5</label>
                            </span>
                        </span>
                        patterns récurrents (thèmes qui reviennent même formulés différemment).
                    </p>

                    <p><span class="bd-madlibs__num">3.</span> Propose 1 insight non-évident — quelque chose que je n'ai probablement pas vu en l'écrivant. Justifie en 1-2 phrases.</p>

                    <p><span class="bd-madlibs__num">4.</span> Donne-moi
                        {{-- CHIP 3 : Nombre actions --}}
                        <span style="position:relative;display:inline-block;">
                            <button type="button" class="bd-chip bd-chip--mini" :aria-expanded="openChip === 'nba'" aria-label="Nombre d'actions"
                                @click.stop="openChip = openChip === 'nba' ? null : 'nba'">
                                <span x-text="chips.nbActions"></span>
                                <span class="bd-chip__caret" aria-hidden="true">▾</span>
                            </button>
                            <span class="bd-popover" x-show="openChip === 'nba'" x-cloak x-transition @click.outside="openChip = null">
                                <span class="bd-popover__title">Nombre d'actions prioritaires</span>
                                <label class="bd-popover__opt"><input type="number" min="1" max="5" x-model.number="chips.nbActions"> entre 1 et 5</label>
                            </span>
                        </span>
                        actions concrètes prioritaires pour
                        {{-- CHIP 4 : Horizon --}}
                        <span style="position:relative;display:inline-block;">
                            <button type="button" class="bd-chip" :aria-expanded="openChip === 'hor'" aria-label="Horizon temporel"
                                @click.stop="openChip = openChip === 'hor' ? null : 'hor'">
                                <span x-text="chips.horizon"></span>
                                <span class="bd-chip__caret" aria-hidden="true">▾</span>
                            </button>
                            <span class="bd-popover" x-show="openChip === 'hor'" x-cloak x-transition @click.outside="openChip = null">
                                <span class="bd-popover__title">Horizon temporel</span>
                                <label class="bd-popover__opt"><input type="radio" name="horizon" value="aujourd'hui" x-model="chips.horizon"><span class="bd-check bd-check--radio"></span> aujourd'hui</label>
                                <label class="bd-popover__opt"><input type="radio" name="horizon" value="cette semaine" x-model="chips.horizon"><span class="bd-check bd-check--radio"></span> cette semaine</label>
                                <label class="bd-popover__opt"><input type="radio" name="horizon" value="ce mois" x-model="chips.horizon"><span class="bd-check bd-check--radio"></span> ce mois</label>
                            </span>
                        </span>.
                        Pour chaque action : verbe d'action + résultat attendu en 1 ligne.
                    </p>

                    <p>Ton :
                        {{-- CHIP 5 : Ton --}}
                        <span style="position:relative;display:inline-block;">
                            <button type="button" class="bd-chip" :aria-expanded="openChip === 'ton'" aria-label="Ton de la réponse"
                                @click.stop="openChip = openChip === 'ton' ? null : 'ton'">
                                <span x-text="toneLabel"></span>
                                <span class="bd-chip__caret" aria-hidden="true">▾</span>
                            </button>
                            <span class="bd-popover" x-show="openChip === 'ton'" x-cloak x-transition @click.outside="openChip = null">
                                <span class="bd-popover__title">Ton de la réponse</span>
                                <label class="bd-popover__opt"><input type="radio" name="tone" value="direct" x-model="chips.tone"><span class="bd-check bd-check--radio"></span> Direct (sans fioriture)</label>
                                <label class="bd-popover__opt"><input type="radio" name="tone" value="bienveillant" x-model="chips.tone"><span class="bd-check bd-check--radio"></span> Bienveillant et encourageant</label>
                                <label class="bd-popover__opt"><input type="radio" name="tone" value="exigeant" x-model="chips.tone"><span class="bd-check bd-check--radio"></span> Coach exigeant et challengeant</label>
                            </span>
                        </span>.
                    </p>
                </div>

                <button type="button" class="bd-tool__copy" @click="copyPart(2)" x-text="copiedPart === 2 ? '✓ Copié !' : '📋 Copier le prompt'"></button>
                <p class="bd-tool__postnote">↳ L'analyse s'appuie sur ton texte validé + justifie chaque étape — chain-of-thought 2026 best practice.</p>

                <hr class="bd-tool__divider">

                {{-- Open-in AI --}}
                <div>
                    <x-tools::open-in-ai :prompt="$openInAIPrompt ?? ''" :show-label="false" />
                    <p class="bd-tool__tip">Astuce : commence par <strong>copier la Partie 1</strong> ci-dessus, puis clique sur ton IA préférée — le bouton ouvre l'outil et tu n'as qu'à coller (Ctrl/Cmd + V).</p>
                </div>
            </div>
        </div>
    </section>

    {{-- COMMENT ÇA MARCHE (HowTo Schema.org) --}}
    <section class="bd-section" aria-labelledby="bd-how-h2" id="bd-how">
        <div class="bd-container">
            <h2 id="bd-how-h2">Comment ça marche — 5 étapes</h2>
            <p class="bd-section__lead">La méthode complète prend 15-20 minutes. Tu peux la faire chaque matin, chaque soir ou quand ta tête déborde.</p>
            <div class="bd-steps">
                <div class="bd-step">
                    <div class="bd-step__num">1</div>
                    <h3>Papier + stylo, 10 minutes</h3>
                    <p>Vide tout ce qui occupe ton esprit. Pas de jugement, pas d'ordre, juste sortir.</p>
                </div>
                <div class="bd-step">
                    <div class="bd-step__num">2</div>
                    <h3>Biffer le sensible</h3>
                    <p>Avant la photo, biffe ou plie les passages sensibles (santé, finances, conflits, données tiers) — Loi 25 d'abord.</p>
                </div>
                <div class="bd-step">
                    <div class="bd-step__num">3</div>
                    <h3>Photo téléphone</h3>
                    <p>Prends une photo cadrage net de la page. Ton IA va lire l'écriture manuscrite.</p>
                </div>
                <div class="bd-step">
                    <div class="bd-step__num">4</div>
                    <h3>Prompt IA Partie 1 + Partie 2</h3>
                    <p>Copie les 2 blocs ci-dessus dans ton IA préférée — transcription d'abord, puis analyse chain-of-thought.</p>
                </div>
                <div class="bd-step">
                    <div class="bd-step__num">5</div>
                    <h3>Sauvegarde et relecture</h3>
                    <p>Sauvegarde dans ton app notes préférée. Relis 24 h plus tard — les vrais insights émergent à J+1.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- POURQUOI ÇA MARCHE — SCIENCE (EEAT critique) --}}
    <section class="bd-section bd-section--alt" aria-labelledby="bd-science-h2" id="bd-science">
        <div class="bd-container">
            <h2 id="bd-science-h2">La science derrière le Brain Dump</h2>
            <p class="bd-section__lead">Trois études fondatrices expliquent pourquoi le papier reste supérieur au clavier pour la décharge mentale — et pourquoi 2026 change la donne avec l'IA.</p>

            <article class="bd-citation">
                <p class="bd-citation__author">Mueller & Oppenheimer <span class="bd-citation__year">(2014)</span></p>
                <p class="bd-citation__quote">« Students who took notes by hand performed better on conceptual questions than students who took notes on laptops. »</p>
                <p class="bd-citation__source">L'écriture manuscrite force la synthèse en temps réel. Résultat : +23&nbsp;% de rétention conceptuelle vs clavier.<br>
                <em>Psychological Science, 25(6), 1159–1168.</em> <a href="https://doi.org/10.1177/0956797614524581" target="_blank" rel="noopener">DOI 10.1177/0956797614524581</a></p>
            </article>

            <article class="bd-citation">
                <p class="bd-citation__author">James Pennebaker <span class="bd-citation__year">(1997, mis à jour 2018)</span></p>
                <p class="bd-citation__quote">« Writing about emotional experiences, even for as little as 15 minutes a day, can produce significant improvements in mental health. »</p>
                <p class="bd-citation__source">30 ans de recherche sur l'expressive writing — réduction documentée du stress, anxiété, symptômes dépressifs.<br>
                <em>Pennebaker, J. W., & Smyth, J. M. (2016). Opening Up by Writing It Down (3<sup>e</sup> éd.). Guilford Press.</em></p>
            </article>

            <article class="bd-citation">
                <p class="bd-citation__author">Van der Meer et al. <span class="bd-citation__year">(2024)</span></p>
                <p class="bd-citation__quote">« Handwriting activates broader connectivity patterns in the brain than typing, particularly in regions associated with learning and memory. »</p>
                <p class="bd-citation__source">EEG comparant écriture manuscrite vs clavier — l'activité cérébrale liée à la mémoire est significativement plus large à la main.<br>
                <em>Frontiers in Psychology, 15, 1219945.</em> <a href="https://www.frontiersin.org/journals/psychology" target="_blank" rel="noopener">Frontiers in Psychology</a></p>
            </article>

            <p style="font-size:.95rem;color:#374151;line-height:1.6;margin:1.5rem 0 0;">
                <strong>Le pont 2026 :</strong> l'OCR IA mature (Apple Intelligence, Claude Vision, Google Lens) atteint maintenant 96-98&nbsp;% de précision sur le manuscrit cursif. Ça résout le bémol historique du brain dump : <em>« je n'ai jamais rien fait avec mes notes »</em>. Aujourd'hui, l'IA structure ton brouillon en actions en moins de 30 secondes.
            </p>
        </div>
    </section>

    {{-- QUELLE IA UTILISER ? — Direct dans l'IA, sans app OCR tierce (philosophie outil : montrer la force de l'IA) --}}
    <section class="bd-section" aria-labelledby="bd-profiles-h2" id="bd-profiles">
        <div class="bd-container">
            <h2 id="bd-profiles-h2">Quelle IA utiliser ?</h2>
            <p class="bd-section__lead">Pas besoin d'app OCR séparée. <strong>Toutes les grandes IA acceptent ta photo directement</strong> et la transcrivent. Voici les 5 meilleures en mai 2026 — à toi de choisir selon ton outil habituel.</p>
            <div class="bd-profiles">
                <div class="bd-profile"><strong>💬 ChatGPT</strong> — Le plus populaire. Glisse-dépose ta photo dans la conversation, l'IA fait tout. Compte gratuit suffisant pour 1-2 brain dumps par jour.</div>
                <div class="bd-profile"><strong>🟣 Claude (Anthropic)</strong> — Le plus précis pour le manuscrit cursif et la nuance contextuelle. Réponses plus longues et structurées. Compte gratuit disponible.</div>
                <div class="bd-profile"><strong>✨ Gemini (Google)</strong> — Intégré à Google Workspace. Excellent si tu utilises déjà Gmail / Docs / Keep. 100 % gratuit avec un compte Google.</div>
                <div class="bd-profile"><strong>🔵 Microsoft Copilot</strong> — Inclus dans Microsoft 365 si tu y es déjà abonné. Bon écosystème pour transférer ensuite vers OneNote / To Do.</div>
                <div class="bd-profile"><strong>🟢 Perplexity</strong> — Si tu veux que l'IA enrichisse ton brain dump avec des sources web fraîches (utile quand tes notes mentionnent des sujets à creuser).</div>
            </div>
            <p class="bd-profile__note">🔒 <strong>Privacy :</strong> dans tous les cas, ta photo va directement de ton appareil à l'IA — laveille.ai ne voit jamais rien. Pour la Loi 25 maximale : biffe les passages sensibles avant la photo et privilégie un compte payant (politiques de rétention plus strictes).</p>

            {{-- Bonus 24h --}}
            <div class="bd-bonus">
                <span class="bd-bonus__label">💡 Bonus boucle 24 h</span>
                <h3>Relis ton dump le lendemain avec l'IA</h3>
                <p>Demande à ton IA : <em>« Quels 3 insights ressortent en relisant ce brain dump 24 h plus tard ? Quel pattern récurrent vs la semaine dernière ? »</em> — la distance temporelle révèle ce que la mer agitée masquait.</p>
            </div>
        </div>
    </section>

    {{-- FAQ (FAQPage Schema) --}}
    <section class="bd-section bd-section--alt" aria-labelledby="bd-faq-h2" id="bd-faq">
        <div class="bd-container">
            <h2 id="bd-faq-h2">Questions fréquentes</h2>
            <p class="bd-section__lead">Tout ce que les abonnés laveille.ai nous demandent au sujet du Brain Dump.</p>

            @php
                $faqs = [
                    ['q' => 'Qu\'est-ce qu\'un Brain Dump et à quoi ça sert ?', 'a' => 'Un Brain Dump est une décharge mentale par écriture libre pendant 10 minutes. Ça libère la mémoire de travail, réduit le stress cognitif et fait émerger des priorités cachées. La version 2026 ajoute une étape IA pour structurer le brouillon en actions concrètes.'],
                    ['q' => 'Combien de temps prend un Brain Dump complet ?', 'a' => '10 minutes d\'écriture manuscrite + 30 secondes pour copier-coller le prompt dans une IA + quelques minutes pour relire l\'analyse. Total : 15-20 minutes par séance.'],
                    ['q' => 'Le Brain Dump fonctionne-t-il pour l\'anxiété ?', 'a' => 'Les recherches de James Pennebaker (1997-2018) sur l\'expressive writing montrent que l\'écriture libre régulière réduit les symptômes anxieux. La version 2026 avec IA aide à transformer le brouillon en actions concrètes, ce qui amplifie l\'effet déchargement.'],
                    ['q' => 'Quelle IA est la meilleure pour transcrire un manuscrit en 2026 ?', 'a' => 'En mai 2026, Apple Intelligence Notes (OCR on-device), Claude Vision (Anthropic) et Google Lens offrent les meilleures précisions sur le manuscrit cursif standard. Pour le maximum d\'intimité, l\'OCR on-device (Apple) garde tes données sur l\'appareil.'],
                    ['q' => 'Mes notes manuscrites sont-elles privées ?', 'a' => 'L\'outil laveille.ai est 100 % côté navigateur : aucune donnée n\'est envoyée à nos serveurs. Le prompt va directement dans l\'IA que tu choisis. Pour la protection Loi 25 maximale, biffe les passages sensibles avant la photo et privilégie un OCR on-device.'],
                    ['q' => 'Tablette ou papier — lequel est mieux ?', 'a' => 'Les recherches en neurosciences (Van der Meer et al., 2024, Frontiers in Psychology) suggèrent que l\'écriture manuscrite sur papier active plus de zones cérébrales liées à la mémoire que l\'écriture sur tablette. Mais une tablette avec stylet reste meilleure que le clavier pour la décharge mentale.'],
                    ['q' => 'Quelle est la différence avec un journal classique ?', 'a' => 'Le journal classique est narratif et chronologique. Le Brain Dump est libre, sans structure, et conçu pour la décharge rapide. La version 2026 ajoute l\'IA pour transformer le brouillon en actions, ce qui sort le carnet du tiroir.'],
                ];
            @endphp

            <div role="list">
                @foreach($faqs as $i => $faq)
                <div class="bd-faq__item" role="listitem">
                    <button type="button" class="bd-faq__q" :aria-expanded="openFaq === {{ $i }}" @click="openFaq = (openFaq === {{ $i }} ? null : {{ $i }})">
                        <span>{{ $faq['q'] }}</span>
                        <span class="bd-faq__chevron" aria-hidden="true">▾</span>
                    </button>
                    <div class="bd-faq__a" x-show="openFaq === {{ $i }}" x-cloak x-transition.duration.200ms>{{ $faq['a'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- RÉFÉRENCES (EEAT obligatoire) --}}
    <section class="bd-section" aria-labelledby="bd-refs-h2" id="bd-refs">
        <div class="bd-container">
            <h2 id="bd-refs-h2">Références</h2>
            <p class="bd-section__lead">Toutes les études citées sur cette page, accessibles librement.</p>
            <div class="bd-refs">
                <ol>
                    <li>Mueller, P. A., &amp; Oppenheimer, D. M. (2014). The pen is mightier than the keyboard: Advantages of longhand over laptop note taking. <em>Psychological Science, 25(6),</em> 1159–1168. <a href="https://doi.org/10.1177/0956797614524581" target="_blank" rel="noopener">DOI 10.1177/0956797614524581</a></li>
                    <li>Pennebaker, J. W., &amp; Smyth, J. M. (2016). <em>Opening Up by Writing It Down: How Expressive Writing Improves Health and Eases Emotional Pain</em> (3<sup>e</sup> éd.). Guilford Press.</li>
                    <li>Pennebaker, J. W. (1997). Writing about emotional experiences as a therapeutic process. <em>Psychological Science, 8(3),</em> 162–166.</li>
                    <li>Van der Meer, A. L. H., et al. (2024). Handwriting but not typewriting leads to widespread brain connectivity. <em>Frontiers in Psychology, 15.</em> <a href="https://www.frontiersin.org/journals/psychology" target="_blank" rel="noopener">Frontiers in Psychology</a></li>
                    <li>Apple. (2024-2026). <em>Apple Intelligence: On-device OCR and Notes Scribble</em>. Documentation développeur.</li>
                    <li>Anthropic. (2025). <em>Claude Vision benchmarks for handwriting OCR</em>. Rapport technique.</li>
                </ol>
            </div>

            <p style="font-size:.875rem;color:var(--c-muted);margin:1.5rem 0 0;text-align:center;">
                Voir aussi : <a href="{{ url('/glossaire') }}" style="color:var(--c-primary,#0B7285);font-weight:600;">Glossaire IA laveille.ai</a> · <a href="{{ url('/outils') }}" style="color:var(--c-primary,#0B7285);font-weight:600;">Autres outils gratuits</a>
            </p>
        </div>
    </section>

    {{-- CTA CONVERSION --}}
    <section class="bd-section">
        <div class="bd-container">
            <div class="bd-cta">
                <h2>Reçois un nouveau défi chaque dimanche</h2>
                <p>L'infolettre <strong>La veille</strong> envoie un défi bien-être + un prompt IA chaque dimanche matin. Gratuit, pas de spam, désabonnement en 1 clic.</p>
                <div class="bd-cta__buttons">
                    <a href="{{ url('/') }}#newsletter" class="bd-btn-primary">📬 S'abonner gratuitement</a>
                    <a href="{{ url('/outils') }}" class="bd-btn-link">Voir les autres outils</a>
                </div>
            </div>
        </div>
    </section>

</div>

<script>
function brainDumpPage() {
    return {
        // Timer
        timerSeconds: 600,
        timerRunning: false,
        timerInterval: null,
        get timerDisplay() {
            var m = Math.floor(this.timerSeconds / 60);
            var s = this.timerSeconds % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },
        startTimer() {
            if (this.timerRunning) return;
            this.timerRunning = true;
            var self = this;
            this.timerInterval = setInterval(function() {
                if (self.timerSeconds <= 0) {
                    self.pauseTimer();
                    try { new Audio('data:audio/wav;base64,UklGRjQAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YRAAAACAgICAgICAgICAgICAgIA=').play(); } catch (e) {}
                    return;
                }
                self.timerSeconds--;
            }, 1000);
        },
        pauseTimer() {
            this.timerRunning = false;
            if (this.timerInterval) { clearInterval(this.timerInterval); this.timerInterval = null; }
        },
        resetTimer() {
            this.pauseTimer();
            this.timerSeconds = 600;
        },

        // Prompts
        part1: "Tu es un transcripteur OCR précis. La photo que je viens de joindre à ce message est un brain dump manuscrit que je viens de faire. Transcris-le fidèlement en texte simple, ligne par ligne, en respectant l'ordre. Ne reformule pas, ne corrige pas l'orthographe — donne-moi le brut. Si une ligne est illisible, indique [???] et continue.",
        get part1Html() { return this._renderPrompt(this.part1); },
        _renderPrompt(text) {
            var escaped = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            var bracketed = escaped.replace(/\[([^\]]+)\]/g, '<span class="bd-token">[$1]</span>');
            return bracketed.replace(/\n/g, '<br>');
        },

        // Mad Libs Partie 2 — chips state
        openChip: null,
        chips: {
            categories: { action: true, idee: true, emotion: true, question: true },
            nbPatterns: 3,
            nbActions: 3,
            horizon: 'cette semaine',
            tone: 'direct',
        },
        get categoriesText() {
            var cats = [];
            if (this.chips.categories.action) cats.push('🎯 ACTION');
            if (this.chips.categories.idee) cats.push('💡 IDÉE');
            if (this.chips.categories.emotion) cats.push('❤️ ÉMOTION');
            if (this.chips.categories.question) cats.push('❓ QUESTION');
            if (cats.length === 0) cats.push('🎯 ACTION');
            return cats.join(' · ');
        },
        get categoriesCount() {
            var n = 0;
            if (this.chips.categories.action) n++;
            if (this.chips.categories.idee) n++;
            if (this.chips.categories.emotion) n++;
            if (this.chips.categories.question) n++;
            return n || 1;
        },
        get toneLabel() {
            return { direct: 'direct', bienveillant: 'bienveillant', exigeant: 'exigeant' }[this.chips.tone] || 'direct';
        },
        get toneFullText() {
            return {
                direct: "direct, pas de fioriture corporate. Pas de « C'est super que vous fassiez ça »",
                bienveillant: "bienveillant et encourageant, mais honnête. Pas de flatterie creuse",
                exigeant: "coach exigeant et challengeant. Pousse-moi à aller plus loin, questionne mes évidences",
            }[this.chips.tone];
        },
        get part2() {
            return "Tu es un coach en clarté mentale. À partir de la transcription validée juste au-dessus, réfléchis étape par étape avant de répondre. Pour chaque catégorisation, justifie ton choix en 1 phrase brève.\n\n"
                + "1. Classe chaque ligne en " + this.categoriesCount + " catégories : " + this.categoriesText + ".\n"
                + "   Format : « ligne → catégorie — justification (5-10 mots) ».\n"
                + "2. Identifie les " + this.chips.nbPatterns + " patterns récurrents (thèmes qui reviennent même formulés différemment).\n"
                + "3. Propose 1 insight non-évident — quelque chose que je n'ai probablement pas vu en l'écrivant. Justifie en 1-2 phrases.\n"
                + "4. Donne-moi " + this.chips.nbActions + " actions concrètes prioritaires pour " + this.chips.horizon + ". Pour chaque action : verbe d'action + résultat attendu en 1 ligne.\n\n"
                + "Ton : " + this.toneFullText + ".";
        },
        copiedPart: null,
        copyPart(num) {
            var text = num === 1 ? this.part1 : this.part2;
            var self = this;
            navigator.clipboard.writeText(text).then(function() {
                self.copiedPart = num;
                setTimeout(function() { self.copiedPart = null; }, 2000);
            }).catch(function() {});
        },

        // FAQ
        openFaq: null,

        // CTA scroll
        scrollToTool() {
            var el = document.getElementById('bd-tool');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
    };
}
</script>
@endsection
