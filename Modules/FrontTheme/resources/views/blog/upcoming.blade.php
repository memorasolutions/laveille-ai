<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{--
    Page d'avant-première d'un article de blogue PLANIFIÉ (statut "published", published_at dans
    le futur). L'adresse définitive de l'article sert cette page en 200/noindex jusqu'à la date
    prévue plutôt qu'un 404 ou un 503 (bonne pratique Google Search Central, 2026-09-25) - à la
    date prévue, la MÊME adresse sert l'article complet (fronttheme::blog.show) sans noindex.

    RÈGLE ABSOLUE DE CE GABARIT : $article->content ne doit JAMAIS y être lu, ni affiché, ni
    inclus dans un JSON-LD. Seuls le titre, l'image à la une, la date de parution et l'extrait
    (résumé éditorial court, jamais le corps) sont montrés.
--}}
@extends(fronttheme_layout())

@section('title', ($article->seo_title ?? $article->title) . ' - ' . config('app.name'))
@section('meta_description', 'À paraître prochainement sur ' . config('app.name') . '. ' . safe_excerpt($article->excerpt, 140))
@section('og_type', 'website')
{{-- Mécanisme EXISTANT du layout (Modules/FrontTheme/resources/views/layouts/master.blade.php) :
     la simple présence de cette section fait passer la balise <meta name="robots"> à
     "noindex, follow, max-image-preview:large". L'en-tête HTTP X-Robots-Tag: noindex est posé
     séparément par le contrôleur (PublicPostController::show()). --}}
@section('page_noindex', true)

@if($article->featured_image)
    @section('og_image', $article->featured_image_shareable_url . '?v=' . ($article->updated_at?->timestamp ?? '0'))
@endif

@php
    // Heure du Québec, format typographique demandé ("9 h", "9 h 30" - jamais "9 h 00").
    $dateQuebec = $article->published_at?->clone()->timezone('America/Toronto');
    $avantPremiereTexte = null;

    if ($dateQuebec) {
        $heure = (int) $dateQuebec->format('G');
        $minute = (int) $dateQuebec->format('i');
        $heureTexte = $minute === 0
            ? $heure . "\u{00A0}h"
            : $heure . "\u{00A0}h\u{00A0}" . sprintf('%02d', $minute);

        $avantPremiereTexte = 'À paraître le ' . $dateQuebec->locale('fr')->isoFormat('dddd D MMMM YYYY') . ' à ' . $heureTexte;
    }
@endphp

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $article->title,
        'breadcrumbItems' => [__('Blog'), $article->title]
    ])
@endsection

@section('content')
    <!-- start wpo-blog-single-section -->
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8 col-12">
                    <div class="wpo-blog-content">
                        <div class="post format-standard-image">
                            @if($article->featured_image)
                                <div class="entry-media">
                                    <img src="{{ $article->featured_image_url }}?v={{ $article->updated_at?->timestamp ?? time() }}" alt="{{ $article->title }}" loading="eager" decoding="async">
                                </div>
                            @endif

                            <h1 style="margin: 24px 0 12px; font-size: 1.8rem; color: #1A1D23;">{{ $article->title }}</h1>

                            @if($avantPremiereTexte)
                                <p
                                    role="status"
                                    style="display: inline-block; background-color: #F0FAFB; border: 2px solid #D5EDF0; border-radius: 8px; padding: 10px 16px; margin: 0 0 24px; font-size: 0.95rem; font-weight: 700; color: #064E5A;"
                                >
                                    {{ $avantPremiereTexte }}
                                </p>
                            @endif

                            @if(filled($article->excerpt))
                                <div class="entry-details" style="font-size: 1.05rem; line-height: 1.7; color: #1A1D23; margin-bottom: 24px;">
                                    <p>{{ $article->excerpt }}</p>
                                </div>
                            @endif

                            {{-- Navigation série (détection automatique par slug "-partie-N") - marque
                                 CETTE partie comme « Vous êtes ici » et donne accès aux parties déjà
                                 parues et aux autres parties planifiées. --}}
                            @include('fronttheme::partials.series-nav', ['article' => $article])

                            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #D5EDF0; color: #4A5160; font-size: 0.95rem; line-height: 1.7;">
                                <p>
                                    Cet article n'est pas encore en ligne{{ $avantPremiereTexte ? "\u{00A0}: reviens à la date indiquée ci-dessus pour le lire en entier" : '' }}.
                                    En attendant, découvre les parties déjà parues ci-dessus ou
                                    <a href="{{ route('blog.index') }}" style="color: #064E5A; text-decoration: underline; font-weight: 600;">consulte tous les articles du blogue</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col col-lg-4 col-12 d-none d-lg-block">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div>
    </section>
    <!-- end wpo-blog-single-section -->
@endsection
