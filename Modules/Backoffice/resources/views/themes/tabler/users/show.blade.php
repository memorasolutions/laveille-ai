@extends('backoffice::layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => $user->name])

@section('content')
<div class="row row-deck row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <span class="avatar avatar-xl rounded-circle mb-3" style="background-color: var(--tblr-primary); color: white; font-size: 2rem;">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </span>
                <h3 class="mb-1">{{ $user->name }}</h3>
                <p class="text-muted">{{ $user->email }}</p>
                <div class="d-flex flex-wrap gap-1 justify-content-center">
                    @foreach($user->roles as $role)
                        <span class="badge bg-primary">{{ $role->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">Informations</h3>
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-edit me-1"></i> Modifier
                </a>
            </div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">Nom</div>
                        <div class="datagrid-content">{{ $user->name }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Courriel</div>
                        <div class="datagrid-content">{{ $user->email }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Email vérifié</div>
                        <div class="datagrid-content">
                            @if($user->email_verified_at)
                                <span class="badge bg-success">Oui</span> {{ $user->email_verified_at->format('d/m/Y H:i') }}
                            @else
                                <span class="badge bg-warning">Non</span>
                            @endif
                        </div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Inscrit le</div>
                        <div class="datagrid-content">{{ $user->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Modifié le</div>
                        <div class="datagrid-content">{{ $user->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Permissions</div>
                        <div class="datagrid-content">
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($user->getAllPermissions() as $perm)
                                    <span class="badge bg-secondary-lt">{{ $perm->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="mt-4">
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-danger">
        <i class="ti ti-arrow-left me-1"></i> Retour à la liste
    </a>
</div>
@endsection
