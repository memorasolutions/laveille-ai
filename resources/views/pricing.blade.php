@extends('fronttheme::themes.gosass.layouts.app')

@section('title', __('Tarifs').' - '.config('app.name'))
@section('description', __('Découvrez nos plans flexibles et abordables. Choisissez l\'offre qui correspond à vos besoins.'))

@php
$featureLabels = [
    '1_user' => __('1 utilisateur'),
    '10_users' => __('10 utilisateurs'),
    'unlimited_users' => __('Utilisateurs illimités'),
    'basic_support' => __('Support par courriel'),
    'priority_support' => __('Support prioritaire'),
    'dedicated_support' => __('Support dédié 24/7'),
    '1gb_storage' => __('1 Go de stockage'),
    '50gb_storage' => __('50 Go de stockage'),
    'unlimited_storage' => __('Stockage illimité'),
    'api_access' => __('Accès API complet'),
    'export' => __('Export de données'),
    'webhooks' => __('Webhooks personnalisés'),
    'sso' => __('Authentification SSO'),
];
@endphp

@section('content')

{{-- Hero --}}
<section class="cs_hero cs_style_1 position-relative">
    <div class="cs_height_100 cs_height_lg_80"></div>
    <div class="container">
        <div class="text-center">
            <p class="cs_section_subtitle cs_mb_23">{{ __('TARIFS') }} <span class="cs_pill"></span></p>
            <h1 class="cs_section_title cs_fs_68 mb-0 wow fadeInDown">
                {{ __('Des offres') }} <span class="cs_accent_color">{{ __('simples et transparentes') }}</span>
            </h1>
            <p class="cs_hero_subtitle wow fadeInUp mt-3 mb-0">
                {{ __('Commencez gratuitement, évoluez à votre rythme. Sans engagement.') }}
            </p>
        </div>
    </div>
    <div class="cs_height_64 cs_height_lg_50"></div>
</section>

{{-- Plans --}}
<section class="cs_gray_bg_5">
    <div class="cs_height_100 cs_height_lg_80"></div>
    <div class="container">
        <div class="row cs_gap_y_24 justify-content-center">
            @foreach($plans as $index => $plan)
            @php
                $isHighlighted = $plan->slug === 'pro';
            @endphp
            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{{ $index * 0.1 }}s">
                <div class="cs_radius_15 p-4 h-100 d-flex flex-column {{ $isHighlighted ? 'cs_accent_bg' : 'cs_white_bg' }}" style="border: {{ $isHighlighted ? 'none' : '1px solid #eee' }}">
                    @if($isHighlighted)
                    <div class="text-center mb-3">
                        <span class="badge bg-white text-dark px-3 py-2 cs_radius_30 cs_semibold">{{ __('Populaire') }}</span>
                    </div>
                    @endif

                    <h3 class="cs_fs_29 cs_semibold text-center {{ $isHighlighted ? 'cs_white_color' : '' }}">{{ $plan->name }}</h3>

                    <div class="text-center my-4">
                        <span class="cs_fs_50 cs_semibold {{ $isHighlighted ? 'cs_white_color' : 'cs_accent_color' }}">
                            {{ number_format($plan->price, 0) }}$
                        </span>
                        <span class="{{ $isHighlighted ? 'cs_white_color' : 'cs_heading_color' }} cs_fs_16"> CAD/mois</span>
                    </div>

                    @if($plan->description)
                    <p class="text-center mb-4 {{ $isHighlighted ? 'cs_white_color' : '' }}">{{ $plan->description }}</p>
                    @endif

                    @if($plan->trial_days > 0)
                    <p class="text-center mb-3 cs_fs_14 {{ $isHighlighted ? 'cs_white_color' : 'text-muted' }}">
                        <i class="fa-solid fa-gift me-1"></i> {{ $plan->trial_days }} {{ __('jours d\'essai gratuit') }}
                    </p>
                    @endif

                    <ul class="list-unstyled flex-grow-1 mb-4">
                        @foreach($plan->features ?? [] as $feature)
                        <li class="d-flex align-items-center mb-3">
                            <i class="fa-solid fa-check me-2 {{ $isHighlighted ? 'cs_white_color' : 'cs_accent_color' }}"></i>
                            <span class="{{ $isHighlighted ? 'cs_white_color' : '' }}">{{ $featureLabels[$feature] ?? ucfirst(str_replace('_', ' ', $feature)) }}</span>
                        </li>
                        @endforeach
                    </ul>

                    <div class="text-center mt-auto">
                        @if($plan->price == 0)
                        <a href="{{ route('register') }}" class="cs_btn cs_style_1 {{ $isHighlighted ? 'cs_white_bg cs_accent_color' : 'cs_accent_bg cs_white_color' }} cs_fs_16 cs_semibold cs_radius_30 w-100 justify-content-center">
                            <span>{{ __('Commencer gratuitement') }}</span>
                        </a>
                        @elseif(auth()->check() && !empty($plan->stripe_price_id))
                        <form action="{{ route('checkout') }}" method="POST" class="d-inline w-100">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="cs_btn cs_style_1 {{ $isHighlighted ? 'cs_white_bg cs_accent_color' : 'cs_accent_bg cs_white_color' }} cs_fs_16 cs_semibold cs_radius_30 w-100 justify-content-center border-0">
                                <span>{{ __('Choisir ce plan') }}</span>
                            </button>
                        </form>
                        @elseif(auth()->check())
                        <a href="{{ route('user.subscription') }}" class="cs_btn cs_style_1 {{ $isHighlighted ? 'cs_white_bg cs_accent_color' : 'cs_accent_bg cs_white_color' }} cs_fs_16 cs_semibold cs_radius_30 w-100 justify-content-center">
                            <span>{{ __('Gérer mon abonnement') }}</span>
                        </a>
                        @else
                        <a href="{{ route('register') }}" class="cs_btn cs_style_1 {{ $isHighlighted ? 'cs_white_bg cs_accent_color' : 'cs_accent_bg cs_white_color' }} cs_fs_16 cs_semibold cs_radius_30 w-100 justify-content-center">
                            <span>{{ __('Choisir ce plan') }}</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="cs_height_100 cs_height_lg_80"></div>
