@extends('backoffice::layouts.admin', ['title' => 'Plugins & Modules', 'subtitle' => 'Gestion des modules'])

@section('content')

{{-- Stats --}}
<div class="row gy-4 mb-24">
    @php
        $total = count($modules);
        $actifs = collect($modules)->where('enabled', true)->count();
        $inactifs = $total - $actifs;
    @endphp
    <div class="col-md-4">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Total modules</p>
                        <h6 class="mb-0">{{ $total }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-primary-600 rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:widget-2-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Actifs</p>
                        <h6 class="mb-0 text-success-main">{{ $actifs }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:check-circle-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-none border bg-gradient-start-3 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Inactifs</p>
                        <h6 class="mb-0 text-danger-main">{{ $inactifs }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-danger-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:close-circle-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Flash messages --}}
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

{{-- Table des modules --}}
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex flex-wrap align-items-center justify-content-end gap-2">
        <span class="text-secondary-light text-sm">Seuls les super administrateurs peuvent modifier ces paramètres.</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table bordered-table sm-table mb-0">
                <thead>
                    <tr>
                        <th style="min-width: 160px">Module</th>
                        <th style="min-width: 200px; max-width: 280px">Description</th>
                        <th style="min-width: 80px">Version</th>
                        <th style="min-width: 80px">Type</th>
                        <th style="min-width: 160px; max-width: 220px">Dépendances</th>
                        <th class="text-center" style="min-width: 80px; width: 80px">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($modules as $module)
                        <tr>
                            <td style="min-width: 160px">
                                <strong>{{ $module['name'] }}</strong>
                                @if($module['protected'])
                                    <span class="badge bg-danger-100 text-danger-600 ms-1">Protégé</span>
                                @endif
                            </td>
                            <td class="text-secondary-light text-truncate" style="min-width: 200px; max-width: 280px">{{ $module['description'] ?: '-' }}</td>
                            <td><code>{{ $module['version'] }}</code></td>
                            <td>
                                <span class="badge bg-{{ $module['type'] === 'plugin' ? 'info' : 'primary' }}-100 text-{{ $module['type'] === 'plugin' ? 'info' : 'primary' }}-600">
                                    {{ $module['type'] }}
                                </span>
                            </td>
                            <td style="min-width: 160px; max-width: 220px">
                                <div class="d-flex flex-wrap gap-1">
                                @forelse($module['dependencies'] as $dep)
                                    <span class="badge bg-neutral-200 text-neutral-600">{{ $dep }}</span>
                                @empty
                                    <span class="text-secondary-light">-</span>
                                @endforelse
                                </div>
                            </td>
                            <td class="text-center" style="width: 80px">
                                @if($module['protected'])
                                    <div class="form-check form-switch switch-primary d-flex justify-content-center">
                                        <input type="checkbox" class="form-check-input" checked disabled>
                                    </div>
                                @else
                                    <form action="{{ route('admin.plugins.toggle', $module['name']) }}" method="POST" class="d-inline">
                                        @csrf
                                        <div class="form-check form-switch switch-primary d-flex justify-content-center">
                                            <input type="checkbox" class="form-check-input" role="switch"
                                                @checked($module['enabled'])
                                                onchange="this.closest('form').submit()"
                                                title="{{ $module['enabled'] ? 'Désactiver' : 'Activer' }}">
                                        </div>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Principe ADHD: graphe dépendances replié par défaut - info secondaire --}}
<div class="mt-24" x-data="{ open: false }">
    <button type="button" class="btn btn-outline-neutral-600 text-sm btn-sm px-16 py-8 radius-8 d-flex align-items-center gap-2"
            @click="open = !open">
        <iconify-icon icon="solar:graph-new-outline" class="text-lg"></iconify-icon>
        Graphe des dépendances
        <iconify-icon :icon="open ? 'solar:alt-arrow-up-outline' : 'solar:alt-arrow-down-outline'" class="text-lg"></iconify-icon>
    </button>
    <div x-show="open" x-transition x-cloak class="card mt-12 p-0 radius-12">
        <div class="card-body p-24">
            <div class="row g-3">
                @foreach($modules as $module)
                    <div class="col-md-6 col-lg-4">
                        <div class="card border radius-8 p-16 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-8">
                                <strong class="text-sm">{{ $module['name'] }}</strong>
                                <span class="badge bg-{{ $module['enabled'] ? 'success' : 'danger' }}-100 text-{{ $module['enabled'] ? 'success' : 'danger' }}-600">
                                    {{ $module['enabled'] ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                            @if(!empty($module['dependencies']))
                                <p class="text-xs text-secondary-light mb-4">Requiert :</p>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($module['dependencies'] as $dep)
                                        @php $depEnabled = collect($modules)->firstWhere('name', $dep)['enabled'] ?? false; @endphp
                                        <span class="badge bg-{{ $depEnabled ? 'success' : 'danger' }}-100 text-{{ $depEnabled ? 'success' : 'danger' }}-600">
                                            {{ $dep }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-secondary-light mb-0">Aucune dépendance</p>
                            @endif
                            @if(!empty($dependencyMap[$module['name']]))
                                <p class="text-xs text-secondary-light mb-4 mt-8">Requis par :</p>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($dependencyMap[$module['name']] as $dependent)
                                        <span class="badge bg-info-100 text-info-600">{{ $dependent }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
