{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Audits Pricing')])

@section('content')
    <div class="page-content">
        <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.directory.index') }}">{{ __('Annuaire') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('Audits Pricing') }}</li>
            </ol>
        </nav>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <h4 class="mb-3">📊 {{ __('Audits Pricing – Multi-source consensus') }}</h4>

        {{-- Stats cards --}}
        <div class="row mb-3">
            <div class="col-md-2 col-sm-4 col-6"><div class="card border-0 bg-primary text-white"><div class="card-body py-2"><div class="small">{{ __('Total audits') }}</div><div class="h5 mb-0">{{ $stats['total'] }}</div></div></div></div>
            <div class="col-md-2 col-sm-4 col-6"><div class="card border-0 bg-warning"><div class="card-body py-2"><div class="small">{{ __('À réviser') }}</div><div class="h5 mb-0">{{ $stats['pending'] }}</div></div></div></div>
            <div class="col-md-2 col-sm-4 col-6"><div class="card border-0 bg-success text-white"><div class="card-body py-2"><div class="small">{{ __('Acceptés') }}</div><div class="h5 mb-0">{{ $stats['accepted'] }}</div></div></div></div>
            <div class="col-md-2 col-sm-4 col-6"><div class="card border-0 bg-secondary text-white"><div class="card-body py-2"><div class="small">{{ __('Rejetés') }}</div><div class="h5 mb-0">{{ $stats['rejected'] }}</div></div></div></div>
            <div class="col-md-2 col-sm-4 col-6"><div class="card border-0 bg-danger text-white"><div class="card-body py-2"><div class="small">{{ __('Périmés (>90j)') }}</div><div class="h5 mb-0">{{ $stats['stale'] }}</div></div></div></div>
            <div class="col-md-2 col-sm-4 col-6"><div class="card border-0 bg-info text-white"><div class="card-body py-2"><div class="small">{{ __('Couverture') }}</div><div class="h5 mb-0">{{ $stats['tools_audited'] }}/{{ $stats['tools_total'] }}</div></div></div></div>
        </div>

        {{-- Filters --}}
        <div class="btn-group mb-3" role="group" aria-label="{{ __('Filtres') }}">
            <a href="?filter=pending" class="btn btn-outline-primary {{ $filter === 'pending' ? 'active' : '' }}">{{ __('À réviser') }}</a>
            <a href="?filter=stale" class="btn btn-outline-danger {{ $filter === 'stale' ? 'active' : '' }}">{{ __('Périmés') }}</a>
            <a href="?filter=fresh" class="btn btn-outline-success {{ $filter === 'fresh' ? 'active' : '' }}">{{ __('Récents') }}</a>
            <a href="?filter=all" class="btn btn-outline-secondary {{ $filter === 'all' ? 'active' : '' }}">{{ __('Tous') }}</a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Outil') }}</th>
                                <th>{{ __('Actuel') }}</th>
                                <th>{{ __('Audité') }}</th>
                                <th>{{ __('Confidence') }}</th>
                                <th>{{ __('Score') }}</th>
                                <th>{{ __('Audité le') }}</th>
                                <th>{{ __('Statut') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($audits as $audit)
                                @php
                                    $diff = $audit->real_pricing && $audit->real_pricing !== $audit->tool->pricing;
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.directory.edit', $audit->tool) }}" class="fw-bold">{{ $audit->tool->name ?? '#'.$audit->directory_tool_id }}</a>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $audit->tool->pricing ?? '–' }}</span></td>
                                    <td>
                                        <span class="badge {{ $diff ? 'bg-warning text-dark' : 'bg-success' }}">
                                            {{ $audit->real_pricing ?? '–' }}
                                        </span>
                                        @if($audit->has_education_discount === true)
                                            <span class="badge bg-info ms-1" title="{{ __('Tarif éducation détecté') }}">🎓</span>
                                        @endif
                                    </td>
                                    <td>{{ $audit->confidence }}/100</td>
                                    <td>{{ $audit->weighted_score }}/100</td>
                                    <td><small class="text-muted">{{ $audit->audited_at?->isoFormat('LL') }}</small></td>
                                    <td>
                                        @switch($audit->review_status)
                                            @case('pending') <span class="badge bg-warning text-dark">{{ __('En attente') }}</span> @break
                                            @case('accepted') <span class="badge bg-success">✓ {{ __('Accepté') }}</span> @break
                                            @case('rejected') <span class="badge bg-secondary">✕ {{ __('Rejeté') }}</span> @break
                                            @default <span class="badge bg-light text-dark">{{ $audit->review_status }}</span>
                                        @endswitch
                                    </td>
                                    <td class="text-end">
                                        @php
                                            $auditActions = [];
                                            if ($audit->screenshot_path) {
                                                $auditActions[] = ['label' => __('Screenshot'), 'icon' => 'camera', 'url' => Storage::url($audit->screenshot_path), 'target' => '_blank'];
                                            }
                                            if (!empty($audit->evidence['url'])) {
                                                $auditActions[] = ['label' => __('Évidence'), 'icon' => 'external-link', 'url' => $audit->evidence['url'], 'target' => '_blank'];
                                            }
                                            if ($audit->review_status === 'pending') {
                                                if (count($auditActions) > 0) {
                                                    $auditActions[] = ['divider' => true];
                                                }
                                                if ($diff) {
                                                    $auditActions[] = ['label' => __('Accepter'), 'icon' => 'check', 'url' => route('admin.directory.pricing-audit.accept', $audit), 'method' => 'POST'];
                                                } else {
                                                    $auditActions[] = ['label' => __('Accepter (aucun changement)'), 'icon' => 'check', 'info' => true];
                                                }
                                                $auditActions[] = ['label' => __('Rejeter'), 'icon' => 'x', 'url' => route('admin.directory.pricing-audit.reject', $audit), 'method' => 'POST', 'confirm' => __('Rejeter cet audit ?'), 'danger' => true];
                                            }
                                        @endphp
                                        @if(count($auditActions) > 0)
                                            @include('core::components.action-menu', ['actions' => $auditActions])
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">{{ __('Aucun audit pour ce filtre.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $audits->links() }}
            </div>
        </div>

        <p class="small text-muted mt-2">
            {{ __('Lance manuellement') }} : <code>php artisan tools:audit-pricing-tiered --limit=10</code>
        </p>
    </div>
@endsection
