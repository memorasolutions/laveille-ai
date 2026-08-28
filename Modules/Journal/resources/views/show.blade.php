<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', $journal->title . ' - ' . config('app.name'))
@section('meta_description', 'Journal personnel « ' . $journal->title . ' », publié sur laveille.ai.')

@if (! $journal->isPublished())
    @section('page_noindex', true)
@endif

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $journal->title])
@endsection

@push('head')
    {!! \Modules\SEO\Services\JsonLdService::render(
        \Modules\SEO\Services\JsonLdService::article($journal),
        \Modules\SEO\Services\JsonLdService::breadcrumbs([
            ['name' => 'Accueil', 'url' => url('/')],
            ['name' => 'Mes journaux', 'url' => route('journal.index')],
            ['name' => $journal->title, 'url' => route('journal.show', $journal)],
        ])
    ) !!}
@endpush

{{-- Refonte visuelle 2026 (veille pp_search 2026-07-11) : typographie de lecture soignée
     (65-70ch, corps 18px, interligne 1.6 — métriques de lisibilité éditoriale 2026), minimalisme
     chaleureux (fond calme, marges généreuses, esprit "privacy-forward" propre aux apps de
     journal personnel), et 4 gabarits enfin visuellement distincts (jusqu'ici journal-template-*
     n'avait aucun CSS associé). Tokens de charte uniquement (--c-primary/--c-accent/--f-heading),
     aucune nouvelle police/couleur introduite. --}}
@push('styles')
<style>
    .journal-reading-wrapper { padding: 8px 0 48px; }

    .journal-title-block {
        font-size: clamp(1.9rem, 1.5rem + 1.6vw, 2.75rem);
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.01em;
        color: var(--sys-text-default, #1A1D23);
        margin-bottom: 6px;
    }
    {{-- Accent papier discret (Option B, veille pp_search 2026-07-12) : date au format manuscrit
         (police Caveat, self-hébergée, jamais sur le corps de texte - contraste AAA du corps
         inchangé) au lieu d'un petit label uppercase générique. --}}
    {{-- Sélecteur qualifié .journal-reading-wrapper .journal-meta (pas .journal-meta seul) :
         le thème global a une règle .wpo-blog-single-section p { color: rgb(89,89,89) } plus
         spécifique qu'une classe seule et l'emportait sur la couleur manuscrite prévue - trouvé
         par la vérification visuelle du 2026-07-12 (contraste restait AAA, mais teinte fausse). --}}
    .journal-reading-wrapper .journal-meta {
        font-family: 'Caveat', cursive;
        font-weight: 600;
        font-size: 1.15rem;
        letter-spacing: normal;
        text-transform: none;
        color: #4b5563;
        margin-bottom: 22px;
    }

    {{-- Papier ligné très discret (repeating-linear-gradient, opacité quasi nulle) - accent, pas décor. --}}
    .journal-blocks {
        display: flex; flex-direction: column; gap: 28px; max-width: 42rem; margin: 0 auto;
        background-image: repeating-linear-gradient(
            to bottom, transparent, transparent 1.65em,
            rgba(6, 78, 90, 0.045) 1.65em, rgba(6, 78, 90, 0.045) calc(1.65em + 1px)
        );
    }
    .journal-block-content {
        font-size: 1.0625rem;
        line-height: 1.65;
        color: var(--sys-text-default, #1A1D23);
    }
    .journal-block-content p { margin: 0 0 1em; }
    {{-- Le texte de citation Tiptap est rendu dans un <p> ENFANT du <blockquote>, jamais dans le
         <blockquote> lui-même : une règle globale du thème "p { font-size:16px }" cible ce <p>
         directement, ce qui bat l'héritage venant du parent quelle que soit sa spécificité.
         Il faut donc styler explicitement le <p> descendant, pas seulement le <blockquote> -
         trouvé par la vérification visuelle du 2026-07-12 (citation restait taille normale). --}}
    .journal-block-content blockquote,
    .journal-block-content blockquote p {
        font-family: 'Caveat', cursive;
        font-weight: 600;
        font-size: 1.35rem;
        line-height: 1.4;
        color: #4b5563;
    }
    .journal-block-content blockquote {
        border-left: 3px solid var(--c-primary, #064E5A);
        margin: 1em 0; padding: 2px 0 2px 18px;
    }

    .journal-block-image img { display: block; width: 100%; border-radius: 14px; }
    .journal-block-video .ratio { border-radius: 14px; overflow: hidden; }

    /* Bloc "source liée" (news/glossary/tool/directory_tool) — carte calme, pas de bordure Bootstrap brute */
    .journal-source-card {
        display: block; padding: 16px 18px; border-radius: 12px;
        background: #F9FAFB; border: 1px solid #EEF0F2;
        text-decoration: none; transition: background-color .15s ease, transform .15s ease;
    }
    .journal-source-card:hover { background: #F3F4F6; transform: translateY(-1px); }
    .journal-source-card .jsc-title { font-weight: 700; color: var(--sys-text-default, #1A1D23); }
    .journal-source-card .jsc-excerpt { font-size: 0.85rem; color: #6b7280; margin-top: 2px; }

    /* ===== Gabarit Magazine : bloc vedette + lettrine — esprit editorial 2026 ===== */
    .journal-template-magazine .journal-block-image,
    .journal-template-magazine .journal-block-video {
        margin-left: -1.25rem; margin-right: -1.25rem;
    }
    .journal-template-magazine .journal-block-image img,
    .journal-template-magazine .journal-block-video .ratio {
        border-radius: 18px;
        box-shadow: 0 16px 32px -12px rgba(6,78,90,0.18);
    }
    .journal-template-magazine .journal-first-text-block .journal-block-content > p:first-of-type::first-letter {
        float: left; font-family: var(--f-heading); font-size: 3.4rem; line-height: 0.85;
        font-weight: 800; color: var(--c-primary, #064E5A); padding: 4px 8px 0 0;
    }

    /* ===== Gabarit Carnet photo : cartes "polaroid" sobres + coin corné discret ===== */
    .journal-template-carnet-photo .journal-block-image {
        position: relative;
        background: #fff; padding: 10px 10px 22px; border-radius: 10px;
        box-shadow: 0 10px 24px -10px rgba(26,29,35,0.18);
        transform: rotate(-0.6deg);
    }
    .journal-template-carnet-photo .journal-block-image:nth-of-type(even) { transform: rotate(0.6deg); }
    .journal-template-carnet-photo .journal-block-image img { border-radius: 6px; }
    .journal-template-carnet-photo .journal-block-image::after {
        content: ''; position: absolute; top: 0; right: 0; width: 2.2rem; height: 2.2rem;
        background-color: #F9FAFB;
        clip-path: polygon(100% 0, 100% 25%, 75% 0);
        box-shadow: -2px 2px 4px rgba(0,0,0,0.06);
        transition: transform .2s ease-out;
    }
    .journal-template-carnet-photo .journal-block-image:hover::after { transform: translate(-2px, 2px); }
    @media (prefers-reduced-motion: reduce) {
        .journal-template-carnet-photo .journal-block-image:hover::after { transform: none; transition: none; }
    }

    /* ===== Gabarit Chronologique : ligne temporelle discrète ===== */
    .journal-template-chronologique .journal-blocks {
        position: relative; padding-left: 26px; max-width: 44rem;
    }
    .journal-template-chronologique .journal-blocks::before {
        content: ''; position: absolute; left: 6px; top: 6px; bottom: 6px;
        width: 2px; background: linear-gradient(var(--c-primary, #064E5A), var(--c-accent, #9A2A06));
        opacity: 0.35; border-radius: 1px;
    }
    .journal-template-chronologique .journal-block { position: relative; }
    .journal-template-chronologique .journal-block::before {
        content: ''; position: absolute; left: -26px; top: 6px;
        width: 10px; height: 10px; border-radius: 50%;
        background: var(--c-primary, #064E5A); border: 2px solid #fff;
        box-shadow: 0 0 0 2px rgba(6,78,90,0.25);
    }

    @media (max-width: 576px) {
        .journal-template-magazine .journal-block-image,
        .journal-template-magazine .journal-block-video { margin-left: 0; margin-right: 0; }
    }
</style>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding journal-reading-wrapper journal-template-{{ $journal->template }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if (! $journal->isPublished())
                    <div class="alert alert-secondary">
                        Ce journal est un <strong>brouillon privé</strong> – seul(e) vous pouvez le voir tant qu'il n'est pas publié.
                    </div>
                @endif

                <h1 class="journal-title-block" style="font-family: var(--f-heading);">{{ $journal->title }}</h1>
                @if ($journal->journal_date)
                    <p class="journal-meta">{{ $journal->journal_date->format('Y-m-d') }}</p>
                @endif

                @include('fronttheme::partials.article-action-bar', [
                    'model' => $journal,
                    'modelType' => 'Modules\\Journal\\Models\\Journal',
                ])
                <div class="mb-4">
                    <a href="{{ route('directory.takedown.create', ['url' => route('journal.show', $journal)]) }}" style="color:#9CA3AF; font-size:0.75rem; text-decoration:underline; text-underline-offset:2px;">⚖️ {{ __('Titulaire de droits ? Demander un retrait') }}</a>
                </div>

                @if ($isOwner)
                    <div class="d-flex gap-2 mb-4">
                        <x-core::button href="{{ route('journal.edit', $journal) }}" variant="secondary" size="sm">Éditer</x-core::button>
                        <x-core::button href="{{ route('journal.index') }}" variant="secondary" size="sm">Mes journaux</x-core::button>
                    </div>
                @endif

                <div class="journal-blocks">
                    @php $firstTextBlockSeen = false; @endphp
                    @forelse ($blocks as $block)
                        {{-- journal-first-text-block posé côté serveur sur le PREMIER bloc texte en ordre
                             d'affichage réel (pas :first-of-type CSS, qui filtre par balise <div> et rate
                             la lettrine dès que ce bloc n'est plus le tout premier enfant — bug trouvé par
                             la simulation E2E round 3 après un test de réordonnancement). --}}
                        @php
                            $isFirstTextBlock = $block->type === 'text' && ! $firstTextBlockSeen;
                            if ($isFirstTextBlock) { $firstTextBlockSeen = true; }
                        @endphp
                        <div class="journal-block journal-block-{{ $block->type }}{{ $isFirstTextBlock ? ' journal-first-text-block' : '' }}">
                            @switch($block->type)
                                @case('text')
                                    <div class="journal-block-content">{!! $block->safeHtml() !!}</div>
                                    @break
                                @case('image')
                                    <img src="{{ $block->payload['url'] ?? '' }}" alt="" class="img-fluid">
                                    @break
                                @case('video')
                                    <div class="ratio ratio-16x9">
                                        <iframe src="{{ $block->payload['embed_url'] ?? '' }}" title="Vidéo" allowfullscreen loading="lazy"></iframe>
                                    </div>
                                    @break
                                @default
                                    <a href="{{ $block->payload['url'] ?? '#' }}" class="journal-source-card">
                                        <div class="jsc-title">{{ $block->payload['title'] ?? '' }}</div>
                                        <div class="jsc-excerpt">{{ $block->payload['excerpt'] ?? '' }}</div>
                                    </a>
                            @endswitch
                        </div>
                    @empty
                        <p class="text-muted">Ce journal est vide pour l'instant.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

{{-- Barre admin flottante : PAS de toggle Lecture/Édition ici (volontaire) - un superadmin peut
     supprimer/modérer un journal mais ne peut plus le modifier silencieusement (correction sécurité/
     vie privée, cf. Gate::before RolesPermissionsServiceProvider). --}}
@can('view_admin_panel')
@include('core::components.admin-bar', [
    'label' => __('Journal admin'),
    'model' => $journal,
    'actions' => array_filter([
        Route::has('journal.destroy') ? ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('journal.destroy', $journal), 'method' => 'DELETE', 'confirm' => __('Supprimer ce journal ? Cette action est irréversible.'), 'danger' => true] : null,
    ]),
])
@endcan
