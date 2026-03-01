@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Modifier la question FAQ')
@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.faqs.index') }}">FAQ</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Modifier la question</h4>
        <a href="{{ route('admin.faqs.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> Retour
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.faqs.update', $faq) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="question" class="form-label">Question <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('question') is-invalid @enderror" id="question" name="question" value="{{ old('question', $faq->question) }}" required maxlength="500">
                    @error('question')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="answer" class="form-label">Réponse <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('answer') is-invalid @enderror" id="answer" name="answer" rows="5" required>{{ old('answer', strip_tags($faq->answer)) }}</textarea>
                    @error('answer')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Catégorie</label>
                        <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $faq->category) }}" maxlength="100" placeholder="Ex: Général, Facturation, Technique...">
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $faq->is_published) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">Publié</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
