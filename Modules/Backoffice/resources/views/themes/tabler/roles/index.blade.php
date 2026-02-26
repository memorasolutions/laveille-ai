@extends('backoffice::layouts.admin', ['title' => 'Rôles', 'subtitle' => 'Liste'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Liste des rôles</h3>
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-plus me-1"></i> Ajouter
        </a>
    </div>
    <div class="card-body">
        @livewire('backoffice-roles-table')
    </div>
</div>
@endsection
