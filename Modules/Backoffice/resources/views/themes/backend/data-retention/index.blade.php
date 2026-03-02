<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Rétention des données', 'subtitle' => 'Tableau de bord'])

@section('breadcrumbs')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Rétention des données</li>
    </ol>
</nav>
@endsection

@section('content')

@php
    $totalRecords = collect($stats)->sum('total');
    $totalEligible = collect($stats)->sum('eligible');
@endphp

<div class="row mb-4">
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px;">
                    <i data-lucide="database" class="icon-lg text-primary"></i>
                </div>
                <p class="text-muted small mb-1">Total enregistrements</p>
                <h4 class="fw-bold mb-0 text-primary">{{ number_format($totalRecords) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px;">
                    <i data-lucide="trash-2" class="icon-lg text-warning"></i>
                </div>
                <p class="text-muted small mb-1">Éligibles au nettoyage</p>
                <h4 class="fw-bold mb-0 text-warning">{{ number_format($totalEligible) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px;">
                    <i data-lucide="shield-check" class="icon-lg text-success"></i>
                </div>
                <p class="text-muted small mb-1">Tables surveillées</p>
                <h4 class="fw-bold mb-0 text-success">{{ count($stats) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i data-lucide="clock" class="icon-md text-primary"></i>
                Détail par table
            </h4>
            <a href="{{ route('admin.settings.index') }}"
               class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2">
                <i data-lucide="settings" class="icon-sm"></i>
                Paramètres rétention
            </a>
        </div>
    </div>
    <div class="p-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="py-3 px-4 fw-semibold text-body">Table</th>
                        <th class="py-3 px-4 fw-semibold text-body text-center">Rétention (jours)</th>
                        <th class="py-3 px-4 fw-semibold text-body text-center">Total</th>
                        <th class="py-3 px-4 fw-semibold text-body text-center">Éligibles</th>
                        <th class="py-3 px-4 fw-semibold text-body text-center">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats as $stat)
                        <tr>
                            <td class="py-3 px-4">
                                <span class="fw-semibold small">{{ $stat['label'] }}</span>
                                <br>
                                <span class="text-muted small">{{ $stat['table'] }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($stat['retention_days'] > 0)
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        {{ $stat['retention_days'] }} j
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                        Expiration auto
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center fw-semibold small">
                                {{ number_format($stat['total']) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($stat['eligible'] > 0)
                                    <span class="fw-semibold small text-warning">{{ number_format($stat['eligible']) }}</span>
                                @else
                                    <span class="small text-success">0</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($stat['eligible'] === 0)
                                    <span class="badge bg-success bg-opacity-10 text-success">OK</span>
                                @elseif($stat['eligible'] > 100)
                                    <span class="badge bg-danger bg-opacity-10 text-danger">Nettoyage requis</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning">À surveiller</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3 pt-3 border-top">
            <p class="text-muted small mb-0">
                Commande de nettoyage : <code>php artisan app:cleanup</code>
                &mdash; Simulation : <code>php artisan app:cleanup --dry-run</code>
            </p>
        </div>
    </div>
</div>

@endsection
