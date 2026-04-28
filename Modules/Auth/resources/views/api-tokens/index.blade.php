<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('title', __('Mes tokens API'))

@section('content')

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <i data-lucide="key" class="text-primary"></i>
        <h1 class="fw-semibold mb-0">{{ __('Tokens API') }}</h1>
    </div>
    <a href="#create-form" class="btn btn-primary rounded-2">
        <i data-lucide="plus-circle"></i>
        {{ __('Créer un token') }}
    </a>
</div>

{{-- Affichage unique du nouveau token --}}
@if(session('token_value'))
<div class="alert alert-primary d-flex align-items-start gap-2 mb-3" role="alert">
    <i data-lucide="alert-triangle" class="flex-shrink-0 mt-1"></i>
    <div class="flex-grow-1">
        <p class="fw-semibold mb-2">{{ __('Copiez ce token maintenant, il ne sera plus affiché :') }}</p>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <code id="token-display"
                  class="font-monospace text-sm px-2 py-2 rounded border"
                  style="background:#f8fafc; word-break:break-all;">
                {{ session('token_value') }}
            </code>
            <button onclick="navigator.clipboard.writeText(document.getElementById('token-display').innerText.trim()); this.textContent='Copié ✓';"
                    class="btn btn-sm btn-primary rounded-2 flex-shrink-0">
                {{ __('Copier') }}
            </button>
        </div>
    </div>
</div>
@endif

{{-- Liste des tokens --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title fw-semibold mb-0">{{ __('Tokens existants') }}</h5>
    </div>
    <div class="card-body p-0">
        @if($tokens->isEmpty())
            <p class="text-muted text-sm py-4 text-center mb-0">{{ __('Aucun token API créé.') }}</p>
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
                            <td class="text-muted">{{ $token->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-muted">
                                {{ $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : __('Jamais') }}
                            </td>
                            <td class="text-end">
                                <form action="{{ route('user.api-tokens.destroy', $token->id) }}" method="POST" class="d-inline"
                                      data-confirm="{{ __('Révoquer ce token définitivement ?') }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-2">
                                        <i data-lucide="trash-2"></i>
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
        <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="plus-circle" class="text-primary"></i>
            {{ __('Nouveau token') }}
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('user.api-tokens.store') }}" method="POST" class="d-flex gap-2 flex-wrap align-items-start">
            @csrf
            <div class="flex-grow-1">
                <input type="text" name="name" required
                       placeholder="{{ __('Nom du token (ex : Mon application)') }}"
                       value="{{ old('name') }}"
                       class="form-control rounded-2 @error('name') is-invalid @enderror">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary rounded-2 flex-shrink-0">
                <i data-lucide="plus-circle"></i> {{ __('Créer') }}
            </button>
        </form>
    </div>
</div>

@endsection
