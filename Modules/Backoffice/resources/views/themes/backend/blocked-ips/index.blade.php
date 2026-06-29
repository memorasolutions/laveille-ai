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

{{-- Formulaire de blocage manuel (reste dans le contrôleur via POST) --}}
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

{{-- Liste Livewire --}}
<div class="card">
    @livewire('backoffice-blocked-ips-table')
</div>

@endsection
