<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Stockage'))

@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Stockage') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0">{{ __('Stockage') }}</h4>
    <x-backoffice::help-modal id="storage-help" title="{{ __('Aide - Stockage') }}">
        @include('storage::admin._help')
    </x-backoffice::help-modal>
</div>

{{-- Résumé global --}}
@php
    $totalFiles = array_sum(array_column($disks, 'files_count'));
    $maxSize = max(array_column($disks, 'total_size') ?: [0]);
@endphp
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                    <i data-lucide="hard-drive" class="text-primary" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{{ __('Disques configurés') }}</p>
                    <h5 class="mb-0 fw-bold">{{ count($disks) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                    <i data-lucide="file" class="text-info" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{{ __('Fichiers totaux') }}</p>
                    <h5 class="mb-0 fw-bold">{{ number_format($totalFiles) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                    <i data-lucide="database" class="text-success" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{{ __('Taille totale') }}</p>
                    @php
                        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
                        $bytes = max($grandTotal, 0);
                        $pow = $bytes ? floor(log($bytes) / log(1024)) : 0;
                        $pow = min($pow, count($units) - 1);
                        $grandTotalHuman = round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
                    @endphp
                    <h5 class="mb-0 fw-bold">{{ $grandTotalHuman }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Grille des disques --}}
<div class="row" role="list" aria-label="{{ __('Liste des disques de stockage') }}">
    @foreach($disks as $diskName => $disk)
    <div class="col-md-6 col-lg-4 mb-4" role="listitem">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i data-lucide="hard-drive" class="me-2" aria-hidden="true"></i>
                    {{ $diskName }}
                </h6>
                @if(!empty($disk['error']))
                    <span class="badge bg-warning">{{ __('Inaccessible') }}</span>
                @else
                    <span class="badge bg-success">{{ __('Actif') }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">
                            <i data-lucide="file" class="me-1" aria-hidden="true"></i>
                            {{ __('Fichiers') }}
                        </span>
                        <span class="fw-semibold">{{ number_format($disk['files_count']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">
                            <i data-lucide="database" class="me-1" aria-hidden="true"></i>
                            {{ __('Taille') }}
                        </span>
                        <span class="fw-semibold">{{ $disk['total_size_human'] }}</span>
                    </div>
                </div>

                @if(empty($disk['error']) && $maxSize > 0)
                    @php $percent = round(($disk['total_size'] / $maxSize) * 100); @endphp
                    <div class="progress" style="height: 6px;" role="progressbar"
                         aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
                         aria-label="{{ __('Utilisation du disque') }} {{ $diskName }}">
                        <div class="progress-bar bg-{{ $percent > 80 ? 'danger' : 'primary' }}"
                             style="width: {{ $percent }}%"></div>
                    </div>
                    <small class="text-muted">{{ $percent }}% {{ __('du plus grand disque') }}</small>
                @endif
            </div>
            <div class="card-footer bg-transparent">
                @if(empty($disk['error']))
                    <a href="{{ route('admin.storage.show', $diskName) }}"
                       class="btn btn-sm btn-outline-primary w-100"
                       aria-label="{{ __('Voir les détails du disque') }} {{ $diskName }}">
                        <i data-lucide="folder-open" class="me-1" aria-hidden="true"></i>
                        {{ __('Explorer') }}
                    </a>
                @else
                    <button class="btn btn-sm btn-outline-secondary w-100" disabled>
                        {{ __('Disque inaccessible') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
