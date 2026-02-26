@extends('auth::layouts.app')

@section('title', __('Mes tokens API'))

@section('content')

<div class="d-flex align-items-center justify-content-between mb-24 flex-wrap gap-12">
    <div class="d-flex align-items-center gap-12">
        <iconify-icon icon="solar:key-outline" class="text-primary-600 text-3xl"></iconify-icon>
        <h1 class="fw-semibold mb-0">{{ __('Tokens API') }}</h1>
    </div>
    <a href="#create-form" class="btn btn-primary-600 radius-8">
        <iconify-icon icon="solar:add-circle-outline"></iconify-icon>
        {{ __('Créer un token') }}
    </a>
</div>

{{-- Affichage unique du nouveau token --}}
@if(session('token_value'))
<div class="alert alert-primary d-flex align-items-start gap-12 mb-20" role="alert">
    <iconify-icon icon="solar:danger-triangle-outline" class="text-xl flex-shrink-0 mt-2"></iconify-icon>
    <div class="flex-grow-1">
        <p class="fw-semibold mb-8">{{ __('Copiez ce token maintenant, il ne sera plus affiché :') }}</p>
        <div class="d-flex align-items-center gap-8 flex-wrap">
            <code id="token-display"
                  class="font-monospace text-sm px-12 py-8 rounded border"
                  style="background:#f8fafc; word-break:break-all;">
                {{ session('token_value') }}
            </code>
            <button onclick="navigator.clipboard.writeText(document.getElementById('token-display').innerText.trim()); this.textContent='Copié ✓';"
                    class="btn btn-sm btn-primary-600 radius-8 flex-shrink-0">
                {{ __('Copier') }}
            </button>
        </div>
    </div>
</div>
@endif

{{-- Liste des tokens --}}
<div class="card mb-20">
    <div class="card-header">
        <h5 class="card-title fw-semibold text-lg mb-0">{{ __('Tokens existants') }}</h5>
    </div>
    <div class="card-body p-0">
        @if($tokens->isEmpty())
            <p class="text-secondary-light text-sm py-32 text-center mb-0">{{ __('Aucun token API créé.') }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Nom') }}</th>
                            <th>{{ __('Créé le') }}</th>
                            <th>{{ __('Dernière utilisation') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tokens as $token)
                        <tr>
                            <td class="fw-medium">{{ $token->name }}</td>
                            <td class="text-secondary-light">{{ $token->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-secondary-light">
                                {{ $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : __('Jamais') }}
                            </td>
                            <td class="text-end">
                                <form action="{{ route('user.api-tokens.destroy', $token->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ __('Révoquer ce token définitivement ?') }}');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger-600 radius-8">
                                        <iconify-icon icon="solar:trash-bin-minimalistic-outline"></iconify-icon>
                                        {{ __('Révoquer') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Formulaire de création --}}
<div id="create-form" class="card">
    <div class="card-header">
        <h5 class="card-title fw-semibold text-lg mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:add-circle-outline" class="text-primary-600"></iconify-icon>
            {{ __('Nouveau token') }}
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('user.api-tokens.store') }}" method="POST" class="d-flex gap-12 flex-wrap align-items-start">
            @csrf
            <div class="flex-grow-1">
                <input type="text" name="name" required
                       placeholder="{{ __('Nom du token (ex : Mon application)') }}"
                       value="{{ old('name') }}"
                       class="form-control radius-8 @error('name') is-invalid @enderror">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary-600 radius-8 flex-shrink-0">
                <iconify-icon icon="solar:add-circle-outline"></iconify-icon> {{ __('Créer') }}
            </button>
        </form>
    </div>
</div>

@endsection
