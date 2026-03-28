<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Mon abonnement') . ' - ' . config('app.name'))

@section('user-content')

@php
    $featureLabels = [
        'articles_illimites' => 'Tous les articles du blog',
        'articles_exclusifs' => 'Articles exclusifs "deep dive"',
        'repertoire_consultation' => 'Consultation du répertoire IA',
        'repertoire_complet' => 'Répertoire complet + filtres avancés',
        'repertoire_alertes' => 'Alertes nouveaux outils par catégorie',
        'glossaire_consultation' => 'Consultation du glossaire IA',
        'glossaire_complet' => 'Glossaire complet + contributions',
        'acronymes_consultation' => 'Consultation des acronymes',
        'acronymes_complet' => 'Acronymes complet + contributions',
        'newsletter_hebdomadaire' => 'Newsletter hebdomadaire',
        'newsletter_quotidienne' => 'Newsletter quotidienne',
        'newsletter_personnalisee' => 'Newsletter personnalisée',
        'bookmarks_10' => 'Jusqu\'à 10 favoris',
        'bookmarks_illimites' => 'Favoris illimités',
        'bookmarks_collections' => 'Collections de favoris',
        'votes_consultation' => 'Consultation des votes',
        'votes_illimites' => 'Votes illimités',
        'suggestions_illimitees' => 'Suggestions de modifications illimitées',
        'outils_gratuits' => 'Outils gratuits (calculatrice, MDP...)',
        'propositions_1_par_mois' => '1 proposition d\'outil par mois',
        'propositions_illimitees' => 'Propositions d\'outils illimitées',
        'badge_pro' => 'Badge Pro visible sur le profil',
        'reputation_complete' => 'Système de réputation complet',
        'leaderboard' => 'Accès au classement contributeurs',
        'export_favoris' => 'Export de vos favoris',
    ];
    $currentSlug = strtolower($planName ?? 'free');
    $plans = $allPlans->isNotEmpty() ? $allPlans->where('is_active', true) : collect();
@endphp

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">{{ __('Mon abonnement') }}</h2>
<p style="color: #777; margin: 0 0 25px;">{{ __('Gérez votre plan et votre facturation.') }}</p>

{{-- Plan actuel --}}
<div class="panel panel-default" style="margin-bottom: 25px;">
    <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-credit-card"></i> {{ __('Plan actuel') }}</h3></div>
    <div class="panel-body">
        <div style="display: flex !important; align-items: center !important; flex-wrap: wrap !important;">
            <div style="display: flex !important; align-items: center !important; margin-right: 20px;">
                <div style="width: 48px; height: 48px; border-radius: 8px; background: {{ $currentSlug === 'free' ? '#f5f5f5' : '#e8f0fe' }}; display: flex !important; align-items: center !important; justify-content: center !important; margin-right: 12px;">
                    <i class="fa {{ $currentSlug === 'free' ? 'fa-gift' : 'fa-star' }} fa-lg" style="color: {{ $currentSlug === 'free' ? '#999' : '#337ab7' }};"></i>
                </div>
                <div>
                    <strong style="display: block;">{{ $planName ?? __('Gratuit') }}</strong>
                    @if(isset($currentPlan) && $currentPlan && $currentPlan->price > 0)
                        <small style="color: #777;">{{ number_format($currentPlan->price, 2) }} {{ strtoupper($currentPlan->currency ?? 'CAD') }} / {{ $currentPlan->interval === 'yearly' ? 'an' : 'mois' }}</small>
                    @else
                        <small style="color: #777;">{{ __('Gratuit') }}</small>
                    @endif
                </div>
            </div>

            @if(isset($activeSub) && $activeSub)
                <span class="label label-success" style="padding: 5px 12px; margin-right: 15px;">
                    {{ $activeSub->stripe_status === 'trialing' ? __("Période d'essai") : __('Actif') }}
                </span>
            @else
                <span class="label label-default" style="padding: 5px 12px;">{{ __('Plan gratuit') }}</span>
            @endif
        </div>
    </div>
</div>

{{-- Comparatif plans --}}
<h3 style="font-weight: 700; margin: 0 0 20px;">{{ __('Comparer les plans') }}</h3>

