@extends('backoffice::layouts.admin', ['title' => 'Rôles', 'subtitle' => $role->name])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Rôle : {{ $role->name }}</h3>
        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-primary">
            <i class="ti ti-edit me-1"></i> Modifier
        </a>
    </div>
    <div class="card-body">
        <h4>Permissions ({{ $role->permissions->count() }})</h4>
        <div class="d-flex flex-wrap gap-2">
            @forelse($role->permissions as $permission)
                <span class="badge bg-primary-lt">{{ $permission->name }}</span>
            @empty
                <span class="text-muted">Aucune permission assignée</span>
            @endforelse
        </div>
    </div>
</div>
<div class="mt-4">
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-danger">
        <i class="ti ti-arrow-left me-1"></i> Retour à la liste
    </a>
</div>
@endsection
