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
                    <label for="affiliate_url" class="form-label">Lien d'affiliation <small class="text-muted">(optionnel — remplace le lien "Visiter le site")</small></label>
                    <input type="url" class="form-control" id="affiliate_url" name="affiliate_url" value="{{ old('affiliate_url', $tool->affiliate_url) }}" placeholder="https://partnerstack.com/...">
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
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                        <option value="published" @selected(old('status', $tool->status) == 'published')>Publie</option>
                        <option value="pending" @selected(old('status', $tool->status) == 'pending')>En attente</option>
                        <option value="draft" @selected(old('status', $tool->status) == 'draft')>Brouillon</option>
                    </select>
                    @error('status')
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
                    <x-core::file-upload name="logo" accept="image/*" :max-size="2" label="Logo" help-text="JPG, PNG ou WebP. Max 2 Mo." :current-image="$tool->logo ? asset($tool->logo) : null" />
                    @error('logo')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="screenshot" class="form-label">Screenshot</label>
                    <input type="text" class="form-control @error('screenshot') is-invalid @enderror" id="screenshot" name="screenshot" value="{{ old('screenshot', $tool->screenshot) }}" placeholder="screenshots/slug.jpg ou https://...">
                    @if($tool->screenshot)
                        <div class="mt-2">
                            @if(str_starts_with($tool->screenshot, 'http'))
                                <img src="{{ $tool->screenshot }}" alt="Screenshot" style="max-height: 120px; border-radius: 6px; border: 1px solid #dee2e6;">
                            @else
                                <img src="{{ asset($tool->screenshot) }}" alt="Screenshot" style="max-height: 120px; border-radius: 6px; border: 1px solid #dee2e6;">
                            @endif
                        </div>
                    @endif
                    @error('screenshot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <form action="{{ route('admin.directory.capture-screenshot', $tool) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-info btn-sm" onclick="return confirm('Capturer un vrai screenshot Puppeteer? Cela peut prendre 30 secondes.')">
                            <i data-lucide="camera"></i> Capturer screenshot (Puppeteer)
                        </button>
                    </form>
                    <small class="text-muted ms-2">Capture le site avec Chromium headless (1200x630, cookie dismiss automatique)</small>
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

    {{-- Screenshots communaute --}}
    @php $communityScreenshots = $tool->screenshots; @endphp
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Screenshots communaute ({{ $communityScreenshots->count() }})</h5>
        </div>
        <div class="card-body">
            @if($communityScreenshots->isEmpty())
                <p class="text-muted">Aucun screenshot soumis par la communaute.</p>
            @else
                <div class="row">
                    @foreach($communityScreenshots as $screenshot)
                        @php $isMain = ($tool->screenshot === $screenshot->image_path); @endphp
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 {{ $isMain ? 'border-success' : '' }}" style="{{ $isMain ? 'border-width: 3px;' : '' }}">
                                <img src="{{ asset($screenshot->image_path) }}" alt="{{ $screenshot->caption }}" style="height: 130px; object-fit: cover;">
                                <div class="card-body p-2">
                                    <small class="text-muted">{{ $screenshot->caption ?: 'Sans titre' }}</small><br>
                                    <span class="badge {{ $screenshot->is_approved ? 'bg-success' : 'bg-warning' }}">
                                        {{ $screenshot->is_approved ? 'Approuve' : 'En attente' }}
                                    </span>
                                    @if($isMain)
                                        <span class="badge bg-primary">Principal</span>
                                    @endif
                                </div>
                                <div class="card-footer p-2">
                                    <form action="{{ route('admin.directory.set-main-screenshot', ['tool' => $tool->id, 'screenshotId' => $screenshot->id]) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $isMain ? 'btn-outline-secondary' : 'btn-primary' }}" {{ $isMain ? 'disabled' : '' }}>
                                            {{ $isMain ? 'Screenshot actuel' : 'Definir comme principal' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