@if($plans->isNotEmpty())
<div class="row">
    @foreach($plans as $plan)
    @php
        $slug = $plan->slug ?? '';
        $name = $plan->name ?? '';
        $price = (float) ($plan->price ?? 0);
        $features = $plan->features ?? [];
        $isCurrent = strtolower($slug) === $currentSlug || strtolower($name) === $currentSlug;
        $isPopular = $slug === 'pro';
    @endphp
    <div class="col-md-6" style="margin-bottom: 20px;">
        <div class="panel {{ $isCurrent ? 'panel-primary' : ($isPopular ? 'panel-info' : 'panel-default') }}" style="height: 100%; position: relative;">
            @if($isPopular && !$isCurrent)
                <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); z-index: 2;">
                    <span class="label label-primary" style="padding: 4px 14px; font-size: 12px;">{{ __('Populaire') }}</span>
                </div>
            @endif
            @if($isCurrent)
                <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); z-index: 2;">
                    <span class="label label-success" style="padding: 4px 14px; font-size: 12px;">{{ __('Plan actuel') }}</span>
                </div>
            @endif

            <div class="panel-heading" style="text-align: center; padding: 20px 15px 15px;">
                <h3 class="panel-title" style="font-size: 20px; font-weight: 700;">{{ $name }}</h3>
                @if($price > 0)
                    <div style="margin-top: 10px;">
                        <span style="font-size: 32px; font-weight: 700; color: #333;">{{ number_format($price, 0) }}$</span>
                        <span style="color: #777;"> / {{ __('mois') }}</span>
                    </div>
                    <small style="color: #999;">{{ __('ou') }} {{ number_format($price * 10, 0) }}$ / {{ __('an') }} ({{ __('2 mois gratuits') }})</small>
                @else
                    <div style="margin-top: 10px;">
                        <span style="font-size: 32px; font-weight: 700; color: #333;">{{ __('Gratuit') }}</span>
                    </div>
                    <small style="color: #999;">{{ __('Pour toujours') }}</small>
                @endif
            </div>

            <div class="panel-body" style="padding: 15px 20px;">
                @if($plan->description)
                    <p style="color: #555; font-size: 13px; margin-bottom: 15px; text-align: center;">{{ $plan->description }}</p>
                @endif

                <ul class="list-unstyled" style="margin-bottom: 20px;">
                    @foreach($features as $feature)
                    <li style="padding: 5px 0; font-size: 13px; color: #555; display: flex !important; align-items: flex-start !important;">
                        <i class="fa fa-check-circle" style="color: #5cb85c; margin-right: 8px; margin-top: 2px; flex-shrink: 0;"></i>
                        {{ $featureLabels[$feature] ?? ucfirst(str_replace('_', ' ', $feature)) }}
                    </li>
                    @endforeach
                </ul>

                <div style="text-align: center;">
                    @if($isCurrent)
                        <button disabled class="btn btn-default btn-block">{{ __('Plan actuel') }}</button>
                    @elseif($plan->stripe_price_id && Route::has('checkout'))
                        <form action="{{ route('checkout') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="btn {{ $isPopular ? 'btn-primary' : 'btn-default' }} btn-block">
                                {{ __('Choisir') }} {{ $name }}
                            </button>
                        </form>
                    @else
                        <button disabled class="btn btn-default btn-block" title="{{ __('Bientôt disponible') }}">
                            {{ __('Bientôt disponible') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Billing Portal --}}
@if(isset($activeSub) && $activeSub && Route::has('billing.portal'))
<div class="alert alert-info" style="margin-top: 10px;">
    <div style="display: flex !important; align-items: center !important; flex-wrap: wrap !important;">
        <div style="flex: 1 !important;">
            <strong>{{ __('Gérer votre facturation') }}</strong>
            <p style="margin: 5px 0 0; font-size: 13px;">{{ __('Modifiez votre moyen de paiement, téléchargez vos factures ou annulez votre abonnement.') }}</p>
        </div>
        <a href="{{ route('billing.portal') }}" class="btn btn-primary" style="margin-left: 15px;">
            <i class="fa fa-credit-card"></i> {{ __('Portail de facturation') }}
        </a>
    </div>
</div>
@endif

@endsection
