<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Webhooks'), 'subtitle' => __('Intégrations')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Webhooks') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="webhook" class="icon-md text-primary"></i>{{ __('Gestion des webhooks') }}</h4>
    <x-backoffice::help-modal id="helpWebhooksModal" :title="__('Webhooks')" icon="webhook" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.webhooks._help')
    </x-backoffice::help-modal>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i data-lucide="webhook" class="text-primary"></i>
        <h5 class="mb-0 fw-semibold">{{ __('Webhooks') }}</h5>
    </div>
    <div class="card-body">
        @livewire('backoffice-webhooks-manager')
    </div>
</div>

@endsection
