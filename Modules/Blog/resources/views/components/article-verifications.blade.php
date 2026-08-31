{{--
    Liste des vérifications d'un article de blogue - module « vérification » étendu au blogue
    (2026-08-31, demande fondateur : « aussi avoir des tags qui disent si on contredit une
    nouvelle qui circule sur internet »).

    Strictement ADDITIF : un article sans vérification ne rend RIEN, ce composant ne s'affiche
    pas, la page reste exactement ce qu'elle était avant.

    Décision de structure du 2026-08-31 (panel, unanime) : une LISTE de vérifications, jamais un
    verdict global sur l'article - chaque entrée garde son propre verdict, sa propre affirmation,
    sa propre preuve. Ce composant ne fait qu'ITÉRER et déléguer le rendu de chaque entrée au
    composant partagé <x-news::fact-check-badge>, DRY strict : le rendu du verdict lui-même
    (libellé, teinte, contraste AAA mesuré, sécurité du lien source) n'est jamais dupliqué ici.
    Ce fichier n'ajoute que ce qui est PROPRE à une entrée de blogue et absent du badge partagé :
    le motif détaillé du cas et les sources probantes (notre preuve, pluriel, distincte de
    l'origine de l'affirmation déjà rendue par le badge).

    Props :
        article  (\Modules\Blog\Models\Article) requis

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
@props(['article'])

@php
    $bvVerifications = $article->relationLoaded('verifications')
        ? $article->verifications
        : $article->verifications()->get();
@endphp

@if($bvVerifications->isNotEmpty())
    @once
    @push('styles')
    <style>
        .bl-verifications { margin: 1.75rem 0; }
        .bl-verifications__heading {
            font-size: 1.125rem; font-weight: 700; margin: 0 0 0.75rem;
            color: var(--c-text-primary, #1a1a1a);
        }
        /* Bloc « extra » propre au blogue (motif, sources probantes) - vient TOUJOURS après le
           badge partagé, jamais avant : le verdict et l'affirmation examinée priment. */
        .bl-verifications__extra {
            margin: -0.5rem 0 1.5rem; padding: 0 1rem 0 1.25rem;
            font-size: 0.875rem; line-height: 1.55; color: var(--c-text-secondary, #4a4f5c);
        }
        .bl-verifications__motif { margin: 0 0 0.5rem; }
        .bl-verifications__sources-label { font-weight: 600; }
        .bl-verifications__sources { margin: 0.25rem 0 0; padding-left: 1.25rem; }
        .bl-verifications__sources li { margin-bottom: 0.25rem; word-break: break-word; }
        .bl-verifications__sources a { color: var(--c-primary, #064E5A); font-weight: 600; text-decoration: none; }
        .bl-verifications__sources a:hover, .bl-verifications__sources a:focus-visible { text-decoration: underline; }
        /* Masquage visuel SANS masquage pour les technologies d'assistance - même motif que le
           badge partagé, redéfini ici en propre plutôt que d'exiger l'ordre de rendu d'un autre
           composant pour exister (composant autonome, où qu'il soit rendu). */
        .bl-verifications__sr {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
        }
    </style>
    @endpush
    @endonce

    <section class="bl-verifications" aria-label="{{ __('Vérifications de cet article') }}">
        @if($bvVerifications->count() > 1)
            <h2 class="bl-verifications__heading">{{ __('Vérifications') }}</h2>
        @endif

        @foreach($bvVerifications as $bvVerification)
            <x-news::fact-check-badge :article="$bvVerification" />

            @php
                // Même défense en profondeur que le composant partagé : une source qui ne passe
                // pas le filtre http(s) n'est pas affichée, jamais un href javascript:/data:.
                $bvSafeSources = collect($bvVerification->sources ?? [])->filter(function ($bvSource) {
                    $bvScheme = is_string($bvSource) ? mb_strtolower((string) parse_url($bvSource, PHP_URL_SCHEME)) : '';

                    return in_array($bvScheme, ['http', 'https'], true);
                });
            @endphp

            @if(filled($bvVerification->motif) || $bvSafeSources->isNotEmpty())
                <div class="bl-verifications__extra">
                    @if(filled($bvVerification->motif))
                        <p class="bl-verifications__motif">{{ $bvVerification->motif }}</p>
                    @endif

                    @if($bvSafeSources->isNotEmpty())
                        <span class="bl-verifications__sources-label">{{ __('Sources probantes') }}</span>
                        <ul class="bl-verifications__sources">
                            @foreach($bvSafeSources as $bvSource)
                                <li>
                                    <a href="{{ $bvSource }}" target="_blank" rel="noopener nofollow ugc">{{ $bvSource }}<span class="bl-verifications__sr"> {{ __('(ouvre un nouvel onglet)') }}</span></a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        @endforeach
    </section>
@endif
