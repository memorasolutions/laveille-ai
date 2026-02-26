@extends('backoffice::layouts.admin', ['title' => 'Shortcodes', 'subtitle' => 'Modifier'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Modifier le shortcode : <code>{{ $shortcode->tag }}</code></h3>
        <a href="{{ route('admin.shortcodes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i> Retour
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.shortcodes.update', $shortcode) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="tag" class="form-label required">Tag</label>
                <input type="text" name="tag" id="tag"
                    class="form-control @error('tag') is-invalid @enderror"
                    value="{{ old('tag', $shortcode->tag) }}" required pattern="[a-z][a-z0-9_]*">
                @error('tag')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="name" class="form-label required">Nom</label>
                <input type="text" name="name" id="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $shortcode->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" rows="2"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $shortcode->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="html_template" class="form-label required">Template HTML</label>
                <textarea name="html_template" id="html_template" rows="4" required
                    class="form-control font-monospace @error('html_template') is-invalid @enderror">{{ old('html_template', $shortcode->html_template) }}</textarea>
                @error('html_template')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="parameters" class="form-label">Paramètres JSON</label>
                <textarea name="parameters" id="parameters" rows="2"
                    class="form-control font-monospace @error('parameters') is-invalid @enderror">{{ old('parameters', $shortcode->parameters ? json_encode($shortcode->parameters) : '') }}</textarea>
                @error('parameters')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-check">
                    <input type="checkbox" name="has_content" value="1"
                        class="form-check-input"
                        {{ old('has_content', $shortcode->has_content) ? 'checked' : '' }}>
                    <span class="form-check-label">Contient du contenu</span>
                </label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i> Mettre à jour
                </button>
                <a href="{{ route('admin.shortcodes.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>

        <hr class="my-4">

        <form method="POST" action="{{ route('admin.shortcodes.destroy', $shortcode) }}"
            onsubmit="return confirm('Supprimer ce shortcode ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="ti ti-trash me-1"></i> Supprimer ce shortcode
            </button>
        </form>
    </div>
</div>
@endsection
