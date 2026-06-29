<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Historique connexions') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="log-in" class="icon-md text-primary"></i>{{ __('Historique de connexion') }}</h4>
    <x-backoffice::help-modal id="helpLoginHistoryModal" :title="__('Historique de connexion')" icon="log-in" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.login-history._help')
    </x-backoffice::help-modal>
</div>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="key-round" class="text-primary icon-md"></i>
            {{ __('Tentatives de connexion') }}
        </h4>
    </div>
    <div class="card-body p-0">
        @livewire('backoffice-login-history-table')
    </div>
</div>

@endsection
