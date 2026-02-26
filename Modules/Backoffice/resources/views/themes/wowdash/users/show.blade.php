@extends('backoffice::layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => $user->name])

@section('content')

<div class="row gy-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="w-80-px h-80-px bg-primary-600 text-white rounded-circle d-flex justify-content-center align-items-center mx-auto mb-16" style="font-size: 2rem;">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <h6 class="mb-4">{{ $user->name }}</h6>
                <p class="text-secondary-light mb-16">{{ $user->email }}</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach($user->roles as $role)
                        <span class="badge bg-primary-600">{{ $role->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0">Informations</h6>
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary-600">Modifier</a>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-sm-6">
                        <p class="text-secondary-light mb-1">Nom</p>
                        <p class="fw-semibold mb-0">{{ $user->name }}</p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-secondary-light mb-1">Courriel</p>
                        <p class="fw-semibold mb-0">{{ $user->email }}</p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-secondary-light mb-1">Courriel vérifié</p>
                        <p class="fw-semibold mb-0">
                            @if($user->email_verified_at)
                                <span class="text-success-main">{{ $user->email_verified_at->format('d/m/Y H:i') }}</span>
                            @else
                                <span class="text-warning-main">Non vérifié</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-secondary-light mb-1">Inscrit le</p>
                        <p class="fw-semibold mb-0">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-secondary-light mb-1">Dernière modification</p>
                        <p class="fw-semibold mb-0">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="col-sm-6">
                        <p class="text-secondary-light mb-1">Permissions</p>
                        <p class="fw-semibold mb-0">{{ $user->getAllPermissions()->count() }} permissions</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-3 mt-24">
    <a href="{{ route('admin.users.index') }}" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">Retour à la liste</a>
</div>

@endsection
