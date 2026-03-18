@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Import / Export produits')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item active" aria-current="page">Import / Export</li>
    </ol>
</nav>

<div class="row">
    {{-- Export --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Exporter les produits</h6>
                <p class="text-muted">Télécharger tous les produits et variantes au format CSV.</p>
                <a href="{{ route('admin.ecommerce.import-export.export') }}" class="btn btn-primary">
                    <i data-lucide="download" class="icon-sm me-1"></i> Exporter CSV
                </a>
            </div>
        </div>
    </div>

    {{-- Import --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Importer des produits</h6>
                <p class="text-muted">Importer un fichier CSV (colonnes : name, slug, price, is_active, sku, variant_price, stock, weight).</p>

                <form action="{{ route('admin.ecommerce.import-export.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" class="form-control @error('file') is-invalid @enderror" name="file" accept=".csv,.txt" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i data-lucide="upload" class="icon-sm me-1"></i> Importer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@if(session('import_errors'))
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-warning">
            <h6>Erreurs d'importation :</h6>
            <ul class="mb-0">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif
@endsection
