<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Nouveau lien court', 'subtitle' => 'Créer un lien raccourci'])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.short-urls.index') }}">Liens courts</a></li>
        <li class="breadcrumb-item active" aria-current="page">Créer</li>
    </ol>
</nav>
@endsection

@section('content')
<form action="{{ route('admin.short-urls.store') }}" method="POST">
    @csrf

    <div class="row g-4">
        {{-- Colonne principale --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="card-title mb-0 fw-semibold">Lien</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="original_url" class="form-label fw-semibold">
                            URL originale <span class="text-danger">*</span>
                        </label>
                        <input type="url" name="original_url" id="original_url"
                               class="form-control @error('original_url') is-invalid @enderror"
                               value="{{ old('original_url') }}" required
                               placeholder="https://exemple.com/ma-longue-page">
                        @error('original_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label fw-semibold">Slug personnalisé</label>
                        <div class="input-group">
                            <span class="input-group-text">/s/</span>
                            <input type="text" name="slug" id="slug"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug') }}"
                                   placeholder="Laisser vide pour auto-générer">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text text-muted">Lettres, chiffres, tirets et underscores uniquement.</div>
                    </div>
                    <div>
                        <label for="title" class="form-label fw-semibold">Titre</label>
                        <input type="text" name="title" id="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}"
                               placeholder="Titre descriptif (usage interne)">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- UTM Builder --}}
            <div class="card mt-4">
                <div class="card-header py-3 px-4 border-bottom">
                    <a class="text-decoration-none d-flex justify-content-between align-items-center"
                       data-bs-toggle="collapse" href="#utmBuilder" role="button"
                       aria-expanded="false" aria-controls="utmBuilder">
                        <h5 class="card-title mb-0 fw-semibold">UTM Builder</h5>
                        <i data-lucide="chevron-down" style="width:16px;height:16px;"></i>
                    </a>
                </div>
                <div class="collapse" id="utmBuilder">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="utm_source" class="form-label fw-semibold">Source</label>
                                <input type="text" name="utm_source" id="utm_source" class="form-control"
                                       value="{{ old('utm_source') }}" placeholder="google">
                            </div>
                            <div class="col-sm-6">
                                <label for="utm_medium" class="form-label fw-semibold">Medium</label>
                                <input type="text" name="utm_medium" id="utm_medium" class="form-control"
                                       value="{{ old('utm_medium') }}" placeholder="cpc">
                            </div>
                            <div class="col-sm-6">
                                <label for="utm_campaign" class="form-label fw-semibold">Campagne</label>
                                <input type="text" name="utm_campaign" id="utm_campaign" class="form-control"
                                       value="{{ old('utm_campaign') }}" placeholder="promo_ete">
                            </div>
                            <div class="col-sm-6">
                                <label for="utm_term" class="form-label fw-semibold">Terme</label>
                                <input type="text" name="utm_term" id="utm_term" class="form-control"
                                       value="{{ old('utm_term') }}" placeholder="mot_cle">
                            </div>
                            <div class="col-12">
                                <label for="utm_content" class="form-label fw-semibold">Contenu</label>
                                <input type="text" name="utm_content" id="utm_content" class="form-control"
                                       value="{{ old('utm_content') }}" placeholder="banniere_header">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar options --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="card-title mb-0 fw-semibold">Options</h5>
                </div>
                <div class="card-body p-4">
                    @if($domains->isNotEmpty())
                    <div class="mb-3">
                        <label for="domain_id" class="form-label fw-semibold">Domaine</label>
                        <select name="domain_id" id="domain_id" class="form-select">
                            <option value="">Domaine par défaut</option>
                            @foreach($domains as $domain)
                                <option value="{{ $domain->id }}" @selected(old('domain_id') == $domain->id)>
                                    {{ $domain->domain }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="redirect_type" class="form-label fw-semibold">Redirection</label>
                        <select name="redirect_type" id="redirect_type" class="form-select">
                            <option value="302" @selected(old('redirect_type', '302') === '302')>302 - Temporaire</option>
                            <option value="301" @selected(old('redirect_type') === '301')>301 - Permanente</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                   value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">Actif</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="expires_at" class="form-label fw-semibold">Date d'expiration</label>
                        <input type="datetime-local" name="expires_at" id="expires_at"
                               class="form-control @error('expires_at') is-invalid @enderror"
                               value="{{ old('expires_at') }}">
                        @error('expires_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="max_clicks" class="form-label fw-semibold">Clics maximum</label>
                        <input type="number" name="max_clicks" id="max_clicks"
                               class="form-control @error('max_clicks') is-invalid @enderror"
                               value="{{ old('max_clicks') }}" min="1" placeholder="Illimité">
                        @error('max_clicks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Protection par mot de passe</label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Laisser vide = pas de protection">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="tags" class="form-label fw-semibold">Tags</label>
                        <input type="text" name="tags" id="tags" class="form-control"
                               value="{{ old('tags') }}" placeholder="tag1, tag2, tag3">
                        <div class="form-text text-muted">Séparer par des virgules.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mt-4">
        <button type="submit" class="btn btn-primary">Créer le lien</button>
        <a href="{{ route('admin.short-urls.index') }}" class="btn btn-outline-danger">Annuler</a>
    </div>
</form>
@endsection
