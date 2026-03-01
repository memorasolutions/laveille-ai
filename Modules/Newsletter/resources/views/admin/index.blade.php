<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Newsletter', 'subtitle' => 'Abonnés'])

@section('content')

<div class="card h-100 p-0">
    <div class="card-header border-bottom py-3 px-4 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <i data-lucide="mail"></i>
            Liste des abonnés
        </h6>
        <a href="{{ route('admin.newsletter.export') }}"
           class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
            <i data-lucide="download"></i> Export CSV
        </a>
    </div>
    <div class="card-body p-4">
        @livewire('backoffice-subscribers-table')
    </div>
</div>

@endsection
