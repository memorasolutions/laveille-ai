@extends('fronttheme::layouts.master')

@php
    $ucMode = $tool->construction_mode ?? 'construction';
    $ucTitle = match ($ucMode) {
        'maintenance' => __(':name : outil en cours d\'amélioration · La veille de Stef', ['name' => $tool->name]),
        'revision' => __(':name fait peau neuve · La veille de Stef', ['name' => $tool->name]),
        default => __(':name : en construction · La veille de Stef', ['name' => $tool->name]),
    };
    $ucMeta = match ($ucMode) {
        'maintenance' => __(':name est en pause le temps d\'une mise à jour. De retour très bientôt.', ['name' => $tool->name]),
        'revision' => __(':name est temporairement hors ligne le temps d\'une mise à jour importante. Vos prompts sauvegardés sont intacts.', ['name' => $tool->name]),
        default => __(':name est en construction. Nous travaillons activement à son lancement public sur laveille.ai.', ['name' => $tool->name]),
    };
@endphp
@section('title', $ucTitle)
@section('meta_description', $ucMeta)

{{-- Round 136 (2026-07-30, passe adversariale) : cette page répond 200 OK alors que l'outil est
     inaccessible au public. Sans cette section, le layout retombe sur « index, follow » et les
     moteurs indexent une page que personne ne peut utiliser. Les deux autres modules qui gatent un
     outil (Decido, Books) posent déjà cette section : c'est le patron du projet, ce module l'avait
     simplement manqué. Vaut pour les 2 modes historiques - construction comme révision.

     Mode "maintenance" (2026-09-19) EXCLU de ce noindex : cette page répond ici 503 + Retry-After
     (PublicToolController::show()), pas 200. Combiner noindex à un 503 ferait sortir de l'index
     une page déjà classée qu'on veut garder - le 503 + Retry-After EST le signal correct, il se
     suffit à lui-même (recommandation Google sur les indisponibilités temporaires).

     ⚠️ Ce @unless NE SUFFIT PAS À LUI SEUL - mesuré en direct le 2026-09-19 sur la page réellement
     servie (coordinateur) : le layout partagé (fronttheme::layouts.master) pose SA PROPRE balise
     <meta name="robots" content="noindex, nofollow"> dès que config('app.noindex') est vrai
     (drapeau SITE ENTIER, ex. APP_NOINDEX=true en local), et cette branche est vérifiée AVANT
     page_noindex - elle gagne toujours, quel que soit cet @unless. La garantie réelle pour le
     mode maintenance vit donc dans PublicToolController::show() (strip/remplacement de toute
     balise noindex sur la réponse 503, quelle qu'en soit la source), pas ici. Ce @unless reste
     néanmoins correct et utile : il évite de poser une 2e couche de noindex inutile. --}}
@unless($ucMode === 'maintenance')
@section('page_noindex', true)
@endunless

@section('content')
@if($ucMode === 'maintenance')
<section class="lv-under-construction" aria-labelledby="uc-title">
    <div class="lv-uc__card" role="region" aria-label="{{ __('Outil en cours d\'amélioration') }}">
        <div class="lv-uc__mascot">
            <x-tools::octopus variant="sleeping" size="160" />
        </div>

        <p class="lv-uc__badge">
            <span aria-hidden="true">🔧</span>
            <span>{{ __('Mise à jour en cours') }}</span>
        </p>

        <h1 id="uc-title" class="lv-uc__title">{{ __('Le :name est en pause pour une mise à jour', ['name' => $tool->name]) }}</h1>

        <p class="lv-uc__lead">
            {{ __("Nous améliorons cet outil en ce moment même. Il revient très bientôt - en attendant, découvre nos autres outils gratuits.") }}
        </p>

        @if(($suggestions ?? collect())->isNotEmpty())
        <div class="lv-uc__suggestions" aria-label="{{ __('Autres outils à essayer') }}">
            @foreach($suggestions as $suggestion)
            <a href="{{ route('tools.show', $suggestion->slug) }}" class="lv-uc__suggestion-chip">
                <span aria-hidden="true">{{ $suggestion->icon ?: '🛠️' }}</span>
                <span>{{ $suggestion->name }}</span>
            </a>
            @endforeach
        </div>
        @endif

        <div class="lv-uc__actions">
            <a href="{{ route('tools.index') }}"
               class="lv-uc__btn lv-uc__btn--primary"
               aria-label="{{ __('Voir tous les outils disponibles') }}">
                {{ __('Voir tous les outils') }}
            </a>
        </div>
    </div>
</section>
@elseif($ucMode === 'revision')
<section class="lv-under-construction lv-under-construction--revision" aria-labelledby="uc-title">
    <div class="lv-uc__card" role="region" aria-label="{{ __('Outil temporairement hors ligne pour mise à jour') }}">
        <div class="lv-uc__mascot">
            <x-tools::octopus variant="confident" size="160" />
        </div>

        <p class="lv-uc__badge">
            <span aria-hidden="true">✨</span>
            <span>{{ __('Mise à jour en cours') }}</span>
        </p>

        <h1 id="uc-title" class="lv-uc__title">{{ __('Le :name fait peau neuve', ['name' => $tool->name]) }}</h1>

        <p class="lv-uc__lead">
            {{ __("Le :name est temporairement hors ligne, le temps d'une mise à jour importante. Vos commentaires ont mis en lumière plusieurs irritants - nous retravaillons les réglages avancés, la bibliothèque de prompts et la compatibilité avec les derniers formats (ChatGPT Canvas, Claude Artifacts).", ['name' => $tool->name]) }}
        </p>

        <div class="lv-uc__reassurance" role="note">
            <span class="lv-uc__reassurance-icon" aria-hidden="true">💾</span>
            <span><strong>{{ __('Vos prompts déjà sauvegardés sont intacts et vous seront accessibles dès le retour de l\'outil.') }}</strong></span>
        </div>

        <div class="lv-uc__actions">
            <a href="{{ route('tools.index') }}"
               class="lv-uc__btn lv-uc__btn--primary"
               aria-label="{{ __('Découvrir nos autres outils') }}">
                {{ __('Découvrir nos autres outils') }}
            </a>
        </div>
    </div>
</section>
@else
<section class="lv-under-construction" aria-labelledby="uc-title">
    <div class="lv-uc__card" role="region" aria-label="{{ __('Outil en construction') }}">
        <div class="lv-uc__mascot">
            <x-tools::octopus variant="thinking" size="160" />
        </div>

        <p class="lv-uc__badge">
            <span aria-hidden="true">🚧</span>
            <span>{{ __('En construction') }}</span>
        </p>

        <h1 id="uc-title" class="lv-uc__title">{{ $tool->name }}</h1>

        <p class="lv-uc__lead">
            {{ __('Cet outil est en construction. Nous travaillons activement à son lancement public.') }}
        </p>

        <div class="lv-uc__timeline" aria-label="{{ __('Étapes du développement') }}">
            <h2 class="lv-uc__timeline-title">{{ __('Avancement prévu') }}</h2>
            <ul class="lv-uc__steps">
                <li class="lv-uc__step lv-uc__step--done">
                    <span class="lv-uc__step-icon" aria-hidden="true">✓</span>
                    <span class="lv-uc__step-label"><strong>{{ __('Conception') }}</strong> : {{ __('Terminée') }}</span>
                </li>
                <li class="lv-uc__step lv-uc__step--current">
                    <span class="lv-uc__step-icon" aria-hidden="true">🚧</span>
                    <span class="lv-uc__step-label"><strong>{{ __('Développement') }}</strong> : {{ __('En cours') }}</span>
                </li>
                <li class="lv-uc__step lv-uc__step--upcoming">
                    <span class="lv-uc__step-icon" aria-hidden="true">🎯</span>
                    <span class="lv-uc__step-label"><strong>{{ __('Lancement public') }}</strong> : {{ __('À venir') }}</span>
                </li>
            </ul>
        </div>

        <div class="lv-uc__actions">
            <a href="{{ route('tools.index') }}"
               class="lv-uc__btn lv-uc__btn--primary"
               aria-label="{{ __('Voir tous les outils disponibles') }}">
                {{ __('Voir tous les outils disponibles') }}
            </a>
            <a href="{{ route('tools.index') }}"
               class="lv-uc__btn lv-uc__btn--ghost"
               aria-label="{{ __('Retour aux outils') }}">
                ← {{ __('Retour aux outils') }}
            </a>
        </div>
    </div>
</section>
@endif

<style>
.lv-under-construction {
    --uc-primary: #064E5C;
    --uc-primary-hover: #053E4A;
    --uc-accent: #7C2D12;
    --uc-dark: #1A1D23;
    --uc-bg: #F0FDFA;
    --uc-card-bg: #FFFFFF;
    --uc-border: rgba(11, 114, 133, 0.18);
    --uc-radius: 10px;
    background: var(--uc-bg);
    min-height: 70vh;
    padding: 4rem 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}
.lv-uc__card {
    max-width: 720px;
    width: 100%;
    background: var(--uc-card-bg);
    border: 1px solid var(--uc-border);
    border-radius: var(--uc-radius);
    padding: 2.5rem 1.75rem;
    text-align: center;
    box-shadow: 0 10px 35px rgba(11, 114, 133, 0.08);
}
@media (min-width: 768px) { .lv-uc__card { padding: 3.25rem 2.5rem; } }
.lv-uc__mascot { display: flex; justify-content: center; margin-bottom: 1.25rem; }
.lv-uc__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.95rem;
    background: #FFF2EC;
    color: var(--uc-accent);
    border: 1px solid #FECABF;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin: 0 0 1.25rem;
}
.lv-uc__title {
    font-size: clamp(1.8rem, 4vw, 2.4rem);
    font-weight: 700;
    color: var(--uc-dark);
    margin: 0 0 0.85rem;
    line-height: 1.2;
}
.lv-uc__lead {
    font-size: 1.075rem;
    color: var(--uc-dark);
    max-width: 56ch;
    margin: 0 auto 2rem;
    line-height: 1.55;
}
.lv-uc__timeline {
    background: #E6F7F5;
    border: 1px solid var(--uc-border);
    border-radius: var(--uc-radius);
    padding: 1.25rem 1.5rem;
    margin: 0 0 2rem;
    text-align: left;
}
.lv-uc__timeline-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--uc-primary);
    margin: 0 0 0.85rem;
}
.lv-uc__steps { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.65rem; }
.lv-uc__step { display: flex; align-items: center; gap: 0.75rem; color: var(--uc-dark); font-size: 0.975rem; line-height: 1.4; }
.lv-uc__step-icon {
    flex-shrink: 0;
    width: 28px; height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 700;
}
.lv-uc__step--done .lv-uc__step-icon { background: var(--uc-primary); color: #FFFFFF; }
.lv-uc__step--current .lv-uc__step-icon { background: #7C2D12; color: #FFFFFF; }
.lv-uc__step--upcoming .lv-uc__step-icon { background: transparent; color: var(--uc-primary); border: 2px solid var(--uc-primary); }
.lv-uc__step--upcoming { opacity: 0.72; }
.lv-uc__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.85rem;
}
.lv-uc__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.85rem 1.5rem;
    border-radius: var(--uc-radius);
    font-weight: 600;
    text-decoration: none;
    transition: background-color 0.18s, transform 0.18s;
    min-height: 44px;
    min-width: 200px;
}
.lv-uc__btn--primary { background: var(--uc-primary); color: #FFFFFF; border: 2px solid var(--uc-primary); }
.lv-uc__btn--primary:hover, .lv-uc__btn--primary:focus { background: var(--uc-primary-hover); border-color: var(--uc-primary-hover); }
.lv-uc__btn--ghost { background: transparent; color: var(--uc-primary); border: 2px solid var(--uc-primary); }
.lv-uc__btn--ghost:hover, .lv-uc__btn--ghost:focus { background: #E6F7F5; }
.lv-uc__btn:focus-visible { outline: 3px solid var(--uc-accent); outline-offset: 3px; }
@media (prefers-reduced-motion: reduce) { .lv-uc__btn { transition: none; } }

/* #325 : mode "revision" (outil retiré temporairement, jamais "construction" neuf) — indigo/ambre,
   zéro rouge, aucune iconographie d'erreur. Réutilise les MÊMES classes .lv-uc__* que le mode
   "construction" (DRY) : seules les variables CSS scopées changent + 2 règles ciblées (badge/reassurance). */
.lv-under-construction--revision {
    --uc-primary: #4338CA;
    --uc-primary-hover: #3730A3;
    --uc-accent: #78350F;
    --uc-dark: #1A1D23;
    --uc-bg: #EEF2FF;
    --uc-card-bg: #FFFFFF;
    --uc-border: rgba(67, 56, 202, 0.18);
}
.lv-under-construction--revision .lv-uc__badge {
    background: #FEF3C7;
    border-color: #FDE68A;
}
.lv-uc__reassurance {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    border-radius: var(--uc-radius);
    padding: 1rem 1.25rem;
    margin: 0 auto 2rem;
    max-width: 56ch;
    text-align: left;
    color: #78350F;
    font-size: 1rem;
    line-height: 1.5;
}
.lv-uc__reassurance-icon { flex-shrink: 0; font-size: 1.25rem; line-height: 1; }

/* Mode "maintenance" (2026-09-19) : suggestions vers d'autres outils actifs, pour que le
   visiteur ne reparte pas les mains vides. Réutilise la palette --uc-* par défaut (DRY). */
.lv-uc__suggestions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.6rem;
    margin: 0 0 2rem;
}
.lv-uc__suggestion-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1rem;
    min-height: 44px;
    border-radius: 999px;
    background: #E6F7F5;
    border: 1px solid var(--uc-border);
    color: var(--uc-primary);
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
}
.lv-uc__suggestion-chip:hover { background: #D5F0EC; }
.lv-uc__suggestion-chip:focus-visible { outline: 3px solid var(--uc-accent); outline-offset: 2px; }
</style>
@endsection
