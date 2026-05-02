<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('IPs bloquées') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="shield-ban" class="icon-md text-primary"></i>{{ __('IPs bloquées') }}</h4>
    <x-backoffice::help-modal id="helpBlockedIpsModal" :title="__('IPs bloquées')" icon="shield-ban" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.blocked-ips._help')
    </x-backoffice::help-modal>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
        <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        {{ session('success') }}
    </div>
@endif

{{-- Formulaire blocage IP --}}
<div class="card mb-4">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="x-circle" style="width:20px;height:20px;" class="text-danger"></i>
            <h5 class="mb-0 fw-semibold">{{ __('Bloquer une adresse IP') }}</h5>
        </div>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.blocked-ips.store') }}" method="POST">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-auto flex-grow-1" style="min-width:200px;">
                    <label class="form-label fw-medium">{{ __('Adresse IP') }}</label>
                    <input type="text" name="ip_address"
                           class="form-control @error('ip_address') is-invalid @enderror"
                           placeholder="192.168.1.1" required>
                    @error('ip_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-sm flex-grow-1" style="min-width:260px;">
                    <label class="form-label fw-medium">{{ __('Raison (optionnel)') }}</label>
                    <input type="text" name="reason"
                           class="form-control"
                           placeholder="{{ __('Tentatives suspectes...') }}">
                </div>
                <div class="col-auto">
                    <button type="submit"
                            class="btn btn-danger d-inline-flex align-items-center gap-2">
                        <i data-lucide="x-circle" style="width:16px;height:16px;"></i>
                        {{ __('Bloquer') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Liste des IPs bloquées --}}
<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="alert-triangle" style="width:20px;height:20px;" class="text-warning"></i>
            <h5 class="mb-0 fw-semibold">
                {{ __('IPs bloquées') }}
                <span class="text-muted fw-normal fs-6">({{ $blockedIps->total() }})</span>
            </h5>
        </div>
    </div>

    @if($blockedIps->isEmpty())
        <div class="card-body p-5 text-center">
            <i data-lucide="shield-check" style="width:64px;height:64px;" class="text-success opacity-50 mb-3"></i>
            <p class="text-muted fw-medium mb-0">{{ __('Aucune IP bloquée.') }}</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('IP') }}</th>
                        <th>{{ __('Raison') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Expire') }}</th>
                        <th class="pe-4">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($blockedIps as $ip)
                        <tr>
                            <td class="ps-4">
                                <code class="text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $ip->ip_address }}</code>
                            </td>
                            <td class="text-muted small">{{ $ip->reason ?? '-' }}</td>
                            <td>
                                @if($ip->auto_blocked)
                                    <span class="badge bg-warning bg-opacity-10 text-warning">
                                        {{ __('Auto') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger">
                                        {{ __('Manuel') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $ip->blocked_until?->format('Y-m-d H:i') ?? __('Permanent') }}
                            </td>
                            <td class="pe-4">
                                <div x-data="{ open: false }" class="position-relative d-inline-block">
                                    <button @click="open = !open" @click.outside="open = false" type="button"
                                            class="btn btn-sm btn-light" style="width:36px;height:36px;padding:0;">
                                        <i data-lucide="more-horizontal" style="width:16px;height:16px;"></i>
                                    </button>
                                    <div x-show="open" x-transition
                                         class="position-absolute end-0 mt-1 bg-white rounded border shadow-sm z-3 py-1"
                                         style="min-width:160px;">
                                        <form action="{{ route('admin.blocked-ips.destroy', $ip) }}" method="POST" x-data>
                                            @csrf @method('DELETE')
                                            <button type="button"
                                                    @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js(__('Débloquer cette IP ?')), action: () => $el.closest('form').submit() })"
                                                    class="dropdown-item d-flex align-items-center gap-2 text-success">
                                                <i data-lucide="shield-check" style="width:16px;height:16px;"></i>
                                                {{ __('Débloquer') }}
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
        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top">
            <span class="text-muted small">{{ $blockedIps->total() }} {{ __('entrée(s)') }}</span>
            {{ $blockedIps->links() }}
        </div>
    @endif
</div>

@endsection
