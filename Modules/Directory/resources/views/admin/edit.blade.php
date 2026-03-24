@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Modifier l\'outil')])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.directory.index') }}">Répertoire</a></li>
            <li class="breadcrumb-item active">Modifier</li>
        </ol>
    </nav>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Modifier l'outil</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.directory.update', $tool) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $tool->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="short_description" class="form-label">Description courte</label>
                    <input type="text" class="form-control @error('short_description') is-invalid @enderror" id="short_description" name="short_description" value="{{ old('short_description', $tool->short_description) }}" maxlength="255">
                    @error('short_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5">{{ old('description', $tool->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="url" class="form-label">URL</label>
                    <input type="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url" value="{{ old('url', $tool->url) }}">
                    @error('url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="pricing" class="form-label">Modèle économique <span class="text-danger">*</span></label>
                    <select class="form-select @error('pricing') is-invalid @enderror" id="pricing" name="pricing" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="free" @selected(old('pricing', $tool->pricing) == 'free')>Gratuit</option>
                        <option value="freemium" @selected(old('pricing', $tool->pricing) == 'freemium')>Freemium</option>
                        <option value="paid" @selected(old('pricing', $tool->pricing) == 'paid')>Payant</option>
                        <option value="open_source" @selected(old('pricing', $tool->pricing) == 'open_source')>Open source</option>
                        <option value="enterprise" @selected(old('pricing', $tool->pricing) == 'enterprise')>Entreprise</option>
                    </select>
                    @error('pricing')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="categories" class="form-label">Catégories</label>
                    <select class="form-select @error('categories') is-invalid @enderror" id="categories" name="categories[]" multiple>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', $tool->categories->pluck('id')->toArray())) ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('categories')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="logo" class="form-label">Logo</label>
                    @if($tool->logo)
                        <div class="mb-2">
                            <img src="{{ asset($tool->logo) }}" alt="Logo actuel" style="max-height: 80px;" class="rounded">
                        </div>
                    @endif
                    <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" accept="image/*">
                    @error('logo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" x-data="{ screenshotUrl: '{{ old('screenshot', $tool->screenshot ?? '') }}', capturing: false }">
                    <label for="screenshot" class="form-label">Screenshot (URL)</label>
                    <div class="input-group">
                        <input type="url" class="form-control @error('screenshot') is-invalid @enderror" id="screenshot" name="screenshot" x-model="screenshotUrl" value="{{ old('screenshot', $tool->screenshot) }}" placeholder="https://...">
                        <button type="button" class="btn btn-outline-secondary" @click="
                            const urlField = document.getElementById('url');
                            if (!urlField || !urlField.value) { alert('Entrez d abord l URL du site.'); return; }
                            capturing = true;
                            fetch('/api/scrape-meta', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ url: urlField.value }) })
                            .then(r => r.json()).then(d => { screenshotUrl = d.og_image || ''; }).catch(() => {}).finally(() => capturing = false);
                        " :disabled="capturing">
                            <span x-show="!capturing">📸 Capturer</span>
                            <span x-show="capturing">⏳</span>
                        </button>
                    </div>
                    <template x-if="screenshotUrl"><img :src="screenshotUrl" alt="Preview" style="max-height: 120px; margin-top: 8px; border-radius: 6px; border: 1px solid #dee2e6;"></template>
                    @error('screenshot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $tool->is_featured) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_featured">Mettre en avant</label>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Ordre d'affichage</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $tool->sort_order) }}">
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i> Mettre à jour
                    </button>
                    <a href="{{ route('admin.directory.index') }}" class="btn btn-outline-secondary">
                        <i data-lucide="arrow-left"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
