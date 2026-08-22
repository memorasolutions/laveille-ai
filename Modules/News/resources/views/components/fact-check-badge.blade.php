{{--
    Badge de vérification - module « vérification » (2026-08-21, demande fondateur).

    Rend le verdict d'une fiche qui démonte une affirmation circulant ailleurs : contenu généré
    par une IA, citation inexacte, attribution erronée, présentation trompeuse, contexte
    manquant. Ne rend RIEN sur une fiche ordinaire : le module est strictement additif, une fiche
    sans verdict est exactement ce qu'elle était avant.

    Tout le vocabulaire (libellé, teinte, phrase explicative) vient de
    NewsArticle::FACT_CHECK_VERDICTS via factCheckVerdict() - jamais réécrit ici (DRY strict).
    Ajouter un verdict au modèle suffit : cette vue n'a pas à changer.

    Deux formats, un seul composant (paramétré, jamais dupliqué) :
      compact=false (défaut) - bloc complet pour la page d'une fiche : verdict, affirmation
                               examinée, et lien vers la source d'origine.
      compact=true           - pastille seule, pour une liste ou une carte.

    Cadrage éditorial : le badge qualifie TOUJOURS l'affirmation, jamais la personne qui l'a
    relayée. C'est autant une question de justesse que de prudence juridique.

    Props :
        article  (\Modules\News\Models\NewsArticle) requis
        compact  (bool) facultatif, défaut false

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
@props(['article', 'compact' => false])

@php
    // Garde volontaire : la carte d'actualité qui appelle ce composant est réutilisée ailleurs
    // (fiche d'outil de l'annuaire, par exemple). Un objet sans cette méthode ne doit jamais
    // faire tomber une page entière pour un badge décoratif - même précaution que
    // JsonLdService::claimReview().
    $verdict = method_exists($article, 'factCheckVerdict') ? $article->factCheckVerdict() : null;

    // Défense en profondeur : la porte d'écriture refuse déjà tout ce qui n'est pas http(s),
    // mais une donnée plus ancienne ou écrite autrement ne doit jamais produire un href
    // exécutable (javascript:, data:). Un lien qui ne passe pas ce filtre n'est pas affiché.
    $nwFcSource = null;
    if ($verdict && filled($article->fact_check_source)) {
        $nwFcSchema = mb_strtolower((string) parse_url($article->fact_check_source, PHP_URL_SCHEME));
        $nwFcSource = in_array($nwFcSchema, ['http', 'https'], true) ? $article->fact_check_source : null;
    }
@endphp

@if($verdict)
    @once
    @push('styles')
    <style>
        /* Badge de vérification - reprend les jetons de la charte (aucune couleur en dur hors
           repli), et le motif visuel de .nw-editorial-signature pour rester dans la même
           famille : bordure gauche, fond très légèrement teinté, contraste AAA.
           Les deux teintes ont été MESURÉES sur le fond réel du badge, pas choisies à l'oeil :
           #9B1F1F donne 7,47:1 et #6E4700 donne 7,64:1, là où les valeurs plus vives d'abord
           retenues (#A32222 et #8A5A00) plafonnaient à 6,94:1 et 5,53:1 - sous le seuil AAA de
           la charte. Toute retouche de ces deux valeurs se remesure avant d'être posée. */
        .nw-factcheck {
            display: flex; align-items: flex-start; gap: 0.75rem;
            margin: 0.75rem 0 1.25rem;
            padding: 0.875rem 1rem;
            border-left: 4px solid var(--nw-fc-accent);
            background: var(--nw-fc-bg);
            border-radius: 0.375rem;
            font-size: 0.9375rem; line-height: 1.55;
            color: var(--c-text-secondary, #4a4f5c);
        }
        .nw-factcheck--danger  { --nw-fc-accent: #9B1F1F; --nw-fc-bg: rgba(155, 31, 31, 0.05); }
        .nw-factcheck--warning { --nw-fc-accent: #6E4700; --nw-fc-bg: rgba(110, 71, 0, 0.05); }
        .nw-factcheck__icon { flex-shrink: 0; width: 20px; height: 20px; margin-top: 0.125rem; color: var(--nw-fc-accent); }
        .nw-factcheck__label { display: block; font-weight: 700; color: var(--nw-fc-accent); letter-spacing: 0.01em; }
        .nw-factcheck__summary { display: block; margin-top: 0.125rem; }
        .nw-factcheck__claim {
            display: block; margin-top: 0.5rem; padding-left: 0.75rem;
            border-left: 2px solid rgba(0, 0, 0, 0.12);
            font-style: italic; color: var(--c-text-secondary, #4a4f5c);
        }
        .nw-factcheck__source { display: inline-block; margin-top: 0.5rem; font-size: 0.875rem; }
        /* Masquage visuel SANS masquage pour les technologies d'assistance. Défini ici plutôt
           que repris d'une classe globale : le composant reste autonome, où qu'il soit rendu. */
        .nw-factcheck__sr {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }
        .nw-factcheck__source a { color: var(--c-primary, #064E5A); font-weight: 600; text-decoration: none; }
        .nw-factcheck__source a:hover, .nw-factcheck__source a:focus-visible { text-decoration: underline; }

        /* Format compact : une pastille, pour les listes et les cartes. */
        .nw-factcheck-pill {
            display: inline-flex; align-items: center; gap: 0.375rem;
            padding: 0.1875rem 0.5rem;
            border: 1px solid var(--nw-fc-accent);
            border-radius: 999px;
            font-size: 0.75rem; font-weight: 700; line-height: 1.4;
            color: var(--nw-fc-accent); background: var(--nw-fc-bg);
            white-space: nowrap;
        }
        .nw-factcheck-pill--danger  { --nw-fc-accent: #9B1F1F; --nw-fc-bg: rgba(155, 31, 31, 0.06); }
        .nw-factcheck-pill--warning { --nw-fc-accent: #6E4700; --nw-fc-bg: rgba(110, 71, 0, 0.06); }
        .nw-factcheck-pill__dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    </style>
    @endpush
    @endonce

    @if($compact)
        <span class="nw-factcheck-pill nw-factcheck-pill--{{ $verdict['tone'] }}">
            <span class="nw-factcheck-pill__dot" aria-hidden="true"></span>
            {{ __('Vérification') }} : {{ __($verdict['label']) }}
        </span>
    @else
        <div class="nw-factcheck nw-factcheck--{{ $verdict['tone'] }}" role="note">
            <svg class="nw-factcheck__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/>
                <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M11 8v3.5M11 14.2v.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <span>
                <strong class="nw-factcheck__label">{{ __('Vérification') }} : {{ __($verdict['label']) }}</strong>
                <span class="nw-factcheck__summary">{{ __($verdict['summary']) }}</span>

                @if(filled($article->fact_check_claim))
                    <span class="nw-factcheck__claim">« {{ $article->fact_check_claim }} »</span>
                @endif

                @if($nwFcSource)
                    <span class="nw-factcheck__source">
                        {{-- L'ouverture dans un nouvel onglet est ANNONCÉE dans le nom accessible
                             (WCAG 3.2.5) : un changement de contexte non signalé désoriente un
                             lecteur d'écran, et la charte du site vise le niveau AAA. --}}
                        <a href="{{ $nwFcSource }}" target="_blank" rel="noopener nofollow ugc">{{ __('Voir la publication d\'origine') }} <span class="nw-factcheck__sr">{{ __('(ouvre un nouvel onglet)') }}</span> &rarr;</a>
                    </span>
                @endif
            </span>
        </div>
    @endif
@endif
