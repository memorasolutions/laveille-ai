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

                {{-- Section Cycle de vie --}}
                <div class="mb-4 p-3 border rounded bg-light">
                    <h5 class="mb-3">Cycle de vie (statut outil)</h5>

                    <div class="mb-3">
                        <label for="lifecycle_status" class="form-label">Statut du cycle de vie</label>
                        <select name="lifecycle_status" id="lifecycle_status" class="form-select @error('lifecycle_status') is-invalid @enderror">
                            <option value="active" {{ old('lifecycle_status', $tool->lifecycle_status ?? 'active') === 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="beta" {{ old('lifecycle_status', $tool->lifecycle_status ?? 'active') === 'beta' ? 'selected' : '' }}>Bêta</option>
                            <option value="paused" {{ old('lifecycle_status', $tool->lifecycle_status ?? 'active') === 'paused' ? 'selected' : '' }}>En pause</option>
                            <option value="renamed" {{ old('lifecycle_status', $tool->lifecycle_status ?? 'active') === 'renamed' ? 'selected' : '' }}>Renommé</option>
                            <option value="pivoted" {{ old('lifecycle_status', $tool->lifecycle_status ?? 'active') === 'pivoted' ? 'selected' : '' }}>Pivoté</option>
                            <option value="acquired" {{ old('lifecycle_status', $tool->lifecycle_status ?? 'active') === 'acquired' ? 'selected' : '' }}>Acquis</option>
                            <option value="closed" {{ old('lifecycle_status', $tool->lifecycle_status ?? 'active') === 'closed' ? 'selected' : '' }}>Fermé</option>
                            <option value="scam" {{ old('lifecycle_status', $tool->lifecycle_status ?? 'active') === 'scam' ? 'selected' : '' }}>Arnaque</option>
                        </select>
                        <small class="form-text text-muted">Statut actuel de l'outil dans son cycle de vie.</small>
                        @error('lifecycle_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="lifecycle_date" class="form-label">Date du changement de statut</label>
                        <input type="date" name="lifecycle_date" id="lifecycle_date" class="form-control @error('lifecycle_date') is-invalid @enderror" value="{{ old('lifecycle_date', $tool->lifecycle_date?->format('Y-m-d')) }}">
                        <small class="form-text text-muted">Date à laquelle le statut a changé (fermeture, acquisition, etc.).</small>
                        @error('lifecycle_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="lifecycle_replacement_url" class="form-label">URL de remplacement</label>
                        <input type="url" name="lifecycle_replacement_url" id="lifecycle_replacement_url" class="form-control @error('lifecycle_replacement_url') is-invalid @enderror" placeholder="https://..." value="{{ old('lifecycle_replacement_url', $tool->lifecycle_replacement_url) }}">
                        <small class="form-text text-muted">Lien vers l'outil ou le service qui remplace celui-ci.</small>
                        @error('lifecycle_replacement_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="lifecycle_replacement_tool_id" class="form-label">ID de l'outil de remplacement</label>
                        <input type="number" name="lifecycle_replacement_tool_id" id="lifecycle_replacement_tool_id" class="form-control @error('lifecycle_replacement_tool_id') is-invalid @enderror" placeholder="ID numérique (optionnel)" value="{{ old('lifecycle_replacement_tool_id', $tool->lifecycle_replacement_tool_id) }}">
                        <small class="form-text text-muted">ID d'un outil existant dans le répertoire qui remplace celui-ci.</small>
                        @error('lifecycle_replacement_tool_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="lifecycle_notes" class="form-label">Notes sur le cycle de vie</label>
                        <textarea name="lifecycle_notes" id="lifecycle_notes" rows="3" maxlength="2000" class="form-control @error('lifecycle_notes') is-invalid @enderror" placeholder="Contexte, raisons du changement de statut...">{{ old('lifecycle_notes', $tool->lifecycle_notes) }}</textarea>
                        <small class="form-text text-muted">Informations complémentaires (max 2000 caractères).</small>
                        @error('lifecycle_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
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

                <x-core::screenshot-capture
                    :uploadUrl="route('admin.directory.upload-screenshot', $tool)"
                    :enabled="\Modules\Settings\Facades\Settings::get('directory.assisted_screenshot_enabled', true)"
                />

                <div class="mb-3 p-3" style="background:#f8f9fa;border-radius:8px;border:1px solid #e5e7eb;">
                    <h6 class="mb-2">{{ __('Uploader un screenshot manuel') }}</h6>
                    <p class="text-muted small mb-2">{{ __('Si Puppeteer ne capture pas bien (sites Cloudflare-protégés, SPA lents), upload un fichier propre. Sera redimensionné automatiquement en 1200×630.') }}</p>
                    <form action="{{ route('admin.directory.upload-screenshot', $tool) }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                        @csrf
                        <input type="file" name="screenshot" accept="image/jpeg,image/png,image/webp" required class="form-control form-control-sm" style="max-width: 360px;">
                        <button type="submit" class="btn btn-sm btn-outline-success">
                            <i data-lucide="upload" class="icon-sm"></i> {{ __('Uploader') }}
                        </button>
                    </form>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $tool->is_featured) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_featured">En vedette (sponsorise)</label>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="featured_until" class="form-label">Expiration mise en vedette</label>
                        <input type="datetime-local" class="form-control @error('featured_until') is-invalid @enderror" id="featured_until" name="featured_until" value="{{ old('featured_until', $tool->featured_until?->format('Y-m-d\TH:i')) }}">
                        <small class="text-muted">Laisser vide = permanent</small>
                        @error('featured_until')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="featured_order" class="form-label">Ordre vedette</label>
                        <input type="number" class="form-control @error('featured_order') is-invalid @enderror" id="featured_order" name="featured_order" value="{{ old('featured_order', $tool->featured_order ?? 0) }}" min="0">
                        <small class="text-muted">0 = premier affiche</small>
                        @error('featured_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
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
