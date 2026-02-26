@extends('fronttheme::themes.gosass.layouts.app')

@section('title', __('À propos').' - '.config('app.name'))
@section('description', __('Découvrez notre mission, nos valeurs et notre équipe.').' '.config('app.name').' - '.__('Solution SaaS sécurisée et performante.'))

@section('content')

<div class="cs_height_85 cs_height_lg_80"></div>
<div class="container text-center">
    <h2 class="cs_fs_50 cs_mb_15 wow fadeInDown">{{ __('À propos') }}</h2>
    <p class="mb-0 wow fadeInUp">{{ __('Découvrez notre mission, nos valeurs') }}<br>{{ __('et ce qui nous anime au quotidien.') }}</p>
</div>

<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    <div class="row cs_gap_y_24">
        <div class="col-lg-4 wow fadeIn" data-wow-delay="0s">
            <div class="cs_radius_15 cs_white_bg p-4 h-100 text-center">
                <div class="cs_fs_50 text-primary mb-3">🔒</div>
                <h5 class="cs_mb_15">{{ __('Sécurité') }}</h5>
                <p class="mb-0">{{ __('Vos données sont protégées par les meilleures pratiques de sécurité : chiffrement, 2FA, audit logs et conformité RGPD.') }}</p>
            </div>
        </div>
        <div class="col-lg-4 wow fadeIn" data-wow-delay="0.05s">
            <div class="cs_radius_15 cs_white_bg p-4 h-100 text-center">
                <div class="cs_fs_50 text-primary mb-3">✨</div>
                <h5 class="cs_mb_15">{{ __('Simplicité') }}</h5>
                <p class="mb-0">{{ __('Une interface intuitive et moderne pour gérer votre activité sans complexité inutile.') }}</p>
            </div>
        </div>
        <div class="col-lg-4 wow fadeIn" data-wow-delay="0.1s">
            <div class="cs_radius_15 cs_white_bg p-4 h-100 text-center">
                <div class="cs_fs_50 text-primary mb-3">⚡</div>
                <h5 class="cs_mb_15">{{ __('Performance') }}</h5>
                <p class="mb-0">{{ __('Infrastructure optimisée avec cache, CDN et monitoring temps réel pour une expérience rapide.') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    <div class="row align-items-center cs_gap_y_24">
        <div class="col-lg-6 wow fadeInLeft">
            <h3 class="cs_fs_50 cs_mb_15">{{ __('Notre mission') }}</h3>
            <p>{{ __('Nous développons une plateforme SaaS complète et modulaire, conçue pour accompagner les entreprises dans leur transformation numérique.') }}</p>
            <p>{{ __('Notre objectif : offrir des outils puissants, accessibles et sécurisés, permettant à chacun de se concentrer sur l\'essentiel - la croissance de son activité.') }}</p>
        </div>
        <div class="col-lg-6 wow fadeInRight">
            <h3 class="cs_fs_50 cs_mb_15">{{ __('Nos valeurs') }}</h3>
            <ul class="list-unstyled">
                <li class="mb-3"><strong>{{ __('Transparence') }}</strong> - {{ __('Communication claire sur nos processus et nos tarifs.') }}</li>
                <li class="mb-3"><strong>{{ __('Innovation') }}</strong> - {{ __('Exploration constante des meilleures technologies.') }}</li>
                <li class="mb-3"><strong>{{ __('Satisfaction') }}</strong> - {{ __('L\'expérience utilisateur au coeur de chaque décision.') }}</li>
                <li class="mb-3"><strong>{{ __('Fiabilité') }}</strong> - {{ __('Un service disponible 24/7 avec un support réactif.') }}</li>
            </ul>
        </div>
    </div>
</div>

<div class="cs_height_85 cs_height_lg_80"></div>

@endsection
