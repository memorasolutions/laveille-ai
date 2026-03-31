@extends('backoffice::layouts.admin', ['title' => 'Modifier : ' . $legalPage->title])

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.legal-pages.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <form action="{{ route('admin.legal-pages.update', $legalPage) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Informations</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $legalPage->title) }}">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" value="{{ $legalPage->slug }}" disabled>
                            <small class="text-muted">Non modifiable (lié aux routes)</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $legalPage->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Page active (remplace la version par défaut)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Contenu</h5>
            </div>
            <div class="card-body">
                @if(class_exists(\Modules\Editor\View\Components\Tiptap::class))
                    <x-editor::tiptap name="content" :value="old('content', $legalPage->content ?? '')" label="" />
                @else
                    <textarea class="form-control @error('content') is-invalid @enderror" name="content" rows="20">{{ old('content', $legalPage->content) }}</textarea>
                @endif
                @error('content')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.legal-pages.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Enregistrer
            </button>
        </div>
    </form>
@endsection
