@extends(fronttheme_layout())

@section('title', __('Connexion') . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Connexion')])
@endsection

@section('content')
<section class="section-padding" style="padding-top: 30px;">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
                <div style="background: #fff; border-radius: var(--r-base); padding: 40px; border: 1px solid #E5E7EB; box-shadow: 0 4px 15px rgba(0,0,0,0.06);">
                    <div style="text-align: center; margin-bottom: 24px;">
                        <div style="font-size: 40px; margin-bottom: 8px;">🔐</div>
                        <h1 style="font-family: var(--f-heading); font-weight: 800; font-size: 1.6rem; color: var(--c-dark); margin: 0 0 8px;">{{ __('Connexion') }}</h1>
                        <p style="color: #6B7280; font-size: 0.95rem; margin: 0;">{{ __('Entrez votre courriel pour recevoir un code à 6 chiffres.') }}</p>
                    </div>

                    @if(session('status'))
                        <div style="background: #D1FAE5; color: #065F46; padding: 12px 16px; border-radius: var(--r-base); font-size: 14px; margin-bottom: 16px;">
                            ✓ {{ session('status') }}
                            <a href="{{ route('magic-link.verify') }}?email={{ urlencode(old('email', '')) }}" style="display: block; margin-top: 6px; color: var(--c-primary); font-weight: 600;">
                                {{ __('Saisir mon code') }} →
                            </a>
                        </div>
                    @endif

                    @if(session('dev_magic_code'))
                        <div style="background: #FEF3C7; border: 1px solid #F59E0B; border-radius: var(--r-base); padding: 12px; margin-bottom: 16px;">
                            <strong style="color: #92400E;">DEV - Code :</strong>
                            <code style="font-size: 1.2rem; font-weight: 700; letter-spacing: 4px; color: #92400E;">{{ session('dev_magic_code') }}</code>
                        </div>
                    @endif

                    <form action="{{ route('magic-link.send') }}" method="POST">
                        @csrf
                        <div style="margin-bottom: 16px;">
                            <label for="magic-email" style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 6px; font-size: 14px;">{{ __('Adresse courriel') }}</label>
                            <input id="magic-email" name="email" type="email" autocomplete="email" required autofocus
                                placeholder="{{ __('vous@exemple.com') }}" value="{{ old('email') }}"
                                style="width: 100%; height: 48px; padding: 0 16px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 16px; outline: none; background: #F9FAFB; color: var(--c-dark);"
                                onfocus="this.style.borderColor='var(--c-primary)';this.style.background='#fff'" onblur="this.style.borderColor='#E5E7EB';this.style.background='#F9FAFB'">
                            @error('email')<p style="color: #DC2626; font-size: 13px; margin-top: 4px;">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit"
                            style="width: 100%; height: 48px; background: var(--c-primary); color: #fff; font-weight: 700; font-size: 16px; border: none; border-radius: var(--r-btn); cursor: pointer; transition: background 0.2s;"
                            onmouseover="this.style.background='var(--c-dark)'" onmouseout="this.style.background='var(--c-primary)'">
                            {{ __('Envoyer le code') }}
                        </button>
                    </form>

                    <div style="text-align: center; margin-top: 20px; padding-top: 16px; border-top: 1px solid #F3F4F6;">
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" style="color: var(--c-primary); font-weight: 600; font-size: 14px; text-decoration: none;">
                            ← {{ __('Retour') }}
                        </a>
                    </div>

                    {{-- Lien admin subtil --}}
                    <div style="text-align: center; margin-top: 16px;">
                        <a href="{{ route('login') }}" style="color: #D1D5DB; font-size: 11px; text-decoration: none;">{{ __('Administration') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
