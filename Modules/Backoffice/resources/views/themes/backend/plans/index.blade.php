<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Plans SaaS', 'subtitle' => 'Gestion'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Plans SaaS') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="credit-card" class="icon-md text-primary"></i>{{ __('Plans SaaS') }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.plans.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
            <i data-lucide="plus"></i>
            {{ __('Ajouter') }}
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @livewire('backoffice-plans-table')
    </div>
</div>

@endsection
