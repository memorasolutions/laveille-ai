<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Modifier l\'article', 'subtitle' => 'Blog'])

@section('content')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('admin.blog.articles.preview', $article) }}" target="_blank"
       class="btn btn-sm btn-outline-info rounded-2 d-flex align-items-center gap-2">
        <i data-lucide="eye"></i>
        {{ __('Apercu') }}
    </a>
    <a href="{{ route('admin.blog.articles.revisions', $article) }}" class="btn btn-sm btn-outline-primary rounded-2 d-flex align-items-center gap-2">
        <i data-lucide="history"></i>
        {{ __('Historique') }}
        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $article->revisions()->count() }}</span>
    </a>
</div>

<form action="{{ route('admin.blog.articles.update', $article) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row gy-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Contenu</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $article->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <x-editor::tiptap name="content" :value="old('content', $article->content ?? '')" label="Contenu" />
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Extrait</label>
                        <textarea name="excerpt" class="form-control" rows="3" maxlength="500">{{ old('excerpt', $article->excerpt) }}</textarea>
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
                            <input type="radio" class="btn-check" name="status" value="draft" id="status-draft" autocomplete="off" {{ old('status', (string) $article->status) === 'draft' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="status-draft">Brouillon</label>
                            <input type="radio" class="btn-check" name="status" value="pending_review" id="status-pending" autocomplete="off" {{ old('status', (string) $article->status) === 'pending_review' ? 'checked' : '' }}>
                            <label class="btn btn-outline-info" for="status-pending">{{ __('En révision') }}</label>
                            <input type="radio" class="btn-check" name="status" value="published" id="status-published" autocomplete="off" {{ old('status', (string) $article->status) === 'published' ? 'checked' : '' }}>
                            <label class="btn btn-outline-success" for="status-published">Publié</label>
                            <input type="radio" class="btn-check" name="status" value="archived" id="status-archived" autocomplete="off" {{ old('status', (string) $article->status) === 'archived' ? 'checked' : '' }}>
                            <label class="btn btn-outline-warning" for="status-archived">Archivé</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date de publication</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.blog.articles.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                    @if($article->status === 'published')
                        <form action="{{ route('admin.blog.articles.unpublish', $article->id) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning w-100">Dépublier</button>
                        </form>
                    @else
                        <form action="{{ route('admin.blog.articles.publish', $article->id) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success w-100">Publier</button>
                        </form>
                    @endif
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
                                <option value="{{ $cat->id }}" {{ old('category_id', $article->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Tapez pour rechercher ou créer une catégorie</div>
                    </div>
                    <div class="mb-0">
                        <label for="tags-select" class="form-label">Tags</label>
                        <select id="tags-select" multiple aria-label="Sélectionner des tags">
                            @foreach($existingTags as $tag)
                                <option value="{{ $tag }}" {{ in_array($tag, $article->tags ?? []) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="tags_input" id="tags-input" value="{{ implode(',', $article->tags ?? []) }}">
                        <div class="form-text">Tapez pour rechercher ou créer un tag</div>
                    </div>
                </div>
            </div>

            {{-- Vidéo YouTube --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Vidéo YouTube</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">URL YouTube</label>
                        <input type="url" name="video_url" class="form-control @error('video_url') is-invalid @enderror"
                               value="{{ old('video_url', $article->video_url) }}" placeholder="https://youtube.com/watch?v=...">
                        @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if($article->video_url)
                        <button type="button" id="btn-yt-summary" class="btn btn-sm btn-outline-primary mb-2" onclick="generateYouTubeSummary()">
                            <i data-lucide="sparkles"></i> Générer le résumé IA
                        </button>
                    @endif
                    <div id="yt-summary-result" class="{{ $article->video_summary ? '' : 'd-none' }}">
                        <label class="form-label">Résumé IA</label>
                        <div id="yt-summary-content" class="border rounded p-2 bg-light" style="font-size:13px;max-height:300px;overflow-y:auto;">
                            {!! \Illuminate\Support\Str::markdown($article->video_summary ?? '') !!}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Image mise en avant --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Image mise en avant</h6>
                </div>
                <div class="card-body">
                    @if($article->featured_image)
                        <img src="{{ asset($article->featured_image) }}"
                             class="img-thumbnail mb-2 w-100" style="max-height:150px;object-fit:cover;">
                    @endif
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                    <div class="form-text">Laissez vide pour conserver l'image actuelle</div>
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
window.generateYouTubeSummary = function() {
    var btn = document.getElementById('btn-yt-summary');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Extraction en cours...';
    fetch('{{ route("admin.blog.articles.youtube-summary", $article) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="sparkles"></i> Générer le résumé IA';
        if (data.error) { alert(data.error); return; }
        document.getElementById('yt-summary-result').classList.remove('d-none');
        document.getElementById('yt-summary-content').innerHTML = data.summary;
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="sparkles"></i> Générer le résumé IA';
        alert('Erreur lors de la génération du résumé.');
    });
};

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
