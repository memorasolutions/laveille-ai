<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Paramètres'), 'subtitle' => __('Configuration')])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Paramètres') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="settings" class="icon-md text-primary"></i>{{ __('Paramètres') }}</h4>
    <div class="d-flex gap-2">
        <x-backoffice::help-modal id="helpSettingsModal" :title="__('Paramètres')" icon="settings" :buttonLabel="__('Aide')">
            @include('backoffice::themes.backend.settings._help')
        </x-backoffice::help-modal>
        <a href="{{ route('admin.settings.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
            <i data-lucide="plus"></i>
            {{ __('Ajouter un paramètre') }}
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @livewire('backoffice-settings-manager')
    </div>
</div>

@endsection
