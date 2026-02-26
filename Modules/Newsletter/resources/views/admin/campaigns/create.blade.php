@extends('backoffice::layouts.admin', ['title' => 'Nouvelle campagne', 'subtitle' => 'Newsletter'])

@section('content')
<div class="row gy-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h6 class="mb-0">Nouvelle campagne</h6>
                <a href="{{ route('admin.newsletter.campaigns.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <iconify-icon icon="solar:arrow-left-bold" class="icon text-xl"></iconify-icon> Retour
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.newsletter.campaigns.store') }}">
                    @csrf

                    <div class="mb-20">
                        <label class="form-label">Sujet <span class="text-danger-main">*</span></label>
                        <input type="text" name="subject"
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject') }}" required
                               placeholder="Objet de l'email...">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-20">
                        <label class="form-label">Contenu <span class="text-danger-main">*</span></label>
                        <textarea name="content" rows="12"
                                  class="form-control @error('content') is-invalid @enderror"
                                  required
                                  placeholder="Contenu de la campagne...">{{ old('content') }}</textarea>
                        @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text text-neutral-400 mt-8">Le contenu sera envoyé tel quel à tous les abonnés actifs.</div>
                    </div>

                    <div class="d-flex gap-3 mt-24">
                        <button type="submit" class="btn btn-primary-600">Créer la campagne</button>
                        <a href="{{ route('admin.newsletter.campaigns.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informations</h6>
            </div>
            <div class="card-body">
                <p class="text-neutral-600 text-sm mb-8">La campagne sera créée en statut <strong>brouillon</strong>.</p>
                <p class="text-neutral-600 text-sm mb-0">Vous pourrez l'envoyer depuis la liste des campagnes.</p>
            </div>
        </div>
    </div>
</div>
@endsection
