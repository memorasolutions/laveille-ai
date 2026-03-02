<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Modifier l\'équipe', 'subtitle' => 'Équipes'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.teams.index') }}">Équipes</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.teams.show', $team) }}">{{ $team->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>

@if($errors->any())
    <div class="alert alert-danger mb-4" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h5 class="fw-bold mb-0">
            <i data-lucide="pencil" class="me-2"></i>Modifier : {{ $team->name }}
        </h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.teams.update', $team) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">
                            Nom de l'équipe <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $team->name) }}"
                               required
                               maxlength="255"
                               aria-required="true"
                               autocomplete="off">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="4"
                                  maxlength="1000">{{ old('description', $team->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Maximum 1 000 caractères.</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Informations</h6>

                            <div class="mb-2">
                                <small class="text-muted d-block">Propriétaire</small>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <i data-lucide="crown" class="text-warning" style="width:14px;height:14px"></i>
                                    <span class="fw-semibold">{{ $team->owner->name ?? '—' }}</span>
                                </div>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted d-block">Membres</small>
                                <span class="badge bg-primary rounded-pill mt-1">{{ $team->members->count() }}</span>
                            </div>

                            <div class="mb-0">
                                <small class="text-muted d-block">Créée le</small>
                                <span class="mt-1 d-block">{{ $team->created_at->format('d/m/Y à H\hi') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="check" class="me-1"></i> Enregistrer les modifications
                </button>
                <a href="{{ route('admin.teams.show', $team) }}" class="btn btn-outline-secondary">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
