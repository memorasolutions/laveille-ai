@extends('backoffice::layouts.admin', ['title' => 'Rôles', 'subtitle' => $role->name])

@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0">Rôle : {{ $role->name }}</h6>
        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-primary-600">Modifier</a>
    </div>
    <div class="card-body">
        <div class="mb-20">
            <p class="text-secondary-light mb-1">Guard</p>
            <p class="fw-semibold mb-0">{{ $role->guard_name }}</p>
        </div>

        <div>
            <p class="text-secondary-light mb-8">Permissions ({{ $role->permissions->count() }})</p>
            @if($role->permissions->count())
                <div class="d-flex flex-wrap gap-2">
                    @foreach($role->permissions->groupBy(fn ($p) => explode('_', $p->name)[0] ?? 'other') as $group => $perms)
                        @foreach($perms as $perm)
                            <span class="badge bg-primary-100 text-primary-600">{{ $perm->name }}</span>
                        @endforeach
                    @endforeach
                </div>
            @else
                <p class="text-secondary-light">Aucune permission</p>
            @endif
        </div>
    </div>
</div>

<div class="d-flex gap-3 mt-24">
    <a href="{{ route('admin.roles.index') }}" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">Retour à la liste</a>
</div>

@endsection
