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

<div class="d-flex flex-wrap align-items-center justify-content-between gap-12 mb-24">
    <div>
        <h1 class="fw-semibold mb-4">{{ __('Mon abonnement') }}</h1>
        <p class="text-secondary-light mb-0">{{ __('Gérez votre plan et votre facturation.') }}</p>
    </div>
    <span class="badge text-sm fw-semibold px-16 py-9 radius-4
        {{ $planName === 'Free' ? 'bg-neutral-focus text-neutral-main' : 'bg-primary-100 text-primary-600' }}">
        <iconify-icon icon="{{ $planName === 'Free' ? 'solar:box-outline' : 'solar:crown-outline' }}"></iconify-icon>
        Plan {{ $planName }}
    </span>
</div>

{{-- Plan actuel --}}
<div class="card mb-24">
    <div class="card-header">
        <h5 class="card-title fw-semibold text-lg mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:receipt-outline" class="text-primary-600"></iconify-icon>
            {{ __('Plan actuel') }}
        </h5>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-24">
            <div class="d-flex align-items-center gap-12">
                <div class="w-48-px h-48-px rounded-12 d-flex align-items-center justify-content-center
                    {{ $planName === 'Free' ? 'bg-neutral-focus' : 'bg-primary-100' }}">
                    <iconify-icon icon="{{ $planName === 'Free' ? 'solar:box-outline' : 'solar:crown-outline' }}"
                                  class="{{ $planName === 'Free' ? 'text-neutral-main' : 'text-primary-600' }} text-2xl"></iconify-icon>
                </div>
                <div>
                    <p class="fw-semibold text-lg mb-4">{{ $planName }}</p>
                    @if($currentPlan && $currentPlan->price > 0)
                        <p class="text-secondary-light text-sm mb-0">
                            {{ number_format($currentPlan->price, 2) }} {{ strtoupper($currentPlan->currency ?? 'CAD') }} /
                            {{ $currentPlan->interval === 'yearly' ? 'an' : 'mois' }}
                        </p>
                    @else
                        <p class="text-secondary-light text-sm mb-0">{{ __('Gratuit') }}</p>
                    @endif
                </div>
            </div>

            @if($activeSub)
                <span class="badge text-sm fw-semibold px-16 py-9 radius-4 bg-success-focus text-success-main d-flex align-items-center gap-4">
                    <span style="width:8px;height:8px;background:currentColor;border-radius:50%;display:inline-block;"></span>
                    {{ $activeSub->stripe_status === 'trialing' ? __("Période d'essai") : __('Actif') }}
                </span>
            @else
                <span class="badge text-sm fw-semibold px-16 py-9 radius-4 bg-neutral-focus text-neutral-main">
                    {{ __('Plan gratuit') }}
                </span>
            @endif

            @if($activeSub && !empty($activeSub->current_period_end))
                <p class="text-secondary-light text-sm mb-0">
                    <iconify-icon icon="solar:calendar-outline" class="me-4"></iconify-icon>
                    {{ __('Renouvellement') }} : {{ \Carbon\Carbon::parse($activeSub->current_period_end)->format('d M Y') }}
                </p>
            @endif
        </div>
    </div>
</div>

{{-- Comparatif plans --}}
<h2 class="fw-semibold text-xl mb-20">{{ __('Comparer les plans') }}</h2>

<div class="row gy-4 mb-24">
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
        <div class="card h-100 position-relative {{ $isCurrent ? 'border border-primary-600' : '' }}">

            @if($isPopular && ! $isCurrent)
                <div class="position-absolute start-50 translate-middle-x"
                     style="top:-14px; z-index:2;">
                    <span class="badge text-sm fw-semibold px-16 py-6 radius-20 bg-primary-600 text-white">{{ __('Populaire') }}</span>
                </div>
            @endif
            @if($isCurrent)
                <div class="position-absolute start-50 translate-middle-x"
                     style="top:-14px; z-index:2;">
                    <span class="badge text-sm fw-semibold px-16 py-6 radius-20 bg-success-600 text-white">{{ __('Plan actuel') }}</span>
                </div>
            @endif

            <div class="card-body d-flex flex-column pt-28">
                <div class="mb-16">
                    <h4 class="fw-semibold mb-8">{{ $name }}</h4>
                    @if($price > 0)
                        <span class="fw-bold text-heading" style="font-size:2rem;">{{ $price }}$</span>
                        <span class="text-secondary-light text-sm"> / {{ __('mois') }}</span>
                    @else
                        <span class="fw-bold text-heading" style="font-size:2rem;">{{ __('Gratuit') }}</span>
                    @endif
                </div>

                <ul class="list-unstyled mb-20 flex-grow-1">
                    @foreach($features as $feature)
                    <li class="d-flex align-items-start gap-8 mb-8 text-sm text-secondary-light">
                        <iconify-icon icon="solar:check-circle-outline" class="text-success-600 flex-shrink-0 mt-2"></iconify-icon>
                        {{ is_string($feature) ? ucfirst(str_replace('_', ' ', $feature)) : $feature }}
                    </li>
                    @endforeach
                </ul>

                @if($isCurrent)
                    <button disabled class="btn btn-outline-secondary radius-8 w-100">{{ __('Plan actuel') }}</button>
                @elseif(!is_array($plan) && $plan->stripe_price_id)
                    <form action="{{ route('checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="btn {{ $isPopular ? 'btn-primary-600' : 'btn-outline-primary-600' }} radius-8 w-100">
                            {{ __('Choisir') }} {{ $name }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('pricing') }}" class="btn {{ $isPopular ? 'btn-primary-600' : 'btn-outline-primary-600' }} radius-8 w-100">
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
<div class="alert alert-primary d-flex align-items-start gap-12" role="alert">
    <iconify-icon icon="solar:settings-outline" class="text-xl flex-shrink-0 mt-2"></iconify-icon>
    <div class="d-flex flex-wrap align-items-center gap-12 w-100">
        <div class="flex-grow-1">
            <p class="fw-semibold mb-4">{{ __('Gérer votre facturation') }}</p>
            <p class="mb-0 text-sm">{{ __('Modifiez votre moyen de paiement, téléchargez vos factures ou annulez votre abonnement.') }}</p>
        </div>
        <a href="{{ route('billing.portal') }}" class="btn btn-primary-600 radius-8">
            <iconify-icon icon="solar:card-outline" class="me-4"></iconify-icon>
            {{ __('Portail de facturation') }}
        </a>
    </div>
</div>
@endif

@endsection
