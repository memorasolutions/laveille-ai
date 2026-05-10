<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Statistiques publiques') . ' - ' . config('app.name'))
@section('meta_description', __("Compteurs temps réel de La veille : nombre d'outils répertoriés, tutoriels, articles, collections. Transparence et signal d'autorité 2026."))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Statistiques')])
@endsection

@push('head')
@php
    $statsJsonLd = json_encode([
        chr(64).'context' => 'https://schema.org',
        chr(64).'type' => 'Dataset',
        'name' => 'Statistiques publiques de La veille',
        'description' => "Compteurs temps réel de l'inventaire La veille : outils, tutoriels, articles, collections.",
        'url' => url()->current(),
        'creator' => [
            chr(64).'type' => 'Organization',
            'name' => 'La veille',
            'url' => url('/'),
        ],
        'license' => 'https://creativecommons.org/licenses/by/4.0/',
        'inLanguage' => 'fr-CA',
        'dateModified' => $stats['updated_at'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $statsJsonLd !!}</script>
@endpush

@section('content')
<section style="padding: 60px 0 40px; background: #fff;">
    <div class="container">
        <div style="max-width: 980px; margin: 0 auto;">

            <header style="margin-bottom: 36px; text-align: center;">
                <p style="text-transform: uppercase; letter-spacing: 1.5px; font-size: 12px; font-weight: 700; color: var(--c-primary, #064E5A); margin: 0 0 10px;">
                    {{ __('Transparence · Mis à jour en temps réel') }}
                </p>
                <h1 style="margin: 0 0 14px; font-weight: 800; font-size: clamp(28px, 4vw, 42px); line-height: 1.15; letter-spacing: -0.5px; color: var(--c-dark, #1a1d23);">
                    {{ __('La veille en chiffres') }}
                </h1>
                <p style="font-size: 17px; color: #4b5563; margin: 0 auto; max-width: 640px; line-height: 1.55;">
                    {{ __('Compteurs vivants de la plateforme. Données rafraîchies toutes les heures. Téléchargeable en JSON via notre') }} <a href="{{ url('/api') }}" style="color: var(--c-primary); font-weight: 600;">{{ __('API publique') }}</a>.
                </p>
            </header>

            @php
                $cards = [
                    ['emoji' => '🛠️', 'value' => $stats['tools_total'], 'label' => __('Outils IA répertoriés'),    'sub' => __('publiés et vérifiés'),                            'cta' => url('/annuaire'),     'cta_label' => __("Explorer l'annuaire")],
                    ['emoji' => '🎓', 'value' => $stats['tools_with_education'], 'label' => __("Outils avec programme éducation"), 'sub' => __('gratuits, rabais ou vérifiés'),       'cta' => url('/annuaire?has_education_pricing=1'), 'cta_label' => __('Filtrer éducation')],
                    ['emoji' => '📚', 'value' => $stats['tutorials_total'], 'label' => __('Tutoriels vidéo curatés'),  'sub' => $stats['tutorials_fr'].' FR · '.$stats['tutorials_en'].' EN', 'cta' => url('/annuaire?has_tutorials=1'), 'cta_label' => __('Voir les tutos')],
                    ['emoji' => '📦', 'value' => $stats['collections_public'], 'label' => __('Collections & stacks IA'), 'sub' => __('top par tâche · stacks par persona'),                  'cta' => url('/collections'), 'cta_label' => __('Voir les collections')],
                    ['emoji' => '📰', 'value' => $stats['articles_published'], 'label' => __('Articles publiés'),         'sub' => __('analyses + concentrés IA hebdo'),                       'cta' => url('/blog'),        'cta_label' => __('Voir le blog')],
                    ['emoji' => '🗞️', 'value' => $stats['concentres_published'], 'label' => __('Concentrés IA hebdo'),     'sub' => __('chaque dimanche depuis 2025'),                          'cta' => url('/rss/concentres.xml'), 'cta_label' => '📡 RSS'],
                    ['emoji' => '📖', 'value' => $stats['glossary_terms'], 'label' => __('Termes du glossaire IA'),  'sub' => __('vocabulaire francophone'),                              'cta' => url('/glossaire'),   'cta_label' => __('Glossaire')],
                    ['emoji' => '🎮', 'value' => $stats['interactive_tools'], 'label' => __('Outils interactifs gratuits'), 'sub' => __('sudoku, mots-croisés, calc. taxes...'),     'cta' => url('/outils'),      'cta_label' => __('Voir les outils')],
                ];
            @endphp

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">
                @foreach($cards as $c)
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 24px 22px; transition: transform 0.15s, box-shadow 0.15s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(6,78,90,0.08)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none';this.style.transform='none'">
                    <div style="font-size: 32px; line-height: 1;" aria-hidden="true">{{ $c['emoji'] }}</div>
                    <div style="font-weight: 800; font-size: clamp(28px, 3.4vw, 38px); margin: 12px 0 4px; color: var(--c-primary, #064E5A); font-variant-numeric: tabular-nums; letter-spacing: -0.5px;">{{ number_format($c['value'], 0, ',', ' ') }}</div>
                    <div style="font-weight: 700; font-size: 15px; color: var(--c-dark, #1a1d23); margin-bottom: 4px;">{{ $c['label'] }}</div>
                    <div style="font-size: 13px; color: var(--c-text-muted, #52586a); margin-bottom: 14px;">{{ $c['sub'] }}</div>
                    @if(!empty($c['cta']))
                        <a href="{{ $c['cta'] }}" style="display: inline-block; font-size: 13px; color: var(--c-primary, #064E5A); font-weight: 600; text-decoration: none; border-bottom: 1px solid currentColor;">{{ $c['cta_label'] }} →</a>
                    @endif
                </div>
                @endforeach
            </div>

            <div style="background: #F0F4F8; border-radius: 12px; padding: 20px 24px; margin-top: 32px; font-size: 14px; color: #4b5563; display: flex; flex-wrap: wrap; gap: 10px 30px; justify-content: space-between;">
                <span>📅 {{ __('Dernière mise à jour') }} : <time datetime="{{ $stats['updated_at'] }}">{{ \Carbon\Carbon::parse($stats['updated_at'])->isoFormat('LL [à] HH:mm') }}</time></span>
                @if($stats['last_tool_added'])
                    <span>🛠️ {{ __('Dernier outil ajouté') }} : <time datetime="{{ $stats['last_tool_added'] }}">{{ \Carbon\Carbon::parse($stats['last_tool_added'])->diffForHumans() }}</time></span>
                @endif
                @if($stats['last_tutorial_added'])
                    <span>📚 {{ __('Dernier tutoriel') }} : <time datetime="{{ $stats['last_tutorial_added'] }}">{{ \Carbon\Carbon::parse($stats['last_tutorial_added'])->diffForHumans() }}</time></span>
                @endif
            </div>

            <div style="margin-top: 28px; padding: 20px 24px; border: 1px dashed #cbd5e1; border-radius: 12px; text-align: center;">
                <p style="margin: 0 0 8px; font-weight: 700; color: var(--c-dark, #1a1d23);">{{ __('Vous êtes développeur ou chercheur ?') }}</p>
                <p style="margin: 0; font-size: 14px; color: #4b5563;">
                    {{ __('Toutes ces données sont accessibles via notre') }} <a href="{{ url('/api') }}" style="color: var(--c-primary); font-weight: 600;">{{ __('API JSON publique') }}</a> ({{ __('licence CC BY 4.0') }}).
                </p>
            </div>

        </div>
    </div>
</section>
@endsection
