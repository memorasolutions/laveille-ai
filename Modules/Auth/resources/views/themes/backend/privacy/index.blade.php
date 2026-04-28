<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Centre de confidentialité') . ' - ' . config('app.name'))

@section('user-content')

<div style="display: flex !important; justify-content: space-between !important; align-items: flex-start !important; flex-wrap: wrap !important; margin-bottom: 20px;">
    <div>
        <h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">{{ __('Centre de confidentialité') }}</h2>
        <p style="color: #777; margin: 0;">{{ __('Consultez, exportez ou supprimez vos données personnelles conformément au RGPD.') }}</p>
    </div>
</div>

<div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; margin-bottom: 20px; overflow: hidden;">
    <div style="padding: 12px 16px; border-bottom: 1px solid #e5e5e5; background: #f8f9fa;">
        <h4 style="font-weight: 600; margin: 0; font-size: 15px;">{{ __('Vos données') }}</h4>
    </div>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f9fa; border-bottom: 2px solid #e5e5e5;">
                <th style="padding: 10px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #333;">{{ __('Catégorie') }}</th>
                <th style="padding: 10px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #333;">{{ __('Description') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataCategories as $cat)
            <tr style="border-bottom: 1px solid #f0f0f0;">
                <td style="padding: 10px 16px; font-size: 14px; color: #333;">{{ $cat['name'] }}</td>
                <td style="padding: 10px 16px; font-size: 14px; color: #777;">{{ $cat['description'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div style="flex: 1; min-width: 280px;">
        <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; padding: 16px; height: 100%;">
            <h4 style="font-weight: 600; margin: 0 0 10px; font-size: 15px;">{{ __('Exporter mes données') }}</h4>
            <p style="color: #777; margin: 0 0 12px; font-size: 14px;">{{ __('Téléchargez une copie complète de vos données personnelles au format JSON.') }}</p>
            <a href="{{ route('user.export-data') }}" class="btn btn-sm" style="border: 1px solid var(--c-primary); color: var(--c-primary); background: transparent; border-radius: 4px; padding: 6px 14px; text-decoration: none;">
                {{ __('Télécharger mes données') }}
            </a>
        </div>
    </div>
    <div style="flex: 1; min-width: 280px;">
        <div style="background: #fff; border: 1px solid var(--c-danger); border-radius: 6px; padding: 16px; height: 100%;">
            <h4 style="font-weight: 600; margin: 0 0 10px; color: var(--c-danger); font-size: 15px;">{{ __('Supprimer mon compte') }}</h4>
            <p style="color: #777; margin: 0 0 12px; font-size: 14px;">{{ __('Cette action est irréversible. Toutes vos données seront anonymisées ou supprimées.') }}</p>
            <form method="POST" action="{{ route('user.account.delete') }}" data-confirm="{{ __('Êtes-vous sûr ? Cette action est irréversible.') }}">
                @csrf @method('DELETE')
                <div style="margin-bottom: 8px;">
                    <input type="password" name="password" required
                           placeholder="{{ __('Confirmez votre mot de passe') }}"
                           style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                </div>
                <button type="submit" class="btn btn-sm" style="border: 1px solid var(--c-danger); color: var(--c-danger); background: transparent; border-radius: 4px; padding: 6px 14px; cursor: pointer;">
                    {{ __('Supprimer définitivement') }}
                </button>
            </form>
        </div>
    </div>
</div>

<div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; padding: 16px;">
    <h4 style="font-weight: 600; margin: 0 0 10px; font-size: 15px;">{{ __('Vos droits') }}</h4>
    <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #555; line-height: 1.8;">
        <li>{{ __('Droit d\'accès : consultez toutes les données que nous détenons sur vous.') }}</li>
        <li>{{ __('Droit de rectification : modifiez vos informations depuis votre profil.') }}</li>
        <li>{{ __('Droit à la portabilité : exportez vos données au format JSON.') }}</li>
        <li>{{ __('Droit à l\'effacement : supprimez votre compte et vos données.') }}</li>
    </ul>
</div>

@endsection
