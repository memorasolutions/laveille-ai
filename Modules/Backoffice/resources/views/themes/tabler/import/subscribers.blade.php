@extends('backoffice::layouts.admin', ['title' => 'Import CSV', 'subtitle' => 'Abonnés'])
@section('content')
@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Importer des abonnés depuis un fichier CSV</h3>
        <a href="{{ route('admin.newsletter.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i> Retour à la liste</a>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Format attendu :</strong> id, email, status, subscribed_at<br>
            <small class="text-muted">La première ligne (en-tête) sera ignorée.</small>
        </div>
        <form action="{{ route('admin.import.subscribers.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-bold">Fichier CSV <span class="text-danger">*</span></label>
                <input type="file" class="form-control @error('file') is-invalid @enderror" name="file" accept=".csv,.txt" required>
                @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Formats acceptés : .csv, .txt - Taille max : 2 Mo</div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="ti ti-upload me-1"></i> Importer</button>
        </form>
    </div>
</div>
@endsection
