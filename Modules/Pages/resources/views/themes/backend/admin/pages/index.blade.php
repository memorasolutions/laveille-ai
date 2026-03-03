<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Pages statiques', 'subtitle' => 'CMS'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Pages') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header d-block py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 fs-5">Pages statiques</h4>
            <a href="{{ route('admin.pages.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                <i data-lucide="plus" class="icon-sm"></i>
                Nouvelle page
            </a>
        </div>
    </div>
    <div class="p-4">
        @livewire('static-pages-table')
    </div>
</div>

@endsection
