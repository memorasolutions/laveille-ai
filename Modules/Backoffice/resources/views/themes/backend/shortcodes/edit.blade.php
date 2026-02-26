@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Shortcodes', 'subtitle' => 'Modifier'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.shortcodes.index') }}">{{ __('Shortcodes') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Modifier') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h5 class="mb-0 fw-semibold">
                Modifier : <code class="text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $shortcode->tag }}</code>
            </h5>
            <a href="{{ route('admin.shortcodes.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                Retour
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.shortcodes.update', $shortcode) }}">
            @csrf
            @method('PUT')

            <div class="row g-4">

                <div class="col-12 col-md-6">
                    <label for="tag" class="form-label fw-medium">
                        Tag <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="tag" id="tag"
                        class="form-control font-monospace @error('tag') is-invalid @enderror"
                        value="{{ old('tag', $shortcode->tag) }}" required pattern="[a-z][a-z0-9_]*">
                    @error('tag')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="name" class="form-label fw-medium">
                        Nom <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $shortcode->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label fw-medium">Description</label>
                    <textarea name="description" id="description" rows="2"
                        class="form-control @error('description') is-invalid @enderror">{{ old('description', $shortcode->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="html_template" class="form-label fw-medium">
                        Template HTML <span class="text-danger">*</span>
                    </label>
                    <textarea name="html_template" id="html_template" rows="4" required
                        class="form-control font-monospace @error('html_template') is-invalid @enderror">{{ old('html_template', $shortcode->html_template) }}</textarea>
                    @error('html_template')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="parameters" class="form-label fw-medium">Paramètres JSON</label>
                    <textarea name="parameters" id="parameters" rows="2"
                        class="form-control font-monospace @error('parameters') is-invalid @enderror">{{ old('parameters', $shortcode->parameters ? json_encode($shortcode->parameters) : '') }}</textarea>
                    @error('parameters')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="fw-medium small">Contient du contenu</div>
                            <div class="text-muted" style="font-size:0.8rem;">Le shortcode accepte du contenu entre les balises ouvrante et fermante</div>
                        </div>
                        <input type="checkbox" name="has_content" value="1"
                            class="form-check-input"
                            {{ old('has_content', $shortcode->has_content) ? 'checked' : '' }}>
                    </div>
                </div>

            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="save" style="width:16px;height:16px;"></i>
                    Mettre à jour
                </button>
                <a href="{{ route('admin.shortcodes.index') }}" class="btn btn-light">Annuler</a>
            </div>
        </form>

        <hr class="my-4">

        <form method="POST" action="{{ route('admin.shortcodes.destroy', $shortcode) }}"
            onsubmit="return confirm('Supprimer ce shortcode ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-2">
                <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                Supprimer ce shortcode
            </button>
        </form>
    </div>
</div>

@endsection
