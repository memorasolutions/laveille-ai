{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@php
    // Demande utilisateur (2026-07-19) : le créateur connecté qui clique "Gérer" depuis "Mes
    // sondages" doit rester dans la mise en page "Mon espace" (sidebar, cohérence de navigation),
    // alors qu'un délégué anonyme avec le jeton en clair (partage à un co-organisateur sans compte,
    // cf. commentaire dans le partial results-content) n'a ni compte ni sidebar à afficher - seul
    // le GABARIT change selon le contexte, jamais le contenu ni les protections de sécurité
    // (noindex/no_analytics/no_ads/breadcrumb sans breadcrumbItems, rounds 10/12/26 ci-dessous),
    // identiques dans les deux cas. authorizeManage() (contrôleur) autorise déjà le créateur
    // connecté quel que soit le jeton fourni dans l'URL - ce $isOwnerContext ne fait que choisir
    // l'HABILLAGE visuel, la vérification d'accès reste entièrement côté contrôleur.
    $isOwnerContext = auth()->check() && auth()->id() === $poll->creator_id;
    $contentSection = $isOwnerContext ? 'user-content' : 'content';
@endphp
@extends($isOwnerContext ? 'auth::layouts.user-frontend' : fronttheme_layout())
@section('title', "Résultats - {$poll->title} · " . config('app.name'))
{{-- Round 10 (skill /100) : page de gestion protégée par un jeton devinable dans l'URL, jamais
     destinée à l'indexation - expose les pseudonymes et résultats complets des votants. --}}
@section('page_noindex', true)
{{-- Round 12 (skill /100) : le jeton admin (contrôle TOTAL du sondage - clôture, export des
     pseudonymes des votants, création de lien court) transite en clair dans le CHEMIN de l'URL
     (/decido/{poll}/gerer/{adminToken}). Le round 10 bloquait déjà l'indexation (page_noindex),
     mais laissait passer une fuite bien plus grave et systématique : le layout charge GA4 avec
     send_page_view:true sur TOUTE page qui ne déclare pas no_analytics (master.blade.php) - le
     hit GA4 capture automatiquement page_location = window.location.href, donc le jeton admin en
     clair était transmis à un tiers (Google) et stocké indéfiniment dans la propriété GA4, à
     chaque chargement de cette page (visite initiale après création, chaque rafraîchissement,
     redirection post-clôture/lien court). Même posture que l'anonymiseur (Modules/Tools -
     outil traitant des PII) : aucune pub ni analytics tierce sur une page qui expose un secret
     de sécurité et des données de votants dans son URL/contenu. --}}
@section('no_analytics', '1')
@section('no_ads', '1')
@section('meta_description', "Résultats du sondage Décido « {$poll->title} » : visualise les votes, clôture le sondage et exporte les données.")
@section('breadcrumb')
    {{-- NE JAMAIS ajouter 'breadcrumbItems' ici (verrouillé par un test round 26/skill 100,
         DecidoPollTest::"la page de gestion (jeton admin dans l'URL) n'expose pas ce jeton via le
         JSON-LD BreadcrumbList") : cette route contient le jeton admin en clair dans l'URL
         (/decido/{poll}/gerer/{adminToken}), et fronttheme::partials.breadcrumb utilise
         url()->current() (sans exclusion) pour le dernier ListItem du BreadcrumbList JSON-LD dès
         que 'breadcrumbItems' est fourni - une fuite du jeton dans une donnée structurée
         indexable, indépendante de la balise noindex de la page elle-même. Ce comportement (et
         donc ce commentaire) s'applique identiquement dans les deux gabarits ci-dessus : le
         layout "Mon espace" a bien son propre breadcrumb par défaut, mais notre @section('breadcrumb')
         local le remplace toujours, préservant cette garantie de sécurité. --}}
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Résultats du sondage'])
@endsection
@section($contentSection)
    @if($isOwnerContext)
        @include('decido::manage.partials.results-content', ['poll' => $poll, 'options' => $options, 'adminToken' => $adminToken, 'declines' => $declines ?? $poll->declines, 'comments' => $comments ?? $poll->comments])
    @else
        <section class="wpo-blog-single-section section-padding" x-data="{}">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        @include('decido::manage.partials.results-content', ['poll' => $poll, 'options' => $options, 'adminToken' => $adminToken, 'declines' => $declines ?? $poll->declines, 'comments' => $comments ?? $poll->comments])
                    </div>
                </div>
            </div>
        </section>
    @endif
{{-- .decido-touch-target : voir public/css/charte.css (round 8, déplacé hors de cette vue pour
     rester DRY - réutilisé aussi par create.blade.php et public/vote.blade.php). --}}
@endsection
