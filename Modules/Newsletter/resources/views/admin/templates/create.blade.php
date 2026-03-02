<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Nouveau template', 'subtitle' => 'Newsletter'])

@section('content')
<div class="row gy-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h6 class="mb-0">Nouveau template marketing</h6>
                <a href="{{ route('admin.newsletter.templates.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <i data-lucide="arrow-left"></i> Retour
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.newsletter.templates.store') }}">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Ex: Bienvenue">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" required placeholder="Ex: welcome-series">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Sujet <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" required placeholder="Objet de l'email...">
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catégorie</label>
                            <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}" placeholder="Ex: onboarding">
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contenu HTML <span class="text-danger">*</span></label>
                        <textarea name="body_html" rows="15" class="form-control font-monospace @error('body_html') is-invalid @enderror" required placeholder="&lt;p&gt;Bonjour @{{subscriber.name}},&lt;/p&gt;">{{ old('body_html') }}</textarea>
                        @error('body_html')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary">Créer le template</button>
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
    </div>
</div>
@endsection
