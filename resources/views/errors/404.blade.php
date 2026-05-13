<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Page introuvable') . ' - ' . config('app.name'))

@section('content')
<div style="display: flex; justify-content: center; align-items: center; min-height: 60vh; padding: 2rem; background: var(--c-surface, #F8FAFB);">
    <div style="max-width: 620px; width: 100%; text-align: center; background: #fff; padding: 2.5rem 2rem; border-radius: 14px; box-shadow: 0 10px 30px rgba(11, 114, 133, 0.08); border-top: 5px solid var(--c-primary, #0B7285);">
        <div style="width: 160px; height: 160px; margin: 0 auto 1.25rem;" aria-hidden="true">
            @include('errors.octopus._render', ['emotion' => 'confused'])
        </div>
        <p style="font-size: 0.8rem; font-weight: 700; color: var(--c-text-muted, #52586a); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 0.5rem;">{{ __('Erreur') }} 404</p>
        <h1 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); color: var(--c-dark, #1A1D23); font-size: 2rem; font-weight: 800; margin-bottom: 0.85rem; letter-spacing: -0.5px; line-height: 1.2;">{{ __('Page introuvable') }}</h1>
        <p style="color: var(--c-text-secondary, #4a4f5c); font-size: 1.05rem; margin-bottom: 1.75rem; line-height: 1.6;">
            {{ __('Cette page s\'est perdue dans les courants. Octopus n\'a rien trouvé à cet endroit, mais voici quelques pistes pour vous retrouver.') }}
        </p>
        <div style="border-top: 1px solid var(--c-border, #E5E7EB); padding-top: 1.5rem;">
            <p style="font-size: 0.78rem; color: var(--c-text-muted, #52586a); margin-bottom: 0.85rem; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif);">{{ __('Quelques pistes pour vous retrouver') }}</p>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 8px;">
                <a href="{{ url('/') }}" style="text-decoration: none; color: var(--c-dark, #1A1D23); background: var(--c-chip-bg, #ECEEF2); padding: 8px 16px; border-radius: 999px; font-size: 14px; font-weight: 700; min-height: 40px; display: inline-flex; align-items: center; gap: 6px;">🏠 {{ __('Accueil') }}</a>
                <a href="{{ url('/blog') }}" style="text-decoration: none; color: var(--c-dark, #1A1D23); background: var(--c-chip-bg, #ECEEF2); padding: 8px 16px; border-radius: 999px; font-size: 14px; font-weight: 700; min-height: 40px; display: inline-flex; align-items: center; gap: 6px;">📝 {{ __('Blog') }}</a>
                @if(Route::has('dictionary.index'))<a href="{{ route('dictionary.index') }}" style="text-decoration: none; color: var(--c-dark, #1A1D23); background: var(--c-chip-bg, #ECEEF2); padding: 8px 16px; border-radius: 999px; font-size: 14px; font-weight: 700; min-height: 40px; display: inline-flex; align-items: center; gap: 6px;">📚 {{ __('Glossaire') }}</a>@endif
                @if(Route::has('directory.index'))<a href="{{ route('directory.index') }}" style="text-decoration: none; color: var(--c-dark, #1A1D23); background: var(--c-chip-bg, #ECEEF2); padding: 8px 16px; border-radius: 999px; font-size: 14px; font-weight: 700; min-height: 40px; display: inline-flex; align-items: center; gap: 6px;">🔍 {{ __('Annuaire') }}</a>@endif
                @if(Route::has('tools.index'))<a href="{{ route('tools.index') }}" style="text-decoration: none; color: var(--c-dark, #1A1D23); background: var(--c-chip-bg, #ECEEF2); padding: 8px 16px; border-radius: 999px; font-size: 14px; font-weight: 700; min-height: 40px; display: inline-flex; align-items: center; gap: 6px;">🛠️ {{ __('Outils') }}</a>@endif
            </div>

            <p style="font-size: 0.78rem; color: var(--c-text-muted, #52586a); margin: 1.5rem 0 0.85rem; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif);">{{ __('Ou essayez nos outils populaires') }}</p>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;">
                <a href="{{ url('/outils/brain-dump') }}" style="text-decoration: none; color: #fff; background: var(--c-primary, #0B7285); padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; min-height: 44px; display: inline-flex; align-items: center; gap: 6px;">🧠 {{ __('Brain Dump') }}</a>
                <a href="{{ url('/outils/constructeur-prompts') }}" style="text-decoration: none; color: #fff; background: var(--c-primary, #0B7285); padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; min-height: 44px; display: inline-flex; align-items: center; gap: 6px;">⚙️ {{ __('Constructeur prompts') }}</a>
                <a href="{{ url('/outils/sudoku') }}" style="text-decoration: none; color: #fff; background: var(--c-accent, #C2410C); padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; min-height: 44px; display: inline-flex; align-items: center; gap: 6px;">🧩 {{ __('Sudoku') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection
