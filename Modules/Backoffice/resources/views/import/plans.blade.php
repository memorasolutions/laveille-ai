@extends('backoffice::layouts.admin', ['title' => 'Import CSV', 'subtitle' => 'Plans'])

@section('content')

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-16" role="alert">
        <iconify-icon icon="solar:check-circle-outline" class="icon text-xl"></iconify-icon>
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-16" role="alert">
        <iconify-icon icon="solar:danger-triangle-outline" class="icon text-xl"></iconify-icon>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-3">
        <h6 class="mb-0">Importer des plans depuis un fichier CSV</h6>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.import.template', 'plans') }}" class="btn btn-sm btn-outline-primary-600 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:download-minimalistic-outline" class="icon text-xl"></iconify-icon> Template CSV
            </a>
            <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-secondary-600 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:arrow-left-outline" class="icon text-xl"></iconify-icon> Retour à la liste
            </a>
        </div>
    </div>
    <div class="card-body">

        <div class="alert alert-info d-flex align-items-start gap-3 mb-24">
            <iconify-icon icon="solar:info-circle-outline" class="icon text-xl mt-1 flex-shrink-0"></iconify-icon>
            <div>
                <p class="mb-4 fw-semibold">Format du fichier CSV attendu :</p>
                <p class="mb-2 text-sm">Colonnes dans cet ordre : <code>name, price, interval, features</code></p>
                <p class="mb-2 text-sm">La 1ère ligne (en-tête) est ignorée. Le slug sert d'identifiant unique.</p>
                <p class="mb-0 text-sm">L'intervalle doit être <code>monthly</code> ou <code>yearly</code> (défaut : monthly).</p>
            </div>
        </div>

        <form action="{{ route('admin.import.plans.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-20">
                <label for="file" class="form-label fw-semibold">Fichier CSV <span class="text-danger">*</span></label>
                <input type="file"
                       class="form-control @error('file') is-invalid @enderror"
                       id="file"
                       name="file"
                       accept=".csv,.txt"
                       required>
                @error('file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Formats acceptés : .csv, .txt — Taille max : 2 Mo</div>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary-600 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:import-outline" class="icon text-xl"></iconify-icon> Importer
                </button>
            </div>
        </form>

    </div>
</div>

@endsection
