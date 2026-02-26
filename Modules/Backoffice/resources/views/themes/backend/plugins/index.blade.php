@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Plugins & Modules', 'subtitle' => 'Gestion des modules'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Plugins') }}</li>
    </ol>
</nav>

@php
    $total = count($modules);
    $actifs = collect($modules)->where('enabled', true)->count();
    $inactifs = $total - $actifs;
@endphp

{{-- Stats cards --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="layout-grid" class="text-primary icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Total modules') }}</p>
                    <h4 class="fw-bold mb-0">{{ $total }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="check-circle" class="text-success icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Actifs') }}</p>
                    <h4 class="fw-bold mb-0 text-success">{{ $actifs }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="x-circle" class="text-danger icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Inactifs') }}</p>
                    <h4 class="fw-bold mb-0 text-danger">{{ $inactifs }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i data-lucide="check-circle" class="icon-sm"></i>
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i data-lucide="x-circle" class="icon-sm"></i>
        {{ session('error') }}
    </div>
@endif

<div class="card mb-4">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-end">
            <span class="small text-muted">{{ __('Seuls les super administrateurs peuvent modifier ces paramètres.') }}</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Module') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Description') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Version') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Type') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Dépendances') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body text-center">{{ __('Statut') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($modules as $module)
                    <tr>
                        <td class="py-3 px-4">
                            <strong class="text-body small">{{ $module['name'] }}</strong>
                            @if($module['protected'])
                                <span class="badge bg-danger bg-opacity-10 text-danger ms-1">{{ __('Protégé') }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-muted small" style="max-width:280px;">
                            {{ $module['description'] ?: '-' }}
                        </td>
                        <td class="py-3 px-4">
                            <code class="small">{{ $module['version'] }}</code>
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge {{ $module['type'] === 'plugin' ? 'bg-info bg-opacity-10 text-info' : 'bg-primary bg-opacity-10 text-primary' }}">
                                {{ $module['type'] }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="d-flex flex-wrap gap-1">
                                @forelse($module['dependencies'] as $dep)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $dep }}</span>
                                @empty
                                    <span class="text-muted small">-</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($module['protected'])
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" checked disabled style="opacity:0.5;">
                                </div>
                            @else
                                <form action="{{ route('admin.plugins.toggle', $module['name']) }}" method="POST" class="d-inline">
                                    @csrf
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input" type="checkbox"
                                               @checked($module['enabled'])
                                               onchange="this.closest('form').submit()"
                                               title="{{ $module['enabled'] ? __('Désactiver') : __('Activer') }}">
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

{{-- Dependency Graph (collapsed by default) --}}
<div x-data="{ open: false }">
    <button type="button"
            @click="open = !open"
            class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 mb-4">
        <i data-lucide="git-branch" class="icon-sm"></i>
        {{ __('Graphe des dépendances') }}
        <i data-lucide="chevron-down" class="icon-sm" :class="open ? 'rotate-180' : ''" style="transition:transform .2s;"></i>
    </button>

    <div x-show="open" x-transition x-cloak>
        <div class="row">
            @foreach($modules as $module)
            <div class="col-xl-4 col-sm-6 mb-3">
                <div class="card border h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <strong class="small text-body">{{ $module['name'] }}</strong>
                            <span class="badge {{ $module['enabled'] ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                {{ $module['enabled'] ? __('Actif') : __('Inactif') }}
                            </span>
                        </div>
                        @if(!empty($module['dependencies']))
                            <p class="text-muted small mb-2">{{ __('Requiert') }} :</p>
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                @foreach($module['dependencies'] as $dep)
                                    @php $depEnabled = collect($modules)->firstWhere('name', $dep)['enabled'] ?? false; @endphp
                                    <span class="badge {{ $depEnabled ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                        {{ $dep }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small mb-2">{{ __('Aucune dépendance') }}</p>
                        @endif
                        @if(!empty($dependencyMap[$module['name']]))
                            <p class="text-muted small mb-2">{{ __('Requis par') }} :</p>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($dependencyMap[$module['name']] as $dependent)
                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $dependent }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
