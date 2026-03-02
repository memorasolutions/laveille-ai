<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Nouvelle experience')
@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.experiments.index') }}">Experiences A/B</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nouvelle</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Nouvelle experience</h4>
        <a href="{{ route('admin.experiments.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> Retour
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.experiments.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required maxlength="255">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="variants" class="form-label">Variantes <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('variants') is-invalid @enderror" id="variants" name="variants" value="{{ old('variants') }}" placeholder="control, variant_a, variant_b" required>
                    <div class="form-text">Separez les variantes par des virgules.</div>
                    @error('variants')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> Creer l'experience
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
