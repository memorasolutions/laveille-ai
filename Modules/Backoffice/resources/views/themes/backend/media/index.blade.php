<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Médias'), 'subtitle' => __('Bibliothèque')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Médias') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="image" class="icon-md text-primary"></i>{{ __('Médiathèque') }}</h4>
    <x-backoffice::help-modal id="helpMediaModal" :title="__('Médiathèque')" icon="image" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.media._help')
    </x-backoffice::help-modal>
</div>

<div class="card">
    <div class="p-4">
        @livewire('backoffice-media-table')
    </div>
</div>

@endsection

@push('plugin-styles')
<link href="{{ asset('build/nobleui/plugins/cropperjs/cropper.css') }}" rel="stylesheet">
@endpush

@push('custom-scripts')
<script src="{{ asset('build/nobleui/plugins/cropperjs/cropper.min.js') }}"></script>
@endpush
