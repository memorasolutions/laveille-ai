<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Tableau de bord organisationnel — Analytiques prédictifs - ' . config('app.name'))
@section('meta_description', 'Vue d\'ensemble des risques d\'abandon par formation (administrateurs uniquement).')

@section('content')
<x-academy::nav />
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                @if(config('academy.predictive_analytics_enabled', false))
                    <livewire:academy::org-analytics-dashboard />
                @else
                    <div style="background: #F9FAFB; border-radius: var(--sys-radius-md, 0.75rem); padding: 2.5rem; text-align: center; font-family: var(--f-body, system-ui);">
                        <p style="font-size: 1rem; color: var(--sys-text-default, #1A1D23); margin: 0 0 12px; font-weight: 600;">
                            Les analytiques prédictifs ne sont pas encore activés.
                        </p>
                        <a href="{{ route('academy.dashboard') }}"
                           style="color: var(--sys-action-primary, #064E5A); font-size: 0.9rem;">
                            Accéder à l'espace Académie
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
