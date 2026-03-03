<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Import CSV', 'subtitle' => 'Utilisateurs'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{ __('Utilisateurs') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Import CSV') }}</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i data-lucide="check-circle" class="icon-sm"></i>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i data-lucide="x-circle" class="icon-sm"></i>
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-3">
        <p class="fw-semibold mb-2">{{ __('Des erreurs sont survenues :') }}</p>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i data-lucide="upload" class="text-primary icon-md"></i>
                {{ __('Importer des utilisateurs depuis un fichier CSV') }}
            </h4>
            <a href="{{ route('admin.users.index') }}" class="btn btn-light btn-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="arrow-left" class="icon-sm"></i>
                {{ __('Retour à la liste') }}
            </a>
        </div>
    </div>
    <div class="card-body p-4">

        {{-- Info format --}}
        <div class="alert alert-info d-flex align-items-start gap-3 mb-4">
            <i data-lucide="info" class="icon-md flex-shrink-0 mt-1"></i>
            <div>
                <p class="fw-semibold mb-1">{{ __('Format du fichier CSV attendu :') }}</p>
                <p class="mb-1">{{ __('Colonnes dans cet ordre :') }} <code class="bg-primary bg-opacity-10 px-2 py-1 rounded small">id, name, email, created_at</code></p>
                <p class="mb-1">{{ __("La 1ère ligne (en-tête) est ignorée. L'email sert d'identifiant unique.") }}</p>
                <p class="mb-0">{{ __('Un mot de passe aléatoire est généré pour les nouveaux utilisateurs.') }}</p>
            </div>
        </div>

        <form action="{{ route('admin.import.users.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="file" class="form-label fw-medium">
                    {{ __('Fichier CSV') }} <span class="text-danger">*</span>
                </label>
                <input type="file"
                    class="form-control @error('file') is-invalid @enderror"
                    id="file"
                    name="file"
                    accept=".csv,.txt"
                    required>
                @error('file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <p class="text-muted small mt-1">{{ __('Formats acceptés : .csv, .txt - Taille max : 2 Mo') }}</p>
            </div>

            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i data-lucide="upload" class="icon-sm"></i>
                {{ __('Importer') }}
            </button>
        </form>

    </div>
</div>

@endsection
