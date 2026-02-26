@extends('backoffice::layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')
@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-20">
        <iconify-icon icon="solar:check-circle-outline" class="icon text-lg"></iconify-icon>
        {{ session('success') }}
    </div>
@endif

<div class="card h-100 p-0 radius-12 mb-20">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:shield-cross-outline" class="icon text-xl"></iconify-icon>
            {{ __('Bloquer une adresse IP') }}
        </h6>
    </div>
    <div class="card-body p-24">
        <form action="{{ route('admin.blocked-ips.store') }}" method="POST" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label text-sm fw-semibold">{{ __('Adresse IP') }}</label>
                <input type="text" name="ip_address" class="form-control radius-8" placeholder="192.168.1.1" required>
                @error('ip_address') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
            <div class="col-md-5">
                <label class="form-label text-sm fw-semibold">{{ __('Raison (optionnel)') }}</label>
                <input type="text" name="reason" class="form-control radius-8" placeholder="{{ __('Tentatives suspectes...') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-danger-600 radius-8 w-100 d-flex align-items-center justify-content-center gap-2">
                    <iconify-icon icon="solar:shield-cross-outline" class="icon"></iconify-icon> {{ __('Bloquer') }}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:shield-warning-outline" class="icon text-xl"></iconify-icon>
            {{ __('IPs bloquées') }} ({{ $blockedIps->total() }})
        </h6>
    </div>
    <div class="card-body p-0">
        @if($blockedIps->isEmpty())
            <div class="text-center py-40">
                <iconify-icon icon="solar:shield-check-outline" class="text-6xl text-success-main mb-16"></iconify-icon>
                <p class="text-secondary-light">{{ __('Aucune IP bloquée.') }}</p>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>{{ __('Raison') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Expire') }}</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($blockedIps as $ip)
                            <tr>
                                <td><code class="text-primary-600 text-sm">{{ $ip->ip_address }}</code></td>
                                <td>{{ $ip->reason ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $ip->auto_blocked ? 'bg-warning-focus text-warning-main' : 'bg-danger-focus text-danger-main' }}">
                                        {{ $ip->auto_blocked ? __('Auto') : __('Manuel') }}
                                    </span>
                                </td>
                                <td class="text-sm text-secondary-light">{{ $ip->blocked_until?->format('Y-m-d H:i') ?? __('Permanent') }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown">
                                            <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-12">
                                            <form action="{{ route('admin.blocked-ips.destroy', $ip) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-success-600" onclick="return confirm('{{ __('Débloquer cette IP ?') }}')">
                                                    <iconify-icon icon="solar:shield-check-outline" class="icon"></iconify-icon> {{ __('Débloquer') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center px-24 py-16">
                <span class="text-secondary-light text-sm">{{ $blockedIps->total() }} {{ __('entrée(s)') }}</span>
                {{ $blockedIps->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
