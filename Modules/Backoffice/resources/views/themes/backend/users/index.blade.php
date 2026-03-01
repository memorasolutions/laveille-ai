<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => 'Liste'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Utilisateurs') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="users" class="icon-md text-primary"></i>{{ __('Utilisateurs') }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.export.users') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-2">
            <i data-lucide="download"></i>
            {{ __('Exporter CSV') }}
        </a>
        <a href="{{ route('admin.import.users') }}" class="btn btn-sm btn-info d-inline-flex align-items-center gap-2">
            <i data-lucide="upload"></i>
            {{ __('Importer CSV') }}
        </a>
        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
            <i data-lucide="plus"></i>
            {{ __('Ajouter') }}
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @livewire('backoffice-users-table')
    </div>
</div>

@endsection
