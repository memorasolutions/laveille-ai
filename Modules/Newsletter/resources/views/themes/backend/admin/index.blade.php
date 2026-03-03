<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Newsletter', 'subtitle' => 'Abonnés'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Newsletter') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header d-block py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 fs-5 d-flex align-items-center gap-2">
                <i data-lucide="mail" class="icon-sm text-primary"></i>
                Liste des abonnés
            </h4>
            <a href="{{ route('admin.newsletter.export') }}"
               class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i data-lucide="download" class="icon-sm"></i>
                Export CSV
            </a>
        </div>
    </div>
    <div class="p-4">
        @livewire('backoffice-subscribers-table')
    </div>
</div>

@endsection
