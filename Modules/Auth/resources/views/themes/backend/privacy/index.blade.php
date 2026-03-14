<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('title', __('Centre de confidentialité'))

@section('content')

<div class="mb-3">
    <h1 class="fw-semibold mb-1" style="font-size:1.25rem;">{{ __('Centre de confidentialité') }}</h1>
    <p class="text-muted mb-0 text-sm">{{ __('Consultez, exportez ou supprimez vos données personnelles conformément au RGPD.') }}</p>
</div>

{{-- Données collectées --}}
<div class="card mb-3">
    <div class="card-header py-2">
        <h5 class="card-title fw-semibold mb-0" style="font-size:0.9rem;">
            <i data-lucide="database" class="me-1"></i>{{ __('Vos données') }}
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="font-size:0.8rem;">{{ __('Catégorie') }}</th>
                        <th style="font-size:0.8rem;">{{ __('Description') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dataCategories as $cat)
                        <tr>
                            <td class="align-middle" style="font-size:0.85rem;">
                                <i data-lucide="{{ $cat['icon'] }}" class="me-1" style="width:16px;height:16px;"></i>
                                {{ $cat['name'] }}
                            </td>
                            <td class="text-muted align-middle" style="font-size:0.85rem;">{{ $cat['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row gy-3">
    {{-- Export --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title fw-semibold" style="font-size:0.9rem;">
                    <i data-lucide="download" class="me-1"></i>{{ __('Exporter mes données') }}
                </h5>
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    {{ __('Téléchargez une copie complète de vos données personnelles au format JSON.') }}
                </p>
                <a href="{{ route('user.export-data') }}" class="btn btn-outline-primary btn-sm">
                    <i data-lucide="download" class="me-1" style="width:14px;height:14px;"></i>{{ __('Télécharger mes données') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Suppression --}}
    <div class="col-md-6">
        <div class="card h-100 border-danger border-opacity-25">
            <div class="card-body">
                <h5 class="card-title fw-semibold text-danger" style="font-size:0.9rem;">
                    <i data-lucide="trash-2" class="me-1"></i>{{ __('Supprimer mon compte') }}
                </h5>
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    {{ __('Cette action est irréversible. Toutes vos données seront anonymisées ou supprimées.') }}
                </p>
                <form method="POST" action="{{ route('user.account.delete') }}"
                      onsubmit="return confirm('{{ __('Êtes-vous sûr ? Cette action est irréversible.') }}')">
                    @csrf
                    @method('DELETE')
                    <div class="mb-2">
                        <input type="password" name="password" class="form-control form-control-sm"
                               placeholder="{{ __('Confirmez votre mot de passe') }}" required>
                    </div>
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i data-lucide="alert-triangle" class="me-1" style="width:14px;height:14px;"></i>{{ __('Supprimer définitivement') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Droits RGPD --}}
<div class="card mt-3">
    <div class="card-body">
        <h5 class="card-title fw-semibold" style="font-size:0.9rem;">
            <i data-lucide="shield" class="me-1"></i>{{ __('Vos droits') }}
        </h5>
        <ul class="mb-0" style="font-size:0.85rem;">
            <li>{{ __('Droit d\'accès : consultez toutes les données que nous détenons sur vous.') }}</li>
            <li>{{ __('Droit de rectification : modifiez vos informations depuis votre profil.') }}</li>
            <li>{{ __('Droit à la portabilité : exportez vos données au format JSON.') }}</li>
            <li>{{ __('Droit à l\'effacement : supprimez votre compte et vos données.') }}</li>
        </ul>
    </div>
</div>

@endsection
