<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Session expirée') . ' - ' . config('app.name'))

@section('content')
<div style="display: flex; justify-content: center; align-items: center; min-height: 60vh; padding: 2rem;">
    <div style="max-width: 600px; width: 100%; text-align: center; background: #fff; padding: 3rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border-top: 5px solid var(--c-accent, #E67E22);">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🍪⏰</div>
        <h1 style="font-family: var(--f-heading, sans-serif); color: var(--c-dark, #1a1d23); font-size: 2rem; margin-bottom: 1rem;">{{ __('Session expirée') }}</h1>
        <p style="color: #6B7280; font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.6;">
            {{ __('Votre session a expiré, un peu comme un cookie oublié trop longtemps. Pour des raisons de sécurité, nous avons dû vous déconnecter.') }}
        </p>
        <div style="display: flex; flex-direction: column; gap: 1rem; align-items: center;">
            <a href="{{ url()->current() }}" style="display: inline-block; background: var(--c-primary, #064E5A); color: #fff; padding: 12px 28px; border-radius: 50px; font-weight: 700; text-decoration: none;">🔄 {{ __('Rafraîchir la page') }}</a>
            @if(Route::has('login'))<a href="{{ route('login') }}" style="color: var(--c-primary, #064E5A); font-weight: 600; text-decoration: none;">{{ __('Se reconnecter') }}</a>@endif
        </div>
    </div>
</div>
@endsection
