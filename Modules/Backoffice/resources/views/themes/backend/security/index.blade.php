<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Sécurité') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="lock" class="icon-md text-primary"></i>{{ __('Sécurité') }}</h4>
    <x-backoffice::help-modal id="helpSecurityModal" :title="__('Sécurité')" icon="lock" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.security._help')
    </x-backoffice::help-modal>
</div>

<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px;">
                    <i data-lucide="log-in" class="icon-lg text-primary"></i>
                </div>
                <h4 class="fw-bold mb-1">{{ $stats['total_logins'] }}</h4>
                <p class="text-muted small">{{ __('Connexions (24h)') }}</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px;">
                    <i data-lucide="check-circle" class="icon-lg text-success"></i>
                </div>
                <h4 class="fw-bold mb-1 text-success">{{ $stats['successful'] }}</h4>
                <p class="text-muted small">{{ __('Réussies') }}</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px;">
                    <i data-lucide="x-circle" class="icon-lg text-danger"></i>
                </div>
                <h4 class="fw-bold mb-1 text-danger">{{ $stats['failed'] }}</h4>
                <p class="text-muted small">{{ __('Échouées') }}</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px;">
                    <i data-lucide="ban" class="icon-lg text-warning"></i>
                </div>
                <h4 class="fw-bold mb-1 text-warning">{{ $stats['blocked_ips'] }}</h4>
                <p class="text-muted small">{{ __('IPs bloquées') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="alert-triangle" class="icon-sm text-danger"></i>
                    {{ __('Top 5 IPs suspectes (24h)') }}
                </h4>
            </div>
            <div class="card-body p-4">
                @if($suspiciousIps->isEmpty())
                    <div class="text-center py-5">
                        <p class="text-muted small">{{ __('Aucune IP suspecte.') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 fw-semibold text-body">IP</th>
                                    <th class="py-3 px-4 fw-semibold text-body">{{ __('Échecs') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suspiciousIps as $ip)
                                <tr>
                                    <td class="py-3 px-4">
                                        <code class="text-primary small">{{ $ip->ip_address }}</code>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="badge bg-danger bg-opacity-10 text-danger">
                                            {{ $ip->fail_count }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="clock" class="icon-sm text-primary"></i>
                    {{ __('Dernières tentatives') }}
                </h4>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="py-3 px-4 fw-semibold text-body">{{ __('Email') }}</th>
                                <th class="py-3 px-4 fw-semibold text-body">{{ __('Statut') }}</th>
                                <th class="py-3 px-4 fw-semibold text-body">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAttempts as $attempt)
                            <tr>
                                <td class="py-3 px-4 text-muted small">{{ $attempt->email }}</td>
                                <td class="py-3 px-4">
                                    <span class="badge {{ $attempt->status === 'success' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                        {{ $attempt->status === 'success' ? 'OK' : __('Échec') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-muted small">
                                    {{ $attempt->logged_in_at->format('H:i:s') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
