<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Notifications'), 'subtitle' => __('Liste')])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Notifications') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="bell" class="icon-md text-primary"></i>{{ __('Notifications') }}</h4>
    <x-backoffice::help-modal id="helpNotificationsModal" :title="__('Notifications')" icon="bell" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.notifications._help')
    </x-backoffice::help-modal>
</div>

{{-- Diffuser une alerte système (reste dans le contrôleur via POST) --}}
@can('manage_notifications')
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0 fw-semibold">{{ __('Diffuser une alerte système') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.notifications.broadcast') }}" method="POST">
            @csrf
            <div class="d-flex flex-wrap gap-3">
                <div style="width:12rem;">
                    <label class="form-label fw-semibold small">{{ __('Niveau') }}</label>
                    <select name="level"
                            class="form-select form-select-sm @error('level') is-invalid @enderror">
                        <option value="info" {{ old('level') === 'info' ? 'selected' : '' }}>{{ __('Information') }}</option>
                        <option value="warning" {{ old('level') === 'warning' ? 'selected' : '' }}>{{ __('Avertissement') }}</option>
                        <option value="critical" {{ old('level') === 'critical' ? 'selected' : '' }}>{{ __('Critique') }}</option>
                    </select>
                    @error('level')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="flex-grow-1" style="min-width:260px;">
                    <label class="form-label fw-semibold small">
                        {{ __('Message') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="message" rows="2"
                              class="form-control form-control-sm @error('message') is-invalid @enderror"
                              placeholder="{{ __('Message à diffuser à tous les utilisateurs...') }}">{{ old('message') }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                    <i data-lucide="bell"></i>
                    {{ __('Diffuser à tous les utilisateurs') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

{{-- Liste des notifications --}}
<div class="card">
    <div class="card-body p-0">
        @livewire('backoffice-notifications-table')
    </div>
</div>

@endsection
