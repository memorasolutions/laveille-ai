@extends('backoffice::layouts.admin', ['title' => 'Rétention des données', 'subtitle' => 'Tableau de bord'])

@section('content')

<div class="row gy-4">
    {{-- Summary cards --}}
    @php
        $totalRecords = collect($stats)->sum('total');
        $totalEligible = collect($stats)->sum('eligible');
    @endphp

    <div class="col-md-4">
        <div class="card radius-12 h-100">
            <div class="card-body text-center">
                <div class="w-40-px h-40-px bg-primary-100 text-primary-600 d-flex align-items-center justify-content-center rounded-circle mx-auto mb-12">
                    <iconify-icon icon="solar:database-outline" class="icon text-xl"></iconify-icon>
                </div>
                <h6 class="mb-4">Total enregistrements</h6>
                <h3 class="fw-bold text-primary-600">{{ number_format($totalRecords) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card radius-12 h-100">
            <div class="card-body text-center">
                <div class="w-40-px h-40-px bg-warning-100 text-warning-600 d-flex align-items-center justify-content-center rounded-circle mx-auto mb-12">
                    <iconify-icon icon="solar:trash-bin-trash-outline" class="icon text-xl"></iconify-icon>
                </div>
                <h6 class="mb-4">Éligibles au nettoyage</h6>
                <h3 class="fw-bold text-warning-600">{{ number_format($totalEligible) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card radius-12 h-100">
            <div class="card-body text-center">
                <div class="w-40-px h-40-px bg-success-100 text-success-600 d-flex align-items-center justify-content-center rounded-circle mx-auto mb-12">
                    <iconify-icon icon="solar:shield-check-outline" class="icon text-xl"></iconify-icon>
                </div>
                <h6 class="mb-4">Tables surveillées</h6>
                <h3 class="fw-bold text-success-600">{{ count($stats) }}</h3>
            </div>
        </div>
    </div>

    {{-- Detail table --}}
    <div class="col-12">
        <div class="card radius-12">
            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:clock-circle-outline" class="icon text-xl"></iconify-icon>
                    Détail par table
                </h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-outline-primary-600 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:settings-outline" class="icon text-xl"></iconify-icon> Paramètres rétention
                    </a>
                </div>
            </div>
            <div class="card-body p-0 scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th class="text-center">Rétention (jours)</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Éligibles</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats as $stat)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $stat['label'] }}</span>
                                    <br><small class="text-secondary-light">{{ $stat['table'] }}</small>
                                </td>
                                <td class="text-center">
                                    @if($stat['retention_days'] > 0)
                                        <span class="badge bg-primary-50 text-primary-600">{{ $stat['retention_days'] }} j</span>
                                    @else
                                        <span class="badge bg-secondary-50 text-secondary-600">Expiration auto</span>
                                    @endif
                                </td>
                                <td class="text-center fw-semibold">{{ number_format($stat['total']) }}</td>
                                <td class="text-center">
                                    @if($stat['eligible'] > 0)
                                        <span class="text-warning-600 fw-semibold">{{ number_format($stat['eligible']) }}</span>
                                    @else
                                        <span class="text-success-600">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($stat['eligible'] === 0)
                                        <span class="badge bg-success-100 text-success-600">OK</span>
                                    @elseif($stat['eligible'] > 100)
                                        <span class="badge bg-danger-100 text-danger-600">Nettoyage requis</span>
                                    @else
                                        <span class="badge bg-warning-100 text-warning-600">À surveiller</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between gap-3">
                <small class="text-secondary-light">
                    <iconify-icon icon="solar:info-circle-outline" class="icon"></iconify-icon>
                    Commande de nettoyage : <code>php artisan app:cleanup</code> — Simulation : <code>php artisan app:cleanup --dry-run</code>
                </small>
            </div>
        </div>
    </div>
</div>

@endsection
