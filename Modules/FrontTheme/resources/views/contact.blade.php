<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Contact') . ' - ' . config('app.name'))
@section('meta_description', __('Contactez l\'equipe de La veille pour toute question sur la plateforme de veille technologique.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Contactez-nous')])
@endsection

@section('content')
    <h1 class="sr-only">{{ __('Contact') }} — {{ config('app.name') }}</h1>
    <!-- start wpo-contact-pg-section -->
    <section class="wpo-contact-pg-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-10 offset-lg-1">
                    <div class="office-info">
                        <div class="row">
                            <div class="col col-xl-4 col-lg-6 col-md-6 col-12">
                                <div class="office-info-item">
                                    <div class="office-info-icon">
                                        <div class="icon">
                                            <i class="fi flaticon-email"></i>
                                        </div>
                                    </div>
                                    <div class="office-info-text">
                                        <h2>{{ __('Courriel') }}</h2>
                                        <p>{{ config('mail.from.address') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wpo-contact-title">
                        <h2>{{ __('Avez-vous des questions ?') }}</h2>
                        <p>{{ __('Remplissez le formulaire ci-dessous et nous vous répondrons dans les plus brefs délais.') }}</p>
                    </div>
                    <div class="wpo-contact-form-area">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <form method="post" class="contact-validation-active" action="{{ route('contact.send') }}" id="contact-form-main">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" placeholder="{{ __('Votre nom') }}" value="{{ old('name') }}" required>
                                        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-group">
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" placeholder="{{ __('Votre courriel') }}" value="{{ old('email') }}" required>
                                        @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-12 col-12">
                                    <div class="form-group">
                                        <input type="text" class="form-control @error('subject') is-invalid @enderror" name="subject" id="subject" placeholder="{{ __('Sujet') }}" value="{{ old('subject') }}" required>
                                        @error('subject') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-12 col-12">
                                    <div class="form-group">
                                        <textarea class="form-control @error('message') is-invalid @enderror" name="message" id="message" placeholder="{{ __('Message...') }}" required>{{ old('message') }}</textarea>
                                        @error('message') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-12 col-12">
                                    <div class="submit-area">
                                        <button type="submit" class="theme-btn">{{ __('Envoyer le message') }}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end wpo-contact-pg-section -->
@endsection
