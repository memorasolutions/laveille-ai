@extends(fronttheme_layout())

@section('title', __('Vérification du code') . ' - ' . config('app.name'))

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
                        <div style="font-size: 40px; margin-bottom: 8px;">✉️</div>
                        <h1 style="font-family: var(--f-heading); font-weight: 800; font-size: 1.6rem; color: var(--c-dark); margin: 0 0 8px;">{{ __('Entrez votre code') }}</h1>
                        <p style="color: #6B7280; font-size: 0.95rem; margin: 0;">
                            {{ __('Un code à 6 chiffres a été envoyé à') }} <strong style="color: var(--c-dark);">{{ $email }}</strong>.
                            {{ __('Valide') }} {{ $expiryMinutes }} {{ __('minutes.') }}
                        </p>
                    </div>

                    @if(session('dev_magic_code'))
                        <div style="background: #FEF3C7; border: 1px solid #F59E0B; border-radius: var(--r-base); padding: 12px; margin-bottom: 16px; text-align: center;">
                            <strong style="color: #92400E;">DEV - Code :</strong>
                            <code style="font-size: 1.4rem; font-weight: 700; letter-spacing: 6px; color: #92400E;">{{ session('dev_magic_code') }}</code>
                        </div>
                    @endif

                    <form action="{{ route('magic-link.confirm') }}" method="POST">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">

                        <div style="margin-bottom: 16px;">
                            <label for="otp-code" style="display: block; font-weight: 600; color: var(--c-dark); margin-bottom: 6px; font-size: 14px;">{{ __('Code à 6 chiffres') }}</label>
                            <input id="otp-code" name="token" type="text" maxlength="6" autocomplete="one-time-code" inputmode="numeric" required autofocus
                                placeholder="000000"
                                style="width: 100%; height: 56px; padding: 0 16px; border: 2px solid #E5E7EB; border-radius: var(--r-base); font-size: 28px; font-weight: 700; letter-spacing: 10px; text-align: center; outline: none; background: #F9FAFB; color: var(--c-dark);"
                                onfocus="this.style.borderColor='var(--c-primary)';this.style.background='#fff'" onblur="this.style.borderColor='#E5E7EB';this.style.background='#F9FAFB'">
                            @error('token')<p style="color: #DC2626; font-size: 13px; margin-top: 4px;">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit"
                            style="width: 100%; height: 48px; background: var(--c-primary); color: #fff; font-weight: 700; font-size: 16px; border: none; border-radius: var(--r-btn); cursor: pointer; transition: background 0.2s;"
                            onmouseover="this.style.background='var(--c-dark)'" onmouseout="this.style.background='var(--c-primary)'">
                            {{ __('Valider et me connecter') }}
                        </button>
                    </form>

                    @if($hasPhone ?? false)
                        <div style="text-align: center; margin-top: 16px;">
                            <form action="{{ route('magic-link.sms') }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="email" value="{{ $email }}">
                                <button type="submit" style="background: none; border: none; color: var(--c-primary); font-weight: 600; font-size: 13px; cursor: pointer;">
                                    📱 {{ __('Recevoir par SMS') }}
                                </button>
                            </form>
                            @if(session('sms_sent'))
                                <span style="color: #065F46; font-size: 12px;">✓ {{ session('sms_sent') }}</span>
                            @endif
                        </div>
                    @endif

                    <div style="text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #F3F4F6;">
                        <a href="{{ route('magic-link.request') }}" style="color: var(--c-primary); font-weight: 600; font-size: 14px; text-decoration: none;">
                            ← {{ __('Demander un nouveau code') }}
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
