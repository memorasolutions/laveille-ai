@extends('backoffice::layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => 'Liste'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Liste des utilisateurs</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.export.users') }}" class="btn btn-success btn-sm">
                <i class="ti ti-download me-1"></i> Exporter CSV
            </a>
            <a href="{{ route('admin.import.users') }}" class="btn btn-info btn-sm">
                <i class="ti ti-upload me-1"></i> Importer CSV
            </a>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i> Ajouter
            </a>
        </div>
    </div>
    <div class="card-body">
        @livewire('backoffice-users-table')
    </div>
</div>
@endsection
