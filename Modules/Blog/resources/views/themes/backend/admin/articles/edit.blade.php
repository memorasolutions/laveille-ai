<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Modifier l\'article', 'subtitle' => 'Blog'])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item">Blog</li>
        <li class="breadcrumb-item"><a href="{{ route('admin.blog.articles.index') }}">Articles</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
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

<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('admin.blog.articles.preview', $article) }}" target="_blank"
       class="btn btn-outline-info d-flex align-items-center gap-2">
        <i data-lucide="eye"></i>
        {{ __('Apercu') }}
    </a>
    @if($article->preview_token)
        <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2"
                onclick="navigator.clipboard.writeText('{{ route('preview.show', $article->preview_token) }}').then(() => this.querySelector('span').textContent = '{{ __('Copié !') }}')">
            <i data-lucide="share-2"></i>
            <span>{{ __('Lien de prévisualisation') }}</span>
        </button>
    @endif
    <a href="{{ route('admin.blog.articles.revisions', $article) }}"
       class="btn btn-outline-secondary d-flex align-items-center gap-2">
        <i data-lucide="history"></i>
        {{ __('Historique') }}
        <span class="badge bg-primary bg-opacity-10 text-primary">{{ $article->revisions()->count() }}</span>
    </a>
</div>

<form action="{{ route('admin.blog.articles.update', $article) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row g-3">
        {{-- Colonne principale --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="fw-semibold mb-0">Contenu</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Titre <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $article->title) }}" required aria-required="true" autocomplete="off">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <x-editor::tiptap name="content" :value="old('content', $article->content ?? '')" label="Contenu" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Extrait</label>
                        <textarea name="excerpt" rows="3" maxlength="500"
                                  class="form-control" style="resize:none;">{{ old('excerpt', $article->excerpt) }}</textarea>
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
                            <input type="radio" class="btn-check" name="status" value="draft" id="status-draft" autocomplete="off" {{ old('status', (string) $article->status) === 'draft' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center gap-1" for="status-draft">
                                <i data-lucide="file-edit" class="icon-sm"></i> Brouillon
                            </label>
                            <input type="radio" class="btn-check" name="status" value="pending_review" id="status-pending" autocomplete="off" {{ old('status', (string) $article->status) === 'pending_review' ? 'checked' : '' }}>
                            <label class="btn btn-outline-info d-inline-flex align-items-center justify-content-center gap-1" for="status-pending">
                                <i data-lucide="eye" class="icon-sm"></i> {{ __('En révision') }}
                            </label>
                            <input type="radio" class="btn-check" name="status" value="published" id="status-published" autocomplete="off" {{ old('status', (string) $article->status) === 'published' ? 'checked' : '' }}>
                            <label class="btn btn-outline-success d-inline-flex align-items-center justify-content-center gap-1" for="status-published">
                                <i data-lucide="globe" class="icon-sm"></i> Publié
                            </label>
                            <input type="radio" class="btn-check" name="status" value="archived" id="status-archived" autocomplete="off" {{ old('status', (string) $article->status) === 'archived' ? 'checked' : '' }}>
                            <label class="btn btn-outline-warning d-inline-flex align-items-center justify-content-center gap-1" for="status-archived">
                                <i data-lucide="archive" class="icon-sm"></i> Archivé
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Date de publication</label>
                        <input type="datetime-local" name="published_at"
                               class="form-control"
                               value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary flex-fill">Enregistrer</button>
                        <a href="{{ route('admin.blog.articles.index') }}" class="btn btn-outline-secondary flex-fill text-center">Annuler</a>
                    </div>
                    <div class="mt-2 text-end">
                        <small id="autosave-status" class="text-muted" style="transition:opacity .5s"></small>
                    </div>
                    @if($article->status === 'published')
                        <form action="{{ route('admin.blog.articles.unpublish', $article->id) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="w-100 btn btn-outline-warning">Dépublier</button>
                        </form>
                    @else
                        <form action="{{ route('admin.blog.articles.publish', $article->id) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="w-100 btn btn-outline-success">Publier</button>
                        </form>
                    @endif
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
                                <option value="{{ $cat->id }}" {{ old('category_id', $article->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">Tapez pour rechercher ou créer une catégorie</div>
                    </div>
                    <div class="mb-3">
                        <label for="tags-select" class="form-label fw-medium">Tags</label>
                        <select id="tags-select" multiple aria-label="Sélectionner des tags"
                                class="form-select">
                            @foreach($existingTags as $tag)
                                <option value="{{ $tag }}" {{ in_array($tag, $article->tags ?? []) ? 'selected' : '' }}>{{ $tag }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="tags_input" id="tags-input" value="{{ implode(',', $article->tags ?? []) }}">
                        <div class="form-text text-muted">Tapez pour rechercher ou créer un tag</div>
                    </div>
                </div>
            </div>

            {{-- Options WordPress --}}
            <div class="card mb-3">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="fw-semibold mb-0">Options</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="format" class="form-label fw-medium d-flex align-items-center gap-2">
                            <i data-lucide="layout-template" style="width:16px;height:16px;"></i> Format
                        </label>
                        <select name="format" id="format" class="form-select @error('format') is-invalid @enderror">
                            @foreach(\Modules\Blog\Enums\ArticleFormat::cases() as $case)
                                <option value="{{ $case->value }}" {{ old('format', $article->format ?? 'standard') === $case->value ? 'selected' : '' }}>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                        @error('format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $article->is_featured) ? 'checked' : '' }}>
                            <label class="form-check-label d-flex align-items-center gap-2" for="is_featured">
                                <i data-lucide="star" style="width:16px;height:16px;"></i> Article mis en avant
                            </label>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label for="content_password" class="form-label fw-medium d-flex align-items-center gap-2">
                            <i data-lucide="lock" style="width:16px;height:16px;"></i> Mot de passe
                        </label>
                        <input type="text" class="form-control @error('content_password') is-invalid @enderror" id="content_password" name="content_password" value="{{ old('content_password', $article->content_password ?? '') }}" placeholder="Laisser vide pour accès libre">
                        @error('content_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Image mise en avant --}}
            <div class="card">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="fw-semibold mb-0">Image mise en avant</h5>
                </div>
                <div class="card-body p-4">
                    @if($article->featured_image)
                        <img src="{{ Storage::url($article->featured_image) }}"
                             class="w-100 rounded-3 mb-3" style="max-height:9rem;object-fit:cover;">
                    @endif
                    <input type="file" name="featured_image"
                           class="form-control"
                           accept="image/*">
                    <div class="form-text text-muted">Laissez vide pour conserver l'image actuelle</div>
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

    // Autosave (every 60s)
    (function() {
        var url = '{{ route("admin.blog.articles.autosave", $article) }}';
        var token = document.querySelector('meta[name="csrf-token"]').content;
        var status = document.getElementById('autosave-status');
        var last = { title: '', content: '', excerpt: '' };

        function snap() {
            var t = document.querySelector('input[name="title"]');
            var c = document.querySelector('input[name="content"]');
            var e = document.querySelector('textarea[name="excerpt"]');
            return { title: t ? t.value : '', content: c ? c.value : '', excerpt: e ? e.value : '' };
        }

        last = snap();

        setInterval(function() {
            var cur = snap();
            if (JSON.stringify(cur) === JSON.stringify(last)) return;
            fetch(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify(cur)
            }).then(function(r) {
                if (r.ok) {
                    last = cur;
                    var t = new Date();
                    status.textContent = 'Sauvegardé à ' + t.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
                    status.style.opacity = '1';
                    setTimeout(function() { status.style.opacity = '0'; }, 3000);
                } else {
                    status.textContent = 'Erreur de sauvegarde';
                    status.style.color = 'red';
                    status.style.opacity = '1';
                }
            }).catch(function() {
                status.textContent = 'Erreur de sauvegarde';
                status.style.color = 'red';
                status.style.opacity = '1';
            });
        }, 60000);
    })();
});
</script>
@endpush
