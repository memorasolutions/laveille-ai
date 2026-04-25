<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Contact') . ' - ' . config('app.name'))
@section('meta_description', __('Contactez l\'équipe de La veille pour toute question sur la plateforme de veille technologique.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Contactez-nous')])
@endsection

@section('content')
    <h1 class="sr-only">{{ __('Contact') }} — {{ config('app.name') }}</h1>

    <section style="padding: 50px 0 60px; background: #fff;">
        <div class="container">
            <div style="max-width: 700px; margin: 0 auto;">

                <div style="text-align: center; margin-bottom: 28px;">
                    <h2 style="margin: 0 0 8px; font-weight: 700; font-size: clamp(22px, 3.2vw, 30px); line-height: 1.2; color: var(--c-dark);">
                        {{ __('Avez-vous des questions?') }}
                    </h2>
                    <p style="color: #374151; margin: 0; font-size: 15px;">
                        {{ __('Remplissez le formulaire ci-dessous et nous vous répondrons dans les plus brefs délais.') }}
                    </p>
                </div>

                @if(session('success'))
                    <div class="alert alert-success" role="alert" style="border-radius: 8px; margin-bottom: 16px;">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger" role="alert" style="border-radius: 8px; margin-bottom: 16px;">
                        {{ __('Veuillez corriger les erreurs ci-dessous.') }}
                    </div>
                @endif

                <div style="background: #f9fafb; border: 1px solid rgba(0,0,0,.06); border-radius: 12px; padding: 24px;">
                    <form method="POST" action="{{ route('contact.send') }}" id="contact-form-main">
                        @csrf

                        <div class="row" style="margin-left: -8px; margin-right: -8px;">
                            <div class="col-sm-6" style="padding-left: 8px; padding-right: 8px;">
                                <div class="form-group" style="margin-bottom: 14px;">
                                    <label for="name" style="font-weight: 600; color: #111827; font-size: 14px; margin-bottom: 4px;">{{ __('Nom') }} *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autocomplete="name" style="border-radius: 8px; height: 44px; box-shadow: none;">
                                    @error('name') <span style="color: #dc2626; font-size: 12px;">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-sm-6" style="padding-left: 8px; padding-right: 8px;">
                                <div class="form-group" style="margin-bottom: 14px;">
                                    <label for="email" style="font-weight: 600; color: #111827; font-size: 14px; margin-bottom: 4px;">{{ __('Courriel') }} *</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" style="border-radius: 8px; height: 44px; box-shadow: none;">
                                    @error('email') <span style="color: #dc2626; font-size: 12px;">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 14px;">
                            <label for="subject" style="font-weight: 600; color: #111827; font-size: 14px; margin-bottom: 4px;">{{ __('Sujet') }} *</label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required style="border-radius: 8px; height: 44px; box-shadow: none;">
                            @error('subject') <span style="color: #dc2626; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group" style="margin-bottom: 18px;">
                            <label for="message" style="font-weight: 600; color: #111827; font-size: 14px; margin-bottom: 4px;">{{ __('Message') }} *</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6" required style="border-radius: 8px; min-height: 150px; resize: vertical; box-shadow: none;">{{ old('message') }}</textarea>
                            @error('message') <span style="color: #dc2626; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="ct-btn ct-btn-primary" style="text-transform: none; border-radius: 8px; height: 44px; padding: 0 24px; line-height: 44px;">
                            {{ __('Envoyer le message') }}
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>
@endsection
