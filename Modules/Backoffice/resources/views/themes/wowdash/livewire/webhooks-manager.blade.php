<div>
    @if($successMessage)
        <div class="alert alert-success d-flex align-items-center gap-2 mb-20" wire:key="success-msg">
            <iconify-icon icon="solar:check-circle-outline" class="icon text-lg"></iconify-icon>
            {{ $successMessage }}
        </div>
    @endif

    <div class="row gy-3">
        {{-- Formulaire d'ajout --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:link-bold" class="icon text-xl"></iconify-icon>
                        Ajouter un endpoint
                    </h6>
                </div>
                <div class="card-body">
                    <form wire:submit="store">
                        <div class="mb-20">
                            <label class="form-label">Nom <span class="text-danger-main">*</span></label>
                            <input
                                type="text"
                                wire:model="name"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Ex: Slack notifications"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-20">
                            <label class="form-label">URL <span class="text-danger-main">*</span></label>
                            <input
                                type="url"
                                wire:model="url"
                                class="form-control @error('url') is-invalid @enderror"
                                placeholder="https://example.com/webhook"
                            >
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-20">
                            <label class="form-label">Secret <span class="text-neutral-400 fw-normal">(optionnel)</span></label>
                            <input
                                type="text"
                                wire:model="secret"
                                class="form-control"
                                placeholder="Clé secrète HMAC"
                            >
                        </div>
                        <button type="submit" class="btn btn-primary-600 d-flex align-items-center gap-2">
                            <iconify-icon icon="ic:baseline-plus" class="icon text-xl"></iconify-icon>
                            Ajouter
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Liste des webhooks --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:list-bold" class="icon text-xl"></iconify-icon>
                        Endpoints configurés ({{ $webhooks->count() }})
                    </h6>
                </div>
                @if($webhooks->isEmpty())
                    <div class="card-body">
                        <div class="text-center text-neutral-600 py-20">
                            <iconify-icon icon="solar:link-outline" class="icon text-4xl mb-2 d-block"></iconify-icon>
                            Aucun webhook configuré.
                        </div>
                    </div>
                @else
                    <div class="card-body p-0">
                      <div class="table-responsive scroll-sm">
                        <table class="table bordered-table sm-table mb-0">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>URL</th>
                                    <th>Statut</th>
                                    <th>Créé le</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($webhooks as $webhook)
                                <tr>
                                    <td>{{ $webhook->name }}</td>
                                    <td><code class="text-sm">{{ \Illuminate\Support\Str::limit($webhook->url, 40) }}</code></td>
                                    <td>
                                        @if($webhook->is_active)
                                            <span class="bg-success-focus text-success-600 border border-success-main px-24 py-4 radius-4 fw-medium text-sm">Actif</span>
                                        @else
                                            <span class="bg-neutral-200 text-neutral-600 border border-neutral-400 px-24 py-4 radius-4 fw-medium text-sm">Inactif</span>
                                        @endif
                                    </td>
                                    <td>{{ $webhook->created_at->diffForHumans() }}</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                                <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end p-12">
                                                <button wire:click="delete({{ $webhook->id }})" wire:confirm="Supprimer ce webhook ?" class="dropdown-item d-flex align-items-center gap-2 px-12 py-8 rounded-4 text-sm text-danger-600">
                                                    <iconify-icon icon="fluent:delete-24-regular" class="icon text-lg"></iconify-icon> Supprimer
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                      </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
