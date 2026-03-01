<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Import CSV', 'subtitle' => 'Plans'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">{{ __('Plans') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Import CSV') }}</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i data-lucide="check-circle" class="icon-sm"></i>
        {{ session('success') }}
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
                {{ __('Importer des plans depuis un fichier CSV') }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.import.template', 'plans') }}" class="btn btn-light btn-sm d-inline-flex align-items-center gap-2">
                    <i data-lucide="download" class="icon-sm"></i>
                    {{ __('Template CSV') }}
                </a>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-light btn-sm d-inline-flex align-items-center gap-2">
                    <i data-lucide="arrow-left" class="icon-sm"></i>
                    {{ __('Retour à la liste') }}
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-4">

        <div class="alert alert-info d-flex align-items-start gap-3 mb-4">
            <i data-lucide="info" class="icon-md flex-shrink-0 mt-1"></i>
            <div>
                <p class="fw-semibold mb-1">{{ __('Format du fichier CSV attendu :') }}</p>
                <p class="mb-1">{{ __('Colonnes dans cet ordre :') }} <code class="bg-primary bg-opacity-10 px-2 py-1 rounded small">name, price, interval, features</code></p>
                <p class="mb-1">{{ __("La 1ère ligne (en-tête) est ignorée. Le slug sert d'identifiant unique.") }}</p>
                <p class="mb-0">{{ __("L'intervalle doit être") }} <code class="bg-primary bg-opacity-10 px-2 py-1 rounded small">monthly</code> {{ __('ou') }} <code class="bg-primary bg-opacity-10 px-2 py-1 rounded small">yearly</code> ({{ __('défaut : monthly') }}).</p>
            </div>
        </div>

        <form action="{{ route('admin.import.plans.store') }}" method="POST" enctype="multipart/form-data">
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
