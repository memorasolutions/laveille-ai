<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'API Tokens', 'subtitle' => 'Profil'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.profile') }}">{{ __('Profil') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('API Tokens') }}</li>
    </ol>
</nav>

@if(session('token_value'))
<div class="alert alert-success d-flex align-items-start gap-3 mb-4">
    <i data-lucide="key-round" class="text-success icon-md flex-shrink-0 mt-1"></i>
    <div>
        <p class="mb-1 fw-semibold small">{{ __('Token créé avec succès') }}</p>
        <code class="bg-light px-3 py-1 rounded small">{{ session('token_value') }}</code>
        <p class="mb-0 mt-2 text-muted small">{{ __('Copiez ce token maintenant, il ne sera plus visible.') }}</p>
    </div>
</div>
@endif

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-bold mb-0">{{ __('Créer un nouveau token API') }}</h4>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.profile.tokens.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-medium">
                            {{ __('Nom du token') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Ex: CI/CD Pipeline" required maxlength="100">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Créer') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-bold mb-0">
                    {{ __('Tokens actifs') }}
                    <span class="fw-normal text-muted small">({{ $tokens->count() }})</span>
                </h4>
            </div>
            @if($tokens->isEmpty())
                <div class="card-body">
                    <p class="text-muted small mb-0">{{ __('Aucun token API créé pour le moment.') }}</p>
                </div>
            @else
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 fw-semibold text-body">{{ __('Nom') }}</th>
                                    <th class="py-3 px-4 fw-semibold text-body">{{ __('Créé le') }}</th>
                                    <th class="py-3 px-4 fw-semibold text-body">{{ __('Dernière utilisation') }}</th>
                                    <th class="py-3 px-4 fw-semibold text-body">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tokens as $token)
                                <tr>
                                    <td class="py-3 px-4 fw-semibold small text-body">{{ $token->name }}</td>
                                    <td class="py-3 px-4 text-muted small">{{ $token->created_at->diffForHumans() }}</td>
                                    <td class="py-3 px-4 text-muted small">{{ $token->last_used_at ? $token->last_used_at->diffForHumans() : __('Jamais') }}</td>
                                    <td class="py-3 px-4">
                                        <form action="{{ route('admin.profile.tokens.destroy', $token->id) }}" method="POST"
                                              onsubmit="return confirm('{{ __('Révoquer ce token ?') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2">
                                                <i data-lucide="ban" class="icon-sm"></i>
                                                {{ __('Révoquer') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
