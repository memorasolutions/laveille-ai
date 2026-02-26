@extends('backoffice::layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => 'Ajouter'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ajouter un utilisateur</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">Nom</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Courriel</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">Mot de passe</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Rôles</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($roles as $id => $name)
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $id }}" {{ in_array($id, old('roles', [])) ? 'checked' : '' }}>
                        <span class="form-check-label">{{ $roleLabels[$name] ?? ucfirst($name) }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Enregistrer</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-danger">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
