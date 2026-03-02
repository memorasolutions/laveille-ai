<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Nouvel article', 'subtitle' => 'Blog'])

@section('breadcrumbs')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item">Blog</li>
        <li class="breadcrumb-item"><a href="{{ route('admin.blog.articles.index') }}">Articles</a></li>
        <li class="breadcrumb-item active" aria-current="page">Créer</li>
    </ol>
</nav>
@endsection

@section('content')

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)
                <li class="text-sm">{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.blog.articles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        {{-- Colonne principale --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-semibold mb-0">Contenu</h5>
                    @livewire('ai-article-generator')
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Titre <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <x-editor::tiptap name="content" :value="old('content', '')" label="Contenu" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Extrait</label>
                        <textarea name="excerpt" rows="3" maxlength="500"
                                  class="form-control" style="resize:none;">{{ old('excerpt') }}</textarea>
                        <div class="form-text text-muted">Résumé court affiché dans les listes (max 500 caractères)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div class="col-xl-4">
            {{-- Publication --}}
            <div class="card mb-3">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="fw-semibold mb-0">Publication</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Statut</label>
                        <div class="btn-group w-100" role="group" aria-label="Statut de publication">
                            <input type="radio" class="btn-check" name="status" value="draft" id="status-draft" autocomplete="off" {{ old('status', 'draft') === 'draft' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1" for="status-draft">
                                <i data-lucide="file-edit" class="icon-sm"></i> Brouillon
                            </label>
                            <input type="radio" class="btn-check" name="status" value="published" id="status-published" autocomplete="off" {{ old('status') === 'published' ? 'checked' : '' }}>
                            <label class="btn btn-outline-success d-inline-flex align-items-center justify-content-center gap-1" for="status-published">
                                <i data-lucide="globe" class="icon-sm"></i> Publié
                            </label>
                            <input type="radio" class="btn-check" name="status" value="archived" id="status-archived" autocomplete="off" {{ old('status') === 'archived' ? 'checked' : '' }}>
                            <label class="btn btn-outline-warning d-inline-flex align-items-center justify-content-center gap-1" for="status-archived">
                                <i data-lucide="archive" class="icon-sm"></i> Archivé
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Date de publication</label>
                        <input type="datetime-local" name="published_at"
                               class="form-control"
                               value="{{ old('published_at') }}">
                    </div>
                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary flex-fill">Enregistrer</button>
                        <a href="{{ route('admin.blog.articles.index') }}" class="btn btn-outline-secondary flex-fill text-center">Annuler</a>
                    </div>
                </div>
            </div>

            {{-- Catégorie et tags --}}
            <div class="card mb-3">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="fw-semibold mb-0">Catégorie et tags</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="category-select" class="form-label fw-medium">Catégorie</label>
                        <select id="category-select" name="category_id"
                                class="form-select"
                                aria-label="Sélectionner une catégorie">
                            <option value="">— Sélectionner —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">Tapez pour rechercher ou créer une catégorie</div>
                    </div>
                    <div class="mb-3">
                        <label for="tags-select" class="form-label fw-medium">Tags</label>
                        <select id="tags-select" multiple aria-label="Sélectionner des tags"
                                class="form-select">
                            @foreach($existingTags as $tag)
                                <option value="{{ $tag }}" {{ in_array($tag, old('tags', [])) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="tags_input" id="tags-input" value="{{ old('tags_input', '') }}">
                        <div class="form-text text-muted">Tapez pour rechercher ou créer un tag</div>
                    </div>
                </div>
            </div>

            {{-- Image mise en avant --}}
            <div class="card" x-data="{
                isDragging: false,
                preview: null,
                handleFile(file) {
                    if (file && ['image/jpeg','image/png','image/webp'].includes(file.type) && file.size <= 2097152) {
                        const reader = new FileReader();
                        reader.onload = (e) => { this.preview = e.target.result; };
                        reader.readAsDataURL(file);
                    } else {
                        alert('Image JPG/PNG/WebP max 2 Mo.');
                        this.$refs.featuredImageInput.value = '';
                    }
                }
            }">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="fw-semibold mb-0">Image mise en avant</h5>
                </div>
                <div class="card-body p-4">
                    <div @click="$refs.featuredImageInput.click()"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; handleFile($event.dataTransfer.files[0])"
                         class="text-center rounded-3 border border-2 border-dashed p-4"
                         :class="isDragging ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary-subtle'"
                         style="cursor:pointer;">
                        <template x-if="!preview">
                            <div>
                                <i data-lucide="upload" class="text-muted d-block mx-auto mb-3" style="width:48px;height:48px;"></i>
                                <p class="text-sm fw-medium mb-1">Glissez une image ici</p>
                                <p class="text-muted small">JPG, PNG, WebP — max 2 Mo</p>
                            </div>
                        </template>
                        <template x-if="preview">
                            <div>
                                <img :src="preview" class="w-100 rounded-3 mb-3" style="max-height:11rem;object-fit:cover;">
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        @click.stop="preview = null; $refs.featuredImageInput.value = ''">
                                    Changer
                                </button>
                            </div>
                        </template>
                    </div>
                    <input type="file" name="featured_image"
                           x-ref="featuredImageInput"
                           @change="handleFile($event.target.files[0])"
                           class="d-none"
                           accept="image/jpeg,image/png,image/webp">
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('plugin-styles')
<link href="{{ asset('build/nobleui/plugins/tom-select/tom-select.bootstrap5.min.css') }}" rel="stylesheet">
@endpush

@push('custom-scripts')
<script src="{{ asset('build/nobleui/plugins/tom-select/tom-select.complete.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
