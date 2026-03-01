<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Import de données')
@section('content')
<div class="page-content">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Import</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Import de données</h4>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px; font-size: 1.25rem; font-weight: 600;">1</div>
                    <h6>Choisir le type</h6>
                    <p class="text-muted small mb-0">Sélectionnez le type de données à importer</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px; font-size: 1.25rem; font-weight: 600;">2</div>
                    <h6>Uploader le fichier</h6>
                    <p class="text-muted small mb-0">Téléchargez votre fichier CSV ou Excel</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 48px; height: 48px; font-size: 1.25rem; font-weight: 600;">3</div>
                    <h6>Mapper les colonnes</h6>
                    <p class="text-muted small mb-0">Associez les colonnes aux champs du modèle</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="model_type" class="form-label">Type de données *</label>
                    <select name="model_type" id="model_type" class="form-select @error('model_type') is-invalid @enderror" required>
                        <option value="">Sélectionnez un type</option>
                        @foreach($modelTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('model_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('model_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="file" class="form-label">Fichier à importer *</label>
                    <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".csv,.xlsx,.xls" required>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Formats acceptés : CSV, XLSX. Taille maximale : 10 Mo.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Télécharger un modèle :</label>
                    <div class="d-flex gap-2">
                        @foreach($modelTypes as $key => $label)
                            <a href="{{ route('admin.import.template', $key) }}" class="btn btn-sm btn-outline-secondary">
                                <i data-lucide="download" style="width: 14px; height: 14px;"></i> {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i data-lucide="eye" style="width: 16px; height: 16px;"></i> Prévisualiser
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
