@extends('backoffice::layouts.admin', ['title' => 'Mon profil', 'subtitle' => 'Paramètres'])
@section('content')
@php $user = auth()->user(); @endphp
<div class="row row-deck row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <span class="avatar avatar-xl rounded-circle mb-3" style="background-color: var(--tblr-primary); color: white; font-size: 2rem;">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                <h3>{{ $user->name }}</h3>
                <p class="text-muted">{{ $user->email }}</p>
                <div class="d-flex flex-wrap gap-1 justify-content-center">
                    @foreach($user->getRoleNames() as $role)
                        <span class="badge bg-primary">{{ $role }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Informations personnelles</h3></div>
            <div class="card-body">
                <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Courriel</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="form-control" rows="3">{{ old('bio', $user->bio ?? '') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Avatar</label>
                        <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
                        @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Enregistrer</button>
                </form>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Changer le mot de passe</h3></div>
            <div class="card-body">
                <form action="{{ route('admin.profile.password') }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Mot de passe actuel</label>
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirmer</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning"><i class="ti ti-lock me-1"></i> Changer</button>
                </form>
            </div>
        </div>
        <div class="card border-danger">
            <div class="card-header"><h3 class="card-title text-danger">Zone dangereuse</h3></div>
            <div class="card-body">
                <p class="text-muted">La suppression de votre compte est irréversible.</p>
                <form action="{{ route('admin.profile') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="ti ti-trash me-1"></i> Supprimer mon compte</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
