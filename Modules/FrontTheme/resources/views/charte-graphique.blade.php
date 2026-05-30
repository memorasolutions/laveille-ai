<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', 'Charte graphique & design system - ' . config('app.name'))
@section('meta_description', 'Référence interne du design system de La veille : tokens, composants réutilisables, overlays, dark mode.')
@push('head')
<meta name="robots" content="noindex, follow">
@endpush
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Charte graphique'])
@endsection
@section('content')
<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <h1 class="mb-4" style="color: var(--sys-text-default);">Charte graphique & design system</h1>

                <div style="margin-bottom: 40px; color: var(--sys-text-default);">
                    <p>Référence du design system — réutiliser, ne jamais dupliquer.</p>
                    <p>Tout consomme des <strong>design tokens</strong> (couche <code>--sys-*</code> sémantique). Les composants proviennent des packages <code>x-core</code> et <code>x-fronttheme</code>.</p>
                </div>

                <div style="margin-bottom: 40px;">
                    <h2 style="color: var(--sys-text-default); margin-bottom: 16px;">Couleurs</h2>
                    <div style="display: flex; flex-wrap: wrap; gap: 24px;">
                        @php
                            $colors = [
                                '--sys-action-primary' => 'Action primaire',
                                '--sys-action-primary-hover' => 'Action primaire (hover)',
                                '--sys-action-accent' => 'Accent',
                                '--sys-danger' => 'Danger',
                                '--sys-success' => 'Succès',
                                '--sys-warning' => 'Avertissement',
                                '--sys-text-default' => 'Texte par défaut',
                                '--sys-text-secondary' => 'Texte secondaire',
                                '--sys-surface-page' => 'Surface page',
                                '--sys-surface-raised' => 'Surface surélevée',
                                '--sys-border-default' => 'Bordure par défaut',
                            ];
                        @endphp
                        @foreach ($colors as $token => $label)
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <div style="width: 80px; height: 80px; border-radius: var(--sys-radius-md); background: var({{ $token }}); border: 1px solid var(--sys-border-default);"></div>
                                <span style="font-size: 12px; color: var(--sys-text-secondary); margin-top: 8px; text-align: center;">{{ $label }}<br><code>{{ $token }}</code></span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="margin-bottom: 40px;">
                    <h2 style="color: var(--sys-text-default); margin-bottom: 16px;">Typographie</h2>
                    <div>
                        <h3 style="font-family: var(--f-heading); color: var(--sys-text-default); margin-bottom: 8px;">Titre (Plus Jakarta Sans)</h3>
                        <p style="font-family: var(--f-body); color: var(--sys-text-default);">Corps de texte (DM Sans). Utilisé pour les paragraphes, listes et la majorité du contenu.</p>
                    </div>
                </div>

                <div style="margin-bottom: 40px;">
                    <h2 style="color: var(--sys-text-default); margin-bottom: 16px;">Boutons (x-core::button)</h2>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin: 16px 0;">
                        <x-core::button variant="primary">Primaire</x-core::button>
                        <x-core::button variant="secondary">Secondaire</x-core::button>
                        <x-core::button variant="accent">Accent</x-core::button>
                        <x-core::button variant="danger">Danger</x-core::button>
                        <x-core::button variant="ghost">Ghost</x-core::button>
                    </div>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin: 16px 0;">
                        <x-core::button variant="primary" size="sm">Petit</x-core::button>
                        <x-core::button variant="primary" size="md">Moyen</x-core::button>
                        <x-core::button variant="primary" size="lg">Grand</x-core::button>
                    </div>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin: 16px 0;">
                        <x-core::button variant="primary" :disabled="true">Désactivé</x-core::button>
                    </div>
                </div>

                <div style="margin-bottom: 40px;">
                    <h2 style="color: var(--sys-text-default); margin-bottom: 16px;">Formulaire infolettre (x-fronttheme::newsletter-form)</h2>
                    <x-fronttheme::newsletter-form source="design-system-demo" layout="inline" heading="Composant newsletter-form" intro="Réutilisé par 3 surfaces." />
                </div>

                <div style="margin-bottom: 40px;">
                    <h2 style="color: var(--sys-text-default); margin-bottom: 16px;">Overlays</h2>
                    <p style="color: var(--sys-text-default);">
                        Les overlays (modale, confirm, toast, bannières) partagent la même grammaire visuelle via les tokens <code>--cmp-overlay-*</code> (surface, rayon, ombre, voile, anneau de focus).
                    </p>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
