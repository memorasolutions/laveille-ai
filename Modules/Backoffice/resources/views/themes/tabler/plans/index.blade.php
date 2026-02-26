@extends('backoffice::layouts.admin', ['title' => 'Plans', 'subtitle' => 'Liste'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Liste des plans</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.export.plans') }}" class="btn btn-success btn-sm">
                <i class="ti ti-download me-1"></i> Exporter CSV
            </a>
            <a href="{{ route('admin.import.plans') }}" class="btn btn-info btn-sm">
                <i class="ti ti-upload me-1"></i> Importer CSV
            </a>
            <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i> Ajouter
            </a>
        </div>
    </div>
    <div class="card-body">
        @livewire('backoffice-plans-table')
    </div>
</div>
@endsection
