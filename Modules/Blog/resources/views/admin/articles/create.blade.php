<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Nouvel article', 'subtitle' => 'Blog'])

@section('content')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('admin.blog.articles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row gy-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Contenu</h6>
                    @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::find('AI')?->isEnabled())
                        @livewire('ai-article-generator')
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <x-editor::tiptap name="content" :value="old('content', '')" label="Contenu" />
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Extrait</label>
                        <textarea name="excerpt" class="form-control" rows="3" maxlength="500">{{ old('excerpt') }}</textarea>
                        <div class="form-text">Résumé court affiché dans les listes (max 500 caractères)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Publication --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Publication</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <div class="btn-group w-100" role="group" aria-label="Statut de publication">
                            <input type="radio" class="btn-check" name="status" value="draft" id="status-draft" autocomplete="off" {{ old('status', 'draft') === 'draft' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="status-draft">Brouillon</label>
                            <input type="radio" class="btn-check" name="status" value="pending_review" id="status-pending" autocomplete="off" {{ old('status') === 'pending_review' ? 'checked' : '' }}>
                            <label class="btn btn-outline-info" for="status-pending">{{ __('En révision') }}</label>
                            <input type="radio" class="btn-check" name="status" value="published" id="status-published" autocomplete="off" {{ old('status') === 'published' ? 'checked' : '' }}>
                            <label class="btn btn-outline-success" for="status-published">Publié</label>
                            <input type="radio" class="btn-check" name="status" value="archived" id="status-archived" autocomplete="off" {{ old('status') === 'archived' ? 'checked' : '' }}>
                            <label class="btn btn-outline-warning" for="status-archived">Archivé</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date de publication</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="{{ old('published_at') }}">
                    </div>
                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.blog.articles.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </div>
            </div>

            {{-- Catégorie et tags --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Catégorie et tags</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="category-select" class="form-label">Catégorie</label>
                        <select id="category-select" name="category_id" class="form-select" aria-label="Sélectionner une catégorie">
                            <option value="">— Sélectionner —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Tapez pour rechercher ou créer une catégorie</div>
                    </div>
                    <div class="mb-0">
                        <label for="tags-select" class="form-label">Tags</label>
                        <select id="tags-select" multiple aria-label="Sélectionner des tags">
                            @foreach($existingTags as $tag)
                                <option value="{{ $tag }}" {{ in_array($tag, old('tags', [])) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="tags_input" id="tags-input" value="{{ old('tags_input', '') }}">
                        <div class="form-text">Tapez pour rechercher ou créer un tag</div>
                    </div>
                </div>
            </div>

            {{-- Image mise en avant --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Image mise en avant</h6>
                </div>
                <div class="card-body">
                    <x-core::file-upload name="featured_image" accept="image/jpeg,image/png,image/webp" :max-size="2" help-text="JPG, PNG ou WebP. Max 2 Mo." />
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('css')
<link href="{{ asset('build/nobleui/plugins/tom-select/tom-select.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('js')
<script src="{{ asset('build/nobleui/plugins/tom-select/tom-select.complete.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category: single select with inline creation
    new TomSelect('#category-select', {
        create: function(input, callback) {
            fetch('{{ route("admin.blog.categories.quick-create") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: input })
            })
            .then(r => r.json())
            .then(data => callback({ value: data.id, text: data.name }))
            .catch(() => callback());
        },
        render: {
            option_create: function(data, escape) {
                return '<div class="create">Créer <strong>' + escape(data.input) + '</strong></div>';
            }
        },
        placeholder: 'Rechercher ou créer...',
        allowEmptyOption: true
    });

    // Tags: multi-select with inline creation
    var tagsSelect = new TomSelect('#tags-select', {
        create: true,
        plugins: ['remove_button'],
        placeholder: 'Rechercher ou créer un tag...',
        render: {
            option_create: function(data, escape) {
                return '<div class="create">Créer le tag <strong>' + escape(data.input) + '</strong></div>';
            }
        },
        onInitialize: function() {
            var raw = document.getElementById('tags-input').value;
            if (raw) {
                var tags = raw.split(',').filter(function(t) { return t.trim() !== ''; });
                for (var i = 0; i < tags.length; i++) {
                    this.addOption({ value: tags[i].trim(), text: tags[i].trim() });
                    this.addItem(tags[i].trim(), true);
                }
            }
        },
        onChange: function(values) {
            document.getElementById('tags-input').value = values.join(',');
        }
    });
});
</script>
@endpush
