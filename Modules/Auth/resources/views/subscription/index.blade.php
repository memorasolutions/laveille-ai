@extends('auth::layouts.app')

@section('title', __('Mon abonnement'))

@section('content')

@php
$defaultPlans = [
    ['name' => 'Free',       'slug' => 'free',       'price' => 0,  'currency' => 'CAD', 'interval' => 'monthly',
     'features' => ['Articles illimités', 'Blog public', 'Support communautaire', '1 Go de stockage médias']],
    ['name' => 'Pro',        'slug' => 'pro',        'price' => 29, 'currency' => 'CAD', 'interval' => 'monthly',
     'features' => ['Tout Free inclus', 'Accès API complète', '10 Go de médias', 'Support prioritaire', 'Analyses avancées']],
    ['name' => 'Enterprise', 'slug' => 'enterprise', 'price' => 99, 'currency' => 'CAD', 'interval' => 'monthly',
     'features' => ['Tout Pro inclus', 'Multi-tenancy', 'SLA 99.9%', 'Support dédié 24/7', 'Déploiement sur mesure']],
];
$plans = $allPlans->isNotEmpty() ? $allPlans : collect($defaultPlans);
$currentSlug = strtolower($planName);
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="fw-semibold mb-1">{{ __('Mon abonnement') }}</h1>
        <p class="text-muted mb-0">{{ __('Gérez votre plan et votre facturation.') }}</p>
    </div>
    <span class="badge fw-semibold px-3 py-2 rounded-1
        {{ $planName === 'Free' ? 'bg-secondary bg-opacity-10 text-secondary' : 'bg-primary bg-opacity-10 text-primary' }}">
        <i data-lucide="{{ $planName === 'Free' ? 'box' : 'crown' }}"></i>
        Plan {{ $planName }}
    </span>
</div>

{{-- Plan actuel --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="file-text" class="text-primary"></i>
            {{ __('Plan actuel') }}
        </h5>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-4">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-3 d-flex align-items-center justify-content-center
                    {{ $planName === 'Free' ? 'bg-secondary bg-opacity-10' : 'bg-primary bg-opacity-10' }}"
                    style="width:48px;height:48px;">
                    <i data-lucide="{{ $planName === 'Free' ? 'box' : 'crown' }}"
                       class="{{ $planName === 'Free' ? 'text-secondary' : 'text-primary' }}"></i>
                </div>
                <div>
                    <p class="fw-semibold mb-1">{{ $planName }}</p>
                    @if($currentPlan && $currentPlan->price > 0)
                        <p class="text-muted text-sm mb-0">
                            {{ number_format($currentPlan->price, 2) }} {{ strtoupper($currentPlan->currency ?? 'CAD') }} /
                            {{ $currentPlan->interval === 'yearly' ? 'an' : 'mois' }}
                        </p>
                    @else
                        <p class="text-muted text-sm mb-0">{{ __('Gratuit') }}</p>
                    @endif
                </div>
            </div>

            @if($activeSub)
                <span class="badge fw-semibold bg-success bg-opacity-10 text-success rounded-1 px-3 py-2 d-flex align-items-center gap-1">
                    <span style="width:8px;height:8px;background:currentColor;border-radius:50%;display:inline-block;"></span>
                    {{ $activeSub->stripe_status === 'trialing' ? __("Période d'essai") : __('Actif') }}
                </span>
            @else
                <span class="badge fw-semibold bg-secondary bg-opacity-10 text-secondary rounded-1 px-3 py-2">
                    {{ __('Plan gratuit') }}
                </span>
            @endif

            @if($activeSub && !empty($activeSub->current_period_end))
                <p class="text-muted text-sm mb-0">
                    <i data-lucide="calendar" class="me-1"></i>
                    {{ __('Renouvellement') }} : {{ \Carbon\Carbon::parse($activeSub->current_period_end)->format('d M Y') }}
                </p>
            @endif
        </div>
    </div>
</div>

{{-- Comparatif plans --}}
<h2 class="fw-semibold mb-3">{{ __('Comparer les plans') }}</h2>

<div class="row gy-4 mb-4">
    @foreach($plans as $plan)
    @php
        $slug     = is_array($plan) ? $plan['slug'] : $plan->slug;
        $name     = is_array($plan) ? $plan['name'] : $plan->name;
        $price    = is_array($plan) ? $plan['price'] : (float) $plan->price;
        $features = is_array($plan) ? $plan['features'] : ($plan->features ?? []);
        $isCurrent = strtolower($slug) === $currentSlug || strtolower($name) === $currentSlug;
        $isPopular = $slug === 'pro';
    @endphp
    <div class="col-md-4">
        <div class="card h-100 position-relative {{ $isCurrent ? 'border border-primary' : '' }}">

            @if($isPopular && ! $isCurrent)
                <div class="position-absolute start-50 translate-middle-x"
                     style="top:-14px; z-index:2;">
                    <span class="badge fw-semibold px-3 py-2 bg-primary text-white rounded-pill">{{ __('Populaire') }}</span>
                </div>
            @endif
            @if($isCurrent)
                <div class="position-absolute start-50 translate-middle-x"
                     style="top:-14px; z-index:2;">
                    <span class="badge fw-semibold px-3 py-2 bg-success text-white rounded-pill">{{ __('Plan actuel') }}</span>
                </div>
            @endif

            <div class="card-body d-flex flex-column pt-4">
                <div class="mb-3">
                    <h4 class="fw-semibold mb-2">{{ $name }}</h4>
                    @if($price > 0)
                        <span class="fw-bold text-heading" style="font-size:2rem;">{{ $price }}$</span>
                        <span class="text-muted text-sm"> / {{ __('mois') }}</span>
                    @else
                        <span class="fw-bold text-heading" style="font-size:2rem;">{{ __('Gratuit') }}</span>
                    @endif
                </div>

                <ul class="list-unstyled mb-3 flex-grow-1">
                    @foreach($features as $feature)
                    <li class="d-flex align-items-start gap-2 mb-2 text-sm text-muted">
                        <i data-lucide="check-circle" class="text-success flex-shrink-0 mt-1"></i>
                        {{ is_string($feature) ? ucfirst(str_replace('_', ' ', $feature)) : $feature }}
                    </li>
                    @endforeach
                </ul>

                @if($isCurrent)
                    <button disabled class="btn btn-outline-secondary rounded-2 w-100">{{ __('Plan actuel') }}</button>
                @elseif(!is_array($plan) && $plan->stripe_price_id)
                    <form action="{{ route('checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="btn {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }} rounded-2 w-100">
                            {{ __('Choisir') }} {{ $name }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('pricing') }}" class="btn {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }} rounded-2 w-100">
                        {{ __('Choisir') }} {{ $name }}
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Billing Portal --}}
@if($activeSub)
<div class="alert alert-primary d-flex align-items-start gap-2" role="alert">
    <i data-lucide="settings" class="flex-shrink-0 mt-1"></i>
    <div class="d-flex flex-wrap align-items-center gap-2 w-100">
        <div class="flex-grow-1">
            <p class="fw-semibold mb-1">{{ __('Gérer votre facturation') }}</p>
            <p class="mb-0 text-sm">{{ __('Modifiez votre moyen de paiement, téléchargez vos factures ou annulez votre abonnement.') }}</p>
        </div>
        <a href="{{ route('billing.portal') }}" class="btn btn-primary rounded-2">
            <i data-lucide="credit-card" class="me-1"></i>
            {{ __('Portail de facturation') }}
        </a>
    </div>
</div>
@endif

@endsection
