@extends(fronttheme_layout())

@section('title', __('Paramètres annuaire - Admin'))

@section('content')
<div style="max-width: 680px; margin: 2rem auto; padding: 0 1rem;">

    <h1 style="font-size: 1.65rem; font-weight: 700; color: var(--c-dark, #1a1a1a); margin-bottom: 0.25rem;">
        {{ __('Paramètres annuaire') }}
    </h1>
    <p style="color: #6b7280; margin-bottom: 1.75rem; font-size: 0.95rem;">
        {{ __('Configuration générale du module annuaire.') }}
    </p>

    @if(session('success'))
        <div role="alert" aria-live="polite" style="background: #d1fae5; color: #065f46; padding: 0.85rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.925rem; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.75rem;">

        <h2 style="font-size: 1.1rem; font-weight: 600; color: var(--c-dark, #1a1a1a); margin-bottom: 0.25rem;">
            {{ __('Ordre de tri par défaut') }} <code style="font-size: 0.85rem; color: #6b7280;">/annuaire</code>
        </h2>
        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1.25rem;">
            {{ __("Détermine l'ordre d'affichage des fiches lorsqu'un visiteur arrive sur la page annuaire.") }}
        </p>

        <form method="POST" action="{{ route('admin.directory.settings.update') }}">
            @csrf

            <div style="margin-bottom: 1.5rem;">
                <label for="default_sort" style="display: block; font-weight: 600; font-size: 0.925rem; color: var(--c-dark, #1a1a1a); margin-bottom: 0.4rem;">
                    {{ __('Tri par défaut') }}
                </label>

                <select
                    name="default_sort"
                    id="default_sort"
                    aria-describedby="default_sort_help"
                    style="display: block; width: 100%; padding: 0.6rem 0.85rem; font-size: 0.95rem; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; color: var(--c-dark, #1a1a1a); min-height: 44px;"
                >
                    @foreach($sortOptions as $key => $label)
                        <option value="{{ $key }}" {{ $key === $defaultSort ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <p id="default_sort_help" style="color: #6b7280; font-size: 0.85rem; margin-top: 0.4rem; line-height: 1.5;">
                    <strong>{{ __('Hasard') }}</strong> : {{ __('ordre aléatoire à chaque chargement.') }}
                    <strong>{{ __('Populaires') }}</strong> : {{ __('fiches les plus cliquées en premier.') }}
                    <strong>{{ __('Récents') }}</strong> : {{ __('fiches ajoutées récemment en premier.') }}
                    <strong>{{ __('Alphabétique') }}</strong> : {{ __('classement de A à Z par nom.') }}
                </p>

                @error('default_sort')
                    <p role="alert" style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button
                    type="submit"
                    style="display: inline-flex; align-items: center; justify-content: center; padding: 0.65rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #fff; background: var(--c-primary, #0B7285); border: none; border-radius: 6px; min-height: 44px; cursor: pointer; transition: opacity 0.15s ease;"
                    onmouseover="this.style.opacity='0.88'"
                    onmouseout="this.style.opacity='1'"
                >
                    {{ __('Enregistrer') }}
                </button>
            </div>
        </form>

    </div>

</div>
@endsection
