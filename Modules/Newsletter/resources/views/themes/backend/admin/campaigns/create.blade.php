@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Nouvelle campagne', 'subtitle' => 'Newsletter'])

@section('content')

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">Nouvelle campagne</h5>
                <a href="{{ route('admin.newsletter.campaigns.index') }}"
                   class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2">
                    <i data-lucide="arrow-left" class="icon-sm"></i> Retour
                </a>
            </div>
            <div class="p-4">
                <form method="POST" action="{{ route('admin.newsletter.campaigns.store') }}">
                    @csrf

                    <div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Sujet <span class="text-danger ms-1">*</span>
                            </label>
                            <input type="text" name="subject"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   value="{{ old('subject') }}" required
                                   placeholder="Objet de l'email...">
                            @error('subject')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Contenu <span class="text-danger ms-1">*</span>
                            </label>
                            <textarea name="content" rows="12"
                                      class="form-control @error('content') is-invalid @enderror"
                                      style="resize:none;"
                                      required
                                      placeholder="Contenu de la campagne...">{{ old('content') }}</textarea>
                            @error('content')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <div class="form-text text-muted">Le contenu sera envoyé tel quel à tous les abonnés actifs.</div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary">Créer la campagne</button>
                            <a href="{{ route('admin.newsletter.campaigns.index') }}" class="btn btn-outline-secondary text-center">Annuler</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header py-3 px-4 border-bottom">
                <h5 class="fw-semibold mb-0">Informations</h5>
            </div>
            <div class="p-4">
                <p class="small text-muted mb-3">La campagne sera créée en statut <strong class="text-body">brouillon</strong>.</p>
                <p class="small text-muted mb-0">Vous pourrez l'envoyer depuis la liste des campagnes.</p>
            </div>
        </div>
    </div>
</div>

@endsection
