@extends('backoffice::layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => 'Liste'])

@section('content')

<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-end">
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('admin.export.users') }}" class="btn btn-success-600 text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:export-outline" class="icon text-xl line-height-1"></iconify-icon> Exporter CSV
            </a>
            <a href="{{ route('admin.import.users') }}" class="btn btn-info-600 text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:import-outline" class="icon text-xl line-height-1"></iconify-icon> Importer CSV
            </a>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon> Ajouter
            </a>
        </div>
    </div>
    <div class="card-body p-24">
        @livewire('backoffice-users-table')
    </div>
</div>

@endsection
