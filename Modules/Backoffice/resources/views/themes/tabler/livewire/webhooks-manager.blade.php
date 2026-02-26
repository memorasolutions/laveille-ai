<div>
    @if($successMessage)
    <div class="alert alert-success alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-check me-2"></i>{{ $successMessage }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" wire:click="$set('successMessage', null)"></button>
    </div>
    @endif

    {{-- Formulaire d'ajout --}}
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="ti ti-webhook me-2 text-muted"></i>
                Ajouter un webhook
            </h4>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label required">Nom</label>
                    <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror"
                        placeholder="Mon webhook">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label required">URL du webhook</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-link"></i></span>
                        <input type="url" wire:model="url" class="form-control @error('url') is-invalid @enderror"
                            placeholder="https://exemple.com/webhook">
                    </div>
                    @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Secret (optionnel)</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-key"></i></span>
                        <input type="text" wire:model="secret" class="form-control"
                            placeholder="Clé secrète pour la signature">
                    </div>
                </div>
                <div class="col-12">
                    <button wire:click="store" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Ajouter
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau des webhooks --}}
    <div class="table-responsive">
        <table class="table table-vcenter table-hover">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>URL</th>
                    <th>Secret</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($webhooks as $webhook)
                <tr>
                    <td class="fw-medium">{{ $webhook->name }}</td>
                    <td style="max-width: 280px;">
                        <div class="text-truncate small" title="{{ $webhook->url }}">
                            <i class="ti ti-link me-1 text-muted"></i>{{ $webhook->url }}
                        </div>
                    </td>
                    <td>
                        @if($webhook->secret)
                        <span class="badge bg-success-lt"><i class="ti ti-key me-1"></i>Signé</span>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-muted small">
                        {{ $webhook->created_at->diffForHumans() }}
                    </td>
                    <td>
                        <button wire:click="delete({{ $webhook->id }})"
                            wire:confirm="Supprimer ce webhook ?"
                            class="btn btn-sm btn-outline-danger" title="Supprimer">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="ti ti-webhook fs-2 d-block mb-2"></i>
                        Aucun webhook configuré
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-muted small mt-2">{{ $webhooks->count() }} webhook(s) au total</div>
</div>
