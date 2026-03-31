<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Modifier la campagne', 'subtitle' => 'Newsletter'])

@section('content')
<div class="row gy-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h6 class="mb-0">Modifier la campagne</h6>
                <a href="{{ route('admin.newsletter.campaigns.index') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                    <i data-lucide="arrow-left"></i> Retour
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.newsletter.campaigns.update', $campaign) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Sujet <span class="text-danger">*</span></label>
                        <input type="text" name="subject"
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject', $campaign->subject) }}" required
                               placeholder="Objet de l'email...">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Template <span class="text-danger">*</span></label>
                        <select name="template" class="form-select @error('template') is-invalid @enderror" required>
                            @foreach($templates as $key => $label)
                                <option value="{{ $key }}" {{ old('template', $campaign->template) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('template')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <x-editor::tiptap name="content" :value="old('content', $campaign->content)" label="Contenu" />
                        @error('content')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
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
                <p class="text-muted text-sm mb-2">Creee le <strong>{{ $campaign->created_at->format('d/m/Y H:i') }}</strong></p>
                <p class="text-muted text-sm mb-2">Statut : <strong>{{ ucfirst($campaign->status) }}</strong></p>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0 text-danger">Zone dangereuse</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.newsletter.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Supprimer cette campagne ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Supprimer la campagne</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
