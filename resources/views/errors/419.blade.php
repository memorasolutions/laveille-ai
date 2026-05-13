<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Session expirée') . ' - ' . config('app.name'))

@section('content')
<div style="display: flex; justify-content: center; align-items: center; min-height: 60vh; padding: 2rem; background: var(--c-surface, #F8FAFB);">
    <div style="max-width: 620px; width: 100%; text-align: center; background: #fff; padding: 2.5rem 2rem; border-radius: 14px; box-shadow: 0 10px 30px rgba(11, 114, 133, 0.08); border-top: 5px solid var(--c-accent, #C2410C);">
        <div style="width: 160px; height: 160px; margin: 0 auto 1.25rem;" aria-hidden="true">
            @include('errors.octopus._render', ['emotion' => 'sleeping'])
        </div>
        <p style="font-size: 0.8rem; font-weight: 700; color: var(--c-text-muted, #52586a); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 0.5rem;">{{ __('Erreur') }} 419</p>
        <h1 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); color: var(--c-dark, #1A1D23); font-size: 2rem; font-weight: 800; margin-bottom: 0.85rem; letter-spacing: -0.5px; line-height: 1.2;">{{ __('Session expirée') }}</h1>
        <p style="color: var(--c-text-secondary, #4a4f5c); font-size: 1.05rem; margin-bottom: 1.75rem; line-height: 1.6;">
            {{ __('Octopus s\'est assoupi. Votre session a expiré pour des raisons de sécurité — rien de cassé, on rafraîchit et c\'est reparti.') }}
        </p>
        <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; justify-content: center;">
            <a href="{{ url()->current() }}" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; background: var(--c-primary, #0B7285); color: #fff; padding: 0.75rem 1.4rem; border-radius: 8px; font-weight: 700; text-decoration: none; min-height: 44px; font-size: 0.95rem; border: 2px solid var(--c-primary, #0B7285);">🔄 {{ __('Rafraîchir la page') }}</a>
            @if(Route::has('login'))<a href="{{ route('login') }}" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; background: var(--c-chip-bg, #ECEEF2); color: var(--c-dark, #1A1D23); padding: 0.75rem 1.4rem; border-radius: 8px; font-weight: 700; text-decoration: none; min-height: 44px; font-size: 0.95rem; border: 2px solid var(--c-chip-bg, #ECEEF2);">{{ __('Se reconnecter') }}</a>@endif
        </div>
    </div>
</div>
@endsection
