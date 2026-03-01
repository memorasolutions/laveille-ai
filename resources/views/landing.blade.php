@extends('fronttheme::themes.gosass.layouts.app')

@section('title', config('app.name').' - '.__('Plateforme SaaS Laravel 12'))
@section('description', config('app.name').' - '.__('Solution SaaS complète et modulaire. Sécurité, performance et simplicité pour votre projet web.'))

@section('content')

{{-- Hero Section --}}
<section class="cs_hero cs_style_1 cs_type_3 position-relative" id="home">
    <div class="cs_height_100 cs_height_lg_80"></div>
    <div class="container">
        <div class="cs_hero_content position-relative">
            <div class="cs_hero_text position-relative z-2 text-center">
                <h1 class="cs_hero_title cs_fs_68 wow fadeInDown">
                    {{ __('Votre plateforme') }} <span class="cs_accent_color">{{ __('SaaS Laravel 12') }}</span>
                </h1>
                <p class="cs_hero_subtitle wow fadeInUp mt-3 mb-4">
                    {{ __('Modules indépendants, sécurité renforcée et performance optimisée.') }}<br>
                    {{ __('Déployez rapidement votre produit avec une base robuste et testée.') }}
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap wow fadeInUp">
                    <a href="{{ route('register') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_fs_16 cs_semibold cs_white_color cs_radius_30">
                        <span>{{ __('Commencer gratuitement') }}</span>
                        <span class="cs_btn_icon cs_center overflow-hidden">
                            <i class="fa-solid fa-arrow-right"></i>
                        </span>
                    </a>
                    <a href="{{ route('login') }}" class="cs_btn cs_style_1 cs_heading_bg cs_white_color cs_fs_16 cs_semibold cs_radius_30">
                        <span>{{ __('Se connecter') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="cs_height_100 cs_height_lg_80"></div>
</section>

{{-- Features Section --}}
<section>
    <div class="cs_height_100 cs_height_lg_80"></div>
    <div class="container">
        <div class="text-center">
            <p class="cs_section_subtitle cs_mb_23">{{ __('FONCTIONNALITÉS') }} <span class="cs_pill"></span></p>
            <h2 class="cs_section_title cs_fs_50 mb-0 wow fadeInDown">{{ __('Tout ce dont vous avez besoin') }}</h2>
        </div>
        <div class="cs_height_64 cs_height_lg_50"></div>
        <div class="row cs_gap_y_24">
            <div class="col-lg-4 wow fadeInLeft">
                <div class="cs_iconbox cs_style_1 cs_gray_bg_1 cs_radius_10">
                    <span class="cs_iconbox_icon cs_mb_23">
                        <i class="fa-solid fa-puzzle-piece fa-2x cs_accent_color"></i>
                    </span>
                    <div class="cs_iconbox_info">
                        <h3 class="cs_iconbox_title cs_fs_29 cs_mb_10">{{ __('Modules indépendants') }}</h3>
                        <p class="cs_iconbox_subtitle mb-0">{{ __('Architecture modulaire Laravel Modules. Activez uniquement ce dont vous avez besoin.') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 wow fadeInDown">
                <div class="cs_iconbox cs_style_1 cs_gray_bg_1 cs_radius_10">
                    <span class="cs_iconbox_icon cs_mb_23">
                        <i class="fa-solid fa-shield-halved fa-2x cs_accent_color"></i>
                    </span>
                    <div class="cs_iconbox_info">
                        <h3 class="cs_iconbox_title cs_fs_29 cs_mb_10">{{ __('Sécurité renforcée') }}</h3>
                        <p class="cs_iconbox_subtitle mb-0">{{ __('Auth 2FA, rôles et permissions, audit logs, rate limiting. Sécurité par défaut.') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 wow fadeInRight">
                <div class="cs_iconbox cs_style_1 cs_gray_bg_1 cs_radius_10">
                    <span class="cs_iconbox_icon cs_mb_23">
                        <i class="fa-solid fa-bolt fa-2x cs_accent_color"></i>
                    </span>
                    <div class="cs_iconbox_info">
                        <h3 class="cs_iconbox_title cs_fs_29 cs_mb_10">{{ __('Performance optimisée') }}</h3>
                        <p class="cs_iconbox_subtitle mb-0">{{ __('Livewire, Alpine.js, cache optimisé. Des temps de réponse ultra-rapides.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cs_height_100 cs_height_lg_80"></div>
</section>

{{-- Pricing Section --}}
@if(isset($plans) && $plans->count() > 0)
<section class="cs_gray_bg_4">
    <div class="cs_height_100 cs_height_lg_80"></div>
    <div class="container">
        <div class="text-center">
            <p class="cs_section_subtitle cs_mb_23">{{ __('TARIFS') }} <span class="cs_pill"></span></p>
            <h2 class="cs_section_title cs_fs_50 mb-0 wow fadeInDown">{{ __('Choisissez votre offre') }}</h2>
        </div>
        <div class="cs_height_64 cs_height_lg_50"></div>
        <div class="row cs_gap_y_24 justify-content-center">
            @foreach($plans as $plan)
            <div class="col-lg-4 col-md-6 wow fadeInUp">
                <div class="cs_pricing cs_style_1 cs_radius_15 cs_white_bg p-4 text-center">
                    <h3 class="cs_fs_29 cs_mb_10">{{ $plan->name }}</h3>
                    <div class="cs_pricing_price cs_fs_50 cs_accent_color cs_semibold mb-3">
                        {{ is_numeric($plan->price) ? number_format($plan->price, 0).'€' : $plan->price }}
                        <span class="cs_fs_16 cs_heading_color">{{ __('/mois') }}</span>
                    </div>
                    @if($plan->description)
                    <p class="mb-4">{{ $plan->description }}</p>
                    @endif
                    @if(auth()->check() && !empty($plan->stripe_price_id) && $plan->price > 0)
                    <form action="{{ route('checkout') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_fs_16 cs_semibold cs_white_color cs_radius_30 border-0">
                            <span>{{ __('Choisir ce plan') }}</span>
                        </button>
                    </form>
                    @elseif(auth()->check() && $plan->price > 0)
                    <a href="{{ route('user.subscription') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_fs_16 cs_semibold cs_white_color cs_radius_30">
                        <span>{{ __('Gérer mon abonnement') }}</span>
                    </a>
                    @else
                    <a href="{{ route('register') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_fs_16 cs_semibold cs_white_color cs_radius_30">
                        <span>{{ __('Commencer') }}</span>
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="cs_height_100 cs_height_lg_80"></div>
</section>
@endif

{{-- Blog recent --}}
@if(isset($recentPosts) && $recentPosts->count() > 0)
<section>
    <div class="cs_height_100 cs_height_lg_80"></div>
    <div class="container">
        <div class="text-center">
            <p class="cs_section_subtitle cs_mb_23">{{ __('BLOG') }} <span class="cs_pill"></span></p>
            <h2 class="cs_section_title cs_fs_50 mb-0 wow fadeInDown">{{ __('Derniers articles') }}</h2>
        </div>
        <div class="cs_height_64 cs_height_lg_50"></div>
        <div class="row cs_gap_y_40">
            @foreach($recentPosts->take(3) as $post)
            <div class="col-lg-4 wow fadeInUp">
                <article class="cs_post cs_style_1">
                    @if($post->cover_image)
                    <div class="cs_post_thumbnail cs_radius_15 overflow-hidden mb-3">
                        <img src="{{ Storage::url($post->cover_image) }}" alt="{{ $post->title }}" class="w-100" style="height:200px;object-fit:cover">
                    </div>
                    @endif
                    <div class="cs_post_info">
                        <p class="cs_post_date mb-2 cs_fs_14">{{ $post->created_at->format('d M Y') }}</p>
                        <h3 class="cs_fs_21 cs_mb_10">
                            <a href="{{ route('blog.show', $post->slug) }}" class="cs_heading_color text-decoration-none">{{ $post->title }}</a>
                        </h3>
                        @if($post->excerpt)
                        <p class="mb-0 cs_fs_16">{{ Str::limit($post->excerpt, 100) }}</p>
                        @endif
                    </div>
                </article>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-5">
            <a href="{{ route('blog.index') }}" class="cs_btn cs_style_1 cs_heading_bg cs_white_color cs_fs_16 cs_semibold cs_radius_30">
                <span>{{ __('Voir tous les articles') }}</span>
                <span class="cs_btn_icon cs_center overflow-hidden"><i class="fa-solid fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
    <div class="cs_height_100 cs_height_lg_80"></div>
</section>
@endif

{{-- CTA Section --}}
<section class="cs_gray_bg_4">
    <div class="container">
        <div class="cs_support_content_wrapper">
            <div class="cs_support_text">
                <h3 class="cs_fs_29 cs_normal mb-0">{{ __('Prêt à lancer votre projet SaaS ?') }}</h3>
            </div>
            <a href="{{ route('register') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_fs_16 cs_white_color cs_semibold mt-0 wow fadeInRight cs_radius_30">
                <span>{{ __('Commencer gratuitement') }}</span>
                <span class="cs_btn_icon cs_center overflow-hidden"><i class="fa-solid fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
</section>

@endsection
