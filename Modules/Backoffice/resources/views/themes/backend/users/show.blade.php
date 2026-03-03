<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => $user->name])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{ __('Utilisateurs') }}</a></li>
        <li class="breadcrumb-item active">{{ $user->name }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="user" class="icon-md text-primary"></i>{{ $user->name }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
            <i data-lucide="pencil"></i> {{ __('Modifier') }}
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>
</div>

<div class="row g-4">

    {{-- Colonne profil --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body d-flex flex-column align-items-center text-center py-4">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold mb-3"
                     style="width:80px;height:80px;font-size:1.5rem;">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}
                </div>
                <h5 class="fw-semibold mb-1">{{ $user->name }}</h5>
                <p class="text-muted small mb-3">{{ $user->email }}</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach($user->roles as $role)
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-medium">
                            {{ $role->name }}
                        </span>
                    @endforeach
                    @if($user->roles->isEmpty())
                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-medium">
                            Aucun rôle
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Colonne informations --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <h5 class="fw-semibold mb-0">Informations</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">

                    <div class="col-sm-6">
                        <p class="text-muted small text-uppercase fw-medium mb-1">Nom</p>
                        <p class="fw-semibold mb-0">{{ $user->name }}</p>
                    </div>

                    <div class="col-sm-6">
                        <p class="text-muted small text-uppercase fw-medium mb-1">Courriel</p>
                        <p class="fw-semibold mb-0">{{ $user->email }}</p>
                    </div>

                    <div class="col-sm-6">
                        <p class="text-muted small text-uppercase fw-medium mb-1">Courriel vérifié</p>
                        @if($user->email_verified_at)
                            <span class="badge bg-success bg-opacity-10 text-success fw-medium">
                                {{ $user->email_verified_at->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning fw-medium">
                                Non vérifié
                            </span>
                        @endif
                    </div>

                    <div class="col-sm-6">
                        <p class="text-muted small text-uppercase fw-medium mb-1">Inscrit le</p>
                        <p class="fw-semibold mb-0">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="col-sm-6">
                        <p class="text-muted small text-uppercase fw-medium mb-1">Dernière modification</p>
                        <p class="fw-semibold mb-0">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="col-sm-6">
                        <p class="text-muted small text-uppercase fw-medium mb-1">Permissions</p>
                        <p class="fw-semibold mb-0">{{ $user->getAllPermissions()->count() }} permissions</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>

@endsection
