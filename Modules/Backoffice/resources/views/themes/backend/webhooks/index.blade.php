@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Webhooks', 'subtitle' => 'Intégrations'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Webhooks') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="webhook" class="icon-md text-primary"></i>{{ __('Gestion des webhooks') }}</h4>
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
