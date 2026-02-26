@extends('backoffice::layouts.admin', ['title' => 'Newsletter', 'subtitle' => 'Abonnés'])

@section('content')

<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:letter-bold" class="icon text-xl"></iconify-icon>
            Liste des abonnés
        </h6>
        <a href="{{ route('admin.newsletter.export') }}"
           class="btn btn-outline-primary-600 text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:download-outline" class="icon text-xl line-height-1"></iconify-icon> Export CSV
        </a>
    </div>
    <div class="card-body p-24">
        @livewire('backoffice-subscribers-table')
    </div>
</div>

@endsection
