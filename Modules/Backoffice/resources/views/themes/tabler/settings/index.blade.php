@extends('backoffice::layouts.admin', ['title' => 'Paramètres', 'subtitle' => 'Configuration'])

@section('content')
<div class="d-flex align-items-center justify-content-end mb-3">
    <a href="{{ route('admin.settings.create') }}" class="btn btn-primary btn-sm">
        <i class="ti ti-plus me-1"></i> Ajouter un paramètre
    </a>
</div>
@livewire('backoffice-settings-manager')
@endsection
