<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('fronttheme::themes.gosass.layouts.app')

@section('title', __('Contact').' - '.config('app.name'))
@section('description', __('Contactez-nous pour toute question sur nos services. Notre équipe vous répond rapidement.'))

@section('content')

<div class="cs_height_58 cs_height_lg_58"></div>
<div class="container text-center">
    <h2 class="cs_fs_50 cs_mb_15 wow fadeInDown">{{ __('Contactez-nous') }}</h2>
    <p class="mb-0 wow fadeInUp">{{ __('Une question ? Nous sommes là pour vous aider.') }}</p>
</div>

<div class="cs_height_97 cs_height_lg_50"></div>
<div class="container">
    <div class="row cs_gap_y_40">
        <div class="col-xl-5 col-lg-5">
            <div class="cs_section_heading cs_style_1">
                <p class="cs_section_subtitle cs_mb_23">{{ __('ÉCRIVEZ-NOUS') }} <span class="cs_pill"></span></p>
                <h2 class="cs_section_title cs_fs_50 mb-0 wow fadeInDown">{{ __('Remplissez ce formulaire pour nous contacter.') }}</h2>
            </div>
        </div>
        <div class="col-xl-6 col-lg-7 offset-xl-1">

            @if(session('success'))
                <div class="alert alert-success cs_radius_10 mb-4">
                    <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger cs_radius_10 mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('contact.send') }}" class="cs_contact_form row cs_gap_y_24">
                @csrf
                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                    <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                </div>
                <div class="col-sm-6">
                    <label for="name">{{ __('Nom complet') }} *</label>
                    <input type="text" name="name" id="name" class="cs_form_field cs_radius_30" value="{{ old('name') }}" required>
                </div>
                <div class="col-sm-6">
                    <label for="email">{{ __('Courriel') }} *</label>
                    <input type="email" name="email" id="email" class="cs_form_field cs_radius_30" value="{{ old('email') }}" required>
                </div>
                <div class="col-sm-12">
                    <label for="subject">{{ __('Sujet') }} *</label>
                    <input type="text" name="subject" id="subject" class="cs_form_field cs_radius_30" value="{{ old('subject') }}" required>
                </div>
                <div class="col-sm-12">
                    <label for="message">{{ __('Message') }} *</label>
                    <textarea name="message" id="message" rows="6" class="cs_form_field cs_radius_30" required>{{ old('message') }}</textarea>
                </div>
                <div class="col-sm-12">
                    <button type="submit" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_fs_16 cs_semibold cs_white_color cs_radius_30 wow fadeInUp">
                        <span>{{ __('Envoyer le message') }}</span>
                        <span class="cs_btn_icon cs_center overflow-hidden">
                            <i class="fa-solid fa-arrow-right"></i>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="cs_height_140 cs_height_lg_80"></div>

@endsection
