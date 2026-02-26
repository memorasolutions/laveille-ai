@extends('fronttheme::themes.gosass.layouts.app')

@section('title', __('FAQ').' - '.config('app.name'))
@section('description', __('Questions fréquentes sur notre plateforme SaaS. Trouvez rapidement les réponses à vos questions.'))

@section('content')

<div class="cs_height_85 cs_height_lg_80"></div>
<div class="container text-center">
    <h2 class="cs_fs_50 cs_mb_15 wow fadeInDown">{{ __('Questions fréquentes') }}</h2>
    <p class="mb-0 wow fadeInUp">{{ __('Trouvez les réponses aux questions les plus courantes sur notre plateforme.') }}</p>
</div>

<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    @php
        $defaultFaqs = [
            ['question' => __('Comment démarrer avec la plateforme ?'), 'answer' => __('Créez un compte gratuitement, confirmez votre email et accédez immédiatement à votre tableau de bord. Vous pouvez commencer à utiliser toutes les fonctionnalités en quelques minutes.')],
            ['question' => __('Puis-je modifier mon plan à tout moment ?'), 'answer' => __('Oui, vous pouvez upgrader ou downgrader votre plan à tout moment depuis votre espace abonné. Les changements sont effectifs immédiatement et la facturation est ajustée au prorata.')],
            ['question' => __('Mes données sont-elles sécurisées ?'), 'answer' => __('Absolument. Toutes les données sont chiffrées en transit (TLS) et au repos. Nous appliquons les meilleures pratiques de sécurité : auth 2FA, audit logs, rate limiting, et politique de mots de passe renforcée.')],
            ['question' => __('Comment contacter le support ?'), 'answer' => __('Notre équipe support est disponible 24/7 via le formulaire de contact. Vous pouvez aussi consulter cette FAQ ou utiliser le chat en direct depuis votre tableau de bord.')],
            ['question' => __('L\'application est-elle compatible mobile ?'), 'answer' => __('Oui, l\'interface est entièrement responsive et optimisée pour tous les appareils : desktop, tablette et mobile.')],
            ['question' => __('Y a-t-il une période d\'essai gratuite ?'), 'answer' => __('Oui, nous proposons 14 jours d\'essai gratuit sans carte bancaire requise. Découvrez toutes les fonctionnalités avant de vous engager.')],
        ];
        $faqList = $faqs ?? collect($defaultFaqs)->map(fn($f) => (object)$f);
    @endphp

    <div class="row cs_gap_y_24 position-relative z-1">
        @foreach($faqList as $faq)
        <div class="col-xl-6 wow fadeIn" data-wow-delay="{{ $loop->index * 0.05 }}s">
            <div class="cs_accordian cs_radius_15 cs_white_bg cs_type_2 position-relative">
                <div class="cs_accordian_head cs_fs_21 cs_heading_color">
                    {{ $faq->question ?? $faq['question'] }}
                    <span class="cs_accordian_toggle position-absolute"></span>
                </div>
                <div class="cs_accordian_body">
                    {{ $faq->answer ?? $faq['answer'] }}
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="cs_height_60 cs_height_lg_40"></div>
    <div class="text-center">
        <p class="cs_fs_18 mb-3">{{ __('Vous ne trouvez pas la réponse que vous cherchez ?') }}</p>
        <a href="{{ route('contact.show') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_fs_16 cs_semibold cs_white_color cs_radius_30">
            <span>{{ __('Contactez-nous') }}</span>
            <span class="cs_btn_icon cs_center overflow-hidden"><i class="fa-solid fa-arrow-right"></i></span>
        </a>
    </div>
</div>
<div class="cs_height_140 cs_height_lg_80"></div>

@endsection
