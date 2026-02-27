@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Modifier : ' . $testimonial->author_name, 'subtitle' => 'Témoignages'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.testimonials.index') }}">Témoignages</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h5 class="fw-bold mb-0">Modifier : {{ $testimonial->author_name }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.testimonials.update', $testimonial) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label for="author_name" class="form-label">Nom de l'auteur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('author_name') is-invalid @enderror" id="author_name" name="author_name" value="{{ old('author_name', $testimonial->author_name) }}" required>
                        @error('author_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="author_title" class="form-label">Titre / Fonction</label>
                        <input type="text" class="form-control @error('author_title') is-invalid @enderror" id="author_title" name="author_title" value="{{ old('author_title', $testimonial->author_title) }}" placeholder="Ex: CEO de Entreprise X">
                        @error('author_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Témoignage <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="4" required>{{ old('content', strip_tags($testimonial->content)) }}</textarea>
                        @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label for="rating" class="form-label">Note</label>
                        <select class="form-select @error('rating') is-invalid @enderror" id="rating" name="rating">
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ old('rating', $testimonial->rating) == $i ? 'selected' : '' }}>{{ $i }} étoile{{ $i > 1 ? 's' : '' }} {{ str_repeat('★', $i) }}</option>
                            @endfor
                        </select>
                        @error('rating')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_approved" name="is_approved" value="1" {{ old('is_approved', $testimonial->is_approved) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_approved">Approuvé (visible publiquement)</label>
                        </div>
                    </div>

                    <div class="card bg-light">
                        <div class="card-body py-2 px-3">
                            <small class="text-muted d-block">Créé le {{ $testimonial->created_at->format('d/m/Y H:i') }}</small>
                            <small class="text-muted d-block">Ordre : {{ $testimonial->order }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" class="me-1"></i> Mettre à jour
                </button>
                <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

@endsection
