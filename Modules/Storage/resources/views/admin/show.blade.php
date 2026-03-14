<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Stockage') . ' - ' . $disk)

@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.storage.index') }}">{{ __('Stockage') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $disk }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0">
        <i data-lucide="hard-drive" class="me-2" aria-hidden="true"></i>
        {{ __('Disque') }} : {{ $disk }}
    </h4>
    <a href="{{ route('admin.storage.index') }}" class="btn btn-outline-secondary btn-sm">
        <i data-lucide="arrow-left" class="me-1" aria-hidden="true"></i>
        {{ __('Retour') }}
    </a>
</div>

{{-- Stats disque --}}
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                    <i data-lucide="file" class="text-info" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{{ __('Fichiers') }}</p>
                    <h5 class="mb-0 fw-bold">{{ number_format($usage['files_count']) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                    <i data-lucide="database" class="text-success" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{{ __('Taille totale') }}</p>
                    <h5 class="mb-0 fw-bold">{{ $usage['total_size_human'] }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Dossiers --}}
@if(count($directories) > 0)
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i data-lucide="folder" class="me-2" aria-hidden="true"></i>
            {{ __('Dossiers') }} ({{ count($directories) }})
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="{{ __('Liste des dossiers') }}">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Nom') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($directories as $dir)
                    <tr>
                        <td>
                            <i data-lucide="folder" class="me-2 text-warning" aria-hidden="true"></i>
                            {{ $dir }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Fichiers --}}
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i data-lucide="file" class="me-2" aria-hidden="true"></i>
            {{ __('Fichiers') }} ({{ count($files) }})
        </h6>
    </div>
    <div class="card-body p-0">
        @if(count($files) > 0)
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="{{ __('Liste des fichiers') }}">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Nom') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($files as $file)
                    <tr>
                        <td>
                            <i data-lucide="file" class="me-2 text-muted" aria-hidden="true"></i>
                            {{ $file }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-4 text-center text-muted">
            <i data-lucide="folder-open" class="mb-2" style="width: 48px; height: 48px;" aria-hidden="true"></i>
            <p class="mb-0">{{ __('Aucun fichier dans ce disque') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
