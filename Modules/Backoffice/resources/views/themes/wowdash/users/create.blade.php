@extends('backoffice::layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => 'Ajouter'])

@section('content')

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Ajouter un utilisateur</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div class="row gy-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nom <span class="text-danger-main">*</span></label>
                    <input type="text" name="name" class="form-control radius-8 @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Courriel <span class="text-danger-main">*</span></label>
                    <input type="email" name="email" class="form-control radius-8 @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Mot de passe <span class="text-danger-main">*</span></label>
                    <input type="password" name="password" class="form-control radius-8 @error('password') is-invalid @enderror" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Confirmer le mot de passe <span class="text-danger-main">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control radius-8" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Rôles</label>
                    @php
                        $roleLabels = [
                            'super_admin' => 'Super administrateur',
                            'admin' => 'Administrateur',
                            'user' => 'Utilisateur',
                        ];
                    @endphp
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($roles as $id => $name)
                            <div class="form-check d-flex align-items-center" style="gap: 0.5rem;">
                                <input class="form-check-input mt-0" type="checkbox" name="roles[]" value="{{ $id }}" id="role_{{ $id }}" @checked(in_array($id, old('roles', [])))>
                                <label class="form-check-label" for="role_{{ $id }}">{{ $roleLabels[$name] ?? ucfirst(str_replace('_', ' ', $name)) }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('roles') <div class="text-danger-main text-sm">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="d-flex gap-3 mt-24">
                <button type="submit" class="btn btn-primary-600">Enregistrer</button>
                <a href="{{ route('admin.users.index') }}" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">Annuler</a>
            </div>
        </form>
    </div>
</div>

@endsection
