{{--
    Signature éditoriale « Vérifié par la rédaction » - signal humain E-E-A-T vérifiable.

    Design doc SPEC-SIGNAL-HUMAIN (décision club des sages 5 oracles, notée 93/100, 2026-08-20) :
    n'affiche JAMAIS l'auteur de la source EXTERNE comme byline (retiré volontairement le
    2026-08-17, arbitrage du panel éditorial) - uniquement « La rédaction de laveille.ai » (ou le
    libellé posé, s'il existe), et SEULEMENT quand une vraie relecture éditoriale a eu lieu.

    Rendu UNIQUEMENT si $article->hasEditorialReview() (reviewed_at posé par la SEULE porte
    d'écriture bornée, Modules\News\Console\NewsApplyCommand - jamais fabriqué côté vue) : une
    fiche jamais relue reste intacte, sans mention de vérification trompeuse.

    Props :
        article (\Modules\News\Models\NewsArticle) requis

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
@props(['article'])

@if($article->hasEditorialReview())
    @once
    @push('styles')
    <style>
        /* Signature éditoriale - même motif que .section-author-byline (charte.css) : fond
           teinté du jeton primaire, bordure gauche 3px, contraste AAA via --c-text-secondary. */
        .nw-editorial-signature {
            display: flex; align-items: flex-start; gap: 0.625rem;
            margin: 0.75rem 0 1.25rem;
            padding: 0.625rem 0.875rem;
            background: rgba(6, 78, 90, 0.04);
            border-left: 3px solid var(--c-primary, #064E5A);
            border-radius: 0.375rem;
            font-size: 0.875rem;
            line-height: 1.5;
            color: var(--c-text-secondary, #4a4f5c);
        }
        .nw-editorial-signature__icon {
            flex-shrink: 0; width: 18px; height: 18px; margin-top: 0.125rem;
            color: var(--c-primary, #064E5A);
        }
        .nw-editorial-signature__text strong { color: var(--c-dark, #1A1D23); font-weight: 600; }
        .nw-editorial-signature__link {
            color: var(--c-primary, #064E5A); font-weight: 600; text-decoration: none;
        }
        .nw-editorial-signature__link:hover,
        .nw-editorial-signature__link:focus-visible { text-decoration: underline; }
    </style>
    @endpush
    @endonce

    <div class="nw-editorial-signature" role="note">
        <svg class="nw-editorial-signature__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
            <path d="M12 2l7 3v6c0 5-3.4 8.6-7 10-3.6-1.4-7-5-7-10V5l7-3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M9 12l2 2 4-4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="nw-editorial-signature__text">
            {{ __('Vérifié par') }} <strong>{{ $article->reviewerLabel() }}</strong>
            {{ __('le') }} <time datetime="{{ $article->reviewed_at->toDateString() }}">{{ format_date($article->reviewed_at, 'long') }}</time>.
            <a href="{{ route('methodologie') }}" class="nw-editorial-signature__link">{{ __('Notre méthodologie') }}</a>
        </span>
    </div>
@endif
