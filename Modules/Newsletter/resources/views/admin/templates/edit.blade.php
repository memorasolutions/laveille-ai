<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Modifier template', 'subtitle' => 'Newsletter'])

@section('content')
<div class="row gy-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h6 class="mb-0">Modifier : {{ $template->name }}</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.newsletter.templates.preview', $template) }}" class="btn btn-sm btn-outline-info d-flex align-items-center gap-1" target="_blank">
                        <i data-lucide="eye"></i> Aperçu
                    </a>
                    <a href="{{ route('admin.newsletter.templates.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                        <i data-lucide="arrow-left"></i> Retour
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.newsletter.templates.update', $template) }}">
                    @csrf @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $template->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catégorie</label>
                            <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category', $template->category) }}" placeholder="Ex: onboarding">
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sujet <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject', $template->subject) }}" required>
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contenu HTML <span class="text-danger">*</span></label>
                        <textarea name="body_html" rows="15" class="form-control font-monospace @error('body_html') is-invalid @enderror" required>{{ old('body_html', $template->body_html) }}</textarea>
                        @error('body_html')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Actif</label>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.newsletter.templates.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Variables disponibles</h6></div>
            <div class="card-body">
                <p class="text-muted text-sm mb-2">Utilisez ces variables dans le contenu :</p>
                <ul class="list-unstyled mb-0">
                    <li class="mb-1"><code>@{{subscriber.name}}</code> - nom de l'abonné</li>
                    <li class="mb-1"><code>@{{subscriber.email}}</code> - email de l'abonné</li>
                    <li><code>@{{unsubscribe_url}}</code> - lien de désabonnement</li>
                </ul>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0">Informations</h6></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt class="text-muted text-sm">Slug</dt>
                    <dd><code>{{ $template->slug }}</code></dd>
                    <dt class="text-muted text-sm">Créé le</dt>
                    <dd>{{ $template->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="text-muted text-sm">Modifié le</dt>
                    <dd class="mb-0">{{ $template->updated_at->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
