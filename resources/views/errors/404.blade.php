<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Page introuvable') . ' - ' . config('app.name'))

@section('content')
<div style="display: flex; justify-content: center; align-items: center; min-height: 60vh; padding: 2rem;">
    <div style="max-width: 600px; width: 100%; text-align: center; background: #fff; padding: 3rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border-top: 5px solid var(--c-primary, #0B7285);">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🤖🔍</div>
        <h1 style="font-family: var(--f-heading, sans-serif); color: var(--c-dark, #1a1d23); font-size: 2rem; margin-bottom: 1rem;">{{ __('Page introuvable') }}</h1>
        <p style="color: #6B7280; font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.6;">
            {{ __('Notre IA a scanné tout le site, mais cette page semble avoir disparu dans une faille spatio-temporelle.') }}
        </p>
        <div style="border-top: 1px solid #E5E7EB; padding-top: 1.5rem;">
            <p style="font-size: 0.85rem; color: #9CA3AF; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">{{ __('Quelques pistes pour vous retrouver') }}</p>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 8px;">
                <a href="{{ url('/') }}" style="text-decoration: none; color: var(--c-dark, #333); background: #F3F4F6; padding: 8px 16px; border-radius: 50px; font-size: 14px; font-weight: 600;">🏠 {{ __('Accueil') }}</a>
                <a href="{{ url('/blog') }}" style="text-decoration: none; color: var(--c-dark, #333); background: #F3F4F6; padding: 8px 16px; border-radius: 50px; font-size: 14px; font-weight: 600;">📝 {{ __('Blog') }}</a>
                @if(Route::has('dictionary.index'))<a href="{{ route('dictionary.index') }}" style="text-decoration: none; color: var(--c-dark, #333); background: #F3F4F6; padding: 8px 16px; border-radius: 50px; font-size: 14px; font-weight: 600;">📚 {{ __('Glossaire') }}</a>@endif
                @if(Route::has('directory.index'))<a href="{{ route('directory.index') }}" style="text-decoration: none; color: var(--c-dark, #333); background: #F3F4F6; padding: 8px 16px; border-radius: 50px; font-size: 14px; font-weight: 600;">🔍 {{ __('Répertoire') }}</a>@endif
                @if(Route::has('tools.index'))<a href="{{ route('tools.index') }}" style="text-decoration: none; color: var(--c-dark, #333); background: #F3F4F6; padding: 8px 16px; border-radius: 50px; font-size: 14px; font-weight: 600;">🛠️ {{ __('Outils') }}</a>@endif
            </div>
        </div>
    </div>
</div>
@endsection
