@extends('backoffice::layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')
{{-- Stats cards --}}
<div class="row mb-20">
    <div class="col-md-3 mb-12">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body text-center p-20">
                <iconify-icon icon="solar:login-3-outline" class="text-primary-600 mb-8" style="font-size: 32px"></iconify-icon>
                <h3 class="fw-bold mb-0">{{ $stats['total_logins'] }}</h3>
                <p class="text-secondary-light text-sm mb-0">{{ __('Connexions (24h)') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-12">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body text-center p-20">
                <iconify-icon icon="solar:check-circle-outline" class="text-success-main mb-8" style="font-size: 32px"></iconify-icon>
                <h3 class="fw-bold text-success-main mb-0">{{ $stats['successful'] }}</h3>
                <p class="text-secondary-light text-sm mb-0">{{ __('Réussies') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-12">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body text-center p-20">
                <iconify-icon icon="solar:close-circle-outline" class="text-danger-main mb-8" style="font-size: 32px"></iconify-icon>
                <h3 class="fw-bold text-danger-main mb-0">{{ $stats['failed'] }}</h3>
                <p class="text-secondary-light text-sm mb-0">{{ __('Échouées') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-12">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body text-center p-20">
                <iconify-icon icon="solar:shield-cross-outline" class="text-warning-main mb-8" style="font-size: 32px"></iconify-icon>
                <h3 class="fw-bold text-warning-main mb-0">{{ $stats['blocked_ips'] }}</h3>
                <p class="text-secondary-light text-sm mb-0">{{ __('IPs bloquées') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Top 5 IPs suspectes --}}
    <div class="col-lg-6 mb-20">
        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:danger-triangle-outline" class="icon text-xl"></iconify-icon>
                    {{ __('Top 5 IPs suspectes (24h)') }}
                </h6>
            </div>
            <div class="card-body p-0">
                @if($suspiciousIps->isEmpty())
                    <div class="text-center py-24">
                        <p class="text-secondary-light mb-0">{{ __('Aucune IP suspecte.') }}</p>
                    </div>
                @else
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table sm-table mb-0">
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th>{{ __('Échecs') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suspiciousIps as $ip)
                                    <tr>
                                        <td><code class="text-primary-600 text-sm">{{ $ip->ip_address }}</code></td>
                                        <td><span class="badge bg-danger-focus text-danger-main">{{ $ip->fail_count }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Dernières tentatives --}}
    <div class="col-lg-6 mb-20">
        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:history-outline" class="icon text-xl"></iconify-icon>
                    {{ __('Dernières tentatives') }}
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table sm-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Statut') }}</th>
                                <th>{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAttempts as $attempt)
                                <tr>
                                    <td class="text-sm">{{ $attempt->email }}</td>
                                    <td>
                                        <span class="badge {{ $attempt->status === 'success' ? 'bg-success-focus text-success-main' : 'bg-danger-focus text-danger-main' }}">
                                            {{ $attempt->status === 'success' ? 'OK' : __('Échec') }}
                                        </span>
                                    </td>
                                    <td class="text-sm text-secondary-light">{{ $attempt->logged_in_at->format('H:i:s') }}</td>
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
