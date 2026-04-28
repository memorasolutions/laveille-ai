<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Réponses prédéfinies'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Réponses prédéfinies') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="message-square-text" class="icon-md text-primary"></i>
        {{ __('Réponses prédéfinies') }}
    </h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> {{ __('Ajouter') }}
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Titre') }}</th>
                    <th>{{ __('Raccourci') }}</th>
                    <th>{{ __('Catégorie') }}</th>
                    <th>{{ __('Portée') }}</th>
                    <th class="text-center">{{ __('Actif') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($replies as $reply)
                <tr>
                    <td>
                        <strong>{{ $reply->title }}</strong>
                        <br><small class="text-muted">{{ Str::limit($reply->content, 80) }}</small>
                    </td>
                    <td>
                        @if($reply->shortcut)
                        <code>/{{ $reply->shortcut }}</code>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $reply->category ?? '-' }}</td>
                    <td>
                        @if($reply->user_id)
                        <span class="badge bg-light text-dark">{{ $reply->user->name ?? __('Personnel') }}</span>
                        @else
                        <span class="badge bg-primary">{{ __('Partagé') }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($reply->is_active)
                        <span class="badge bg-success">{{ __('Oui') }}</span>
                        @else
                        <span class="badge bg-secondary">{{ __('Non') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal{{ $reply->id }}">
                            <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                        </button>
                        <form action="{{ route('admin.ai.canned-replies.destroy', $reply) }}" method="POST" class="d-inline" data-confirm="{{ __('Supprimer cette réponse?') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                {{-- Edit modal --}}
                <div class="modal fade" id="editModal{{ $reply->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('admin.ai.canned-replies.update', $reply) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Modifier la réponse') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Titre') }} *</label>
                                        <input type="text" name="title" class="form-control" value="{{ $reply->title }}" required maxlength="255">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Contenu') }} *</label>
                                        <textarea name="content" class="form-control" rows="4" required>{{ $reply->content }}</textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <label class="form-label">{{ __('Raccourci') }}</label>
                                            <input type="text" name="shortcut" class="form-control" value="{{ $reply->shortcut }}" maxlength="50">
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label">{{ __('Catégorie') }}</label>
                                            <input type="text" name="category" class="form-control" value="{{ $reply->category }}" maxlength="100">
                                        </div>
                                    </div>
                                    <div class="form-check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="editActive{{ $reply->id }}" {{ $reply->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="editActive{{ $reply->id }}">{{ __('Actif') }}</label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('Aucune réponse prédéfinie.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.ai.canned-replies.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Nouvelle réponse prédéfinie') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Titre') }} *</label>
                        <input type="text" name="title" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Contenu') }} *</label>
                        <textarea name="content" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ __('Raccourci') }}</label>
                            <input type="text" name="shortcut" class="form-control" maxlength="50" placeholder="ex: salut">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ __('Catégorie') }}</label>
                            <input type="text" name="category" class="form-control" maxlength="100">
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="shared" value="1" class="form-check-input" id="createShared">
                        <label class="form-check-label" for="createShared">{{ __('Partager avec tous les agents') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Créer') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
