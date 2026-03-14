<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Feature Flags'), 'subtitle' => __('Gestion')])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Feature Flags') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="flag" class="icon-md text-primary"></i>{{ __('Feature Flags') }}</h4>
    <x-backoffice::help-modal id="helpFeatureFlagsModal" :title="__('Qu\'est-ce qu\'un Feature Flag ?')" icon="flag" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.feature-flags._help')
    </x-backoffice::help-modal>
</div>

<div class="card">
    <div class="card-body p-4">
        @livewire('backoffice-feature-flags-table')
    </div>
</div>

@endsection
