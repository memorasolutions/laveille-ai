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
                    @livewire('ai-article-generator')
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
                <div class="card-header">
                    <h6 class="mb-0">Image mise en avant</h6>
                </div>
                <div class="card-body">
                    <div @click="$refs.featuredImageInput.click()"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; handleFile($event.dataTransfer.files[0])"
                         class="text-center"
                         style="border: 2px dashed #dee2e6; border-radius: 12px; padding: 1.5rem; cursor: pointer; transition: border-color .15s;"
                         :style="{'border-color': isDragging ? '#487fff' : '#dee2e6'}">
                        <template x-if="!preview">
                            <div>
                                <i data-lucide="upload" class="text-muted d-block mb-2 mx-auto" style="width:32px;height:32px;"></i>
                                <p class="mb-1 text-sm fw-medium">Glissez une image ici</p>
                                <p class="mb-0 small text-muted">JPG, PNG, WebP — max 2 Mo</p>
                            </div>
                        </template>
                        <template x-if="preview">
                            <div>
                                <img :src="preview" class="img-fluid rounded mb-2" style="max-height:180px;">
                                <button type="button" class="btn btn-sm btn-outline-secondary d-block mx-auto"
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

@push('css')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
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