</section>

{{-- FAQ Pricing --}}
<section>
    <div class="cs_height_100 cs_height_lg_80"></div>
    <div class="container">
        <div class="text-center">
            <p class="cs_section_subtitle cs_mb_23">{{ __('FAQ') }} <span class="cs_pill"></span></p>
            <h2 class="cs_section_title cs_fs_50 mb-0 wow fadeInDown">{{ __('Questions fréquentes') }}</h2>
        </div>
        <div class="cs_height_64 cs_height_lg_50"></div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="pricingFaq">
                    <div class="accordion-item cs_radius_10 mb-3 border">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed cs_semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                {{ __('Puis-je changer de plan à tout moment ?') }}
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                            <div class="accordion-body">
                                {{ __('Oui, vous pouvez passer à un plan supérieur ou inférieur à tout moment depuis votre tableau de bord. Le changement prend effet immédiatement et la facturation est ajustée au prorata.') }}
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item cs_radius_10 mb-3 border">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed cs_semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                {{ __('Y a-t-il un engagement minimum ?') }}
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                            <div class="accordion-body">
                                {{ __('Non, tous nos plans sont sans engagement. Vous pouvez annuler votre abonnement à tout moment. Votre accès reste actif jusqu\'à la fin de la période facturée.') }}
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item cs_radius_10 mb-3 border">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed cs_semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                {{ __('Quels moyens de paiement acceptez-vous ?') }}
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                            <div class="accordion-body">
                                {{ __('Nous acceptons les cartes Visa, Mastercard et American Express via Stripe. Toutes les transactions sont sécurisées et chiffrées.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cs_height_100 cs_height_lg_80"></div>
</section>

{{-- CTA --}}
<section class="cs_gray_bg_5">
    <div class="cs_height_100 cs_height_lg_80"></div>
    <div class="container">
        <div class="cs_support_content_wrapper">
            <div class="cs_support_text">
                <h3 class="cs_fs_29 cs_normal mb-0">{{ __('Une question sur nos tarifs ?') }}</h3>
            </div>
            <a href="{{ route('contact.show') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_fs_16 cs_white_color cs_semibold mt-0 wow fadeInRight cs_radius_30">
                <span>{{ __('Contactez-nous') }}</span>
                <span class="cs_btn_icon cs_center overflow-hidden"><i class="fa-solid fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
    <div class="cs_height_100 cs_height_lg_80"></div>
</section>

@endsection
