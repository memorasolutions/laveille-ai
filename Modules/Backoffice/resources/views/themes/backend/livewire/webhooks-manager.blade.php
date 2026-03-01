<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if($successMessage)
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" wire:key="success-msg">
            <i data-lucide="check-circle" class="icon-sm"></i>
            {{ $successMessage }}
        </div>
    @endif

    <div class="row g-4">
        {{-- Formulaire d'ajout --}}
        <div class="col-12 col-md-5">
            <div class="border rounded-3">
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                    <i data-lucide="link" class="icon-sm text-muted"></i>
                    <h6 class="fw-medium mb-0 text-body">Ajouter un endpoint</h6>
                </div>
                <div class="p-3">
                    <form wire:submit="store">
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                Nom <span class="text-danger">*</span>
                            </label>
                            <input type="text" wire:model="name"
                                   class="form-control form-control-sm @error('name') is-invalid @enderror"
                                   placeholder="Ex: Notifications Slack">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                URL <span class="text-danger">*</span>
                            </label>
                            <input type="url" wire:model="url"
                                   class="form-control form-control-sm @error('url') is-invalid @enderror"
                                   placeholder="https://example.com/webhook">
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                Secret <span class="text-muted fw-normal">(optionnel)</span>
                            </label>
                            <input type="text" wire:model="secret"
                                   class="form-control form-control-sm"
                                   placeholder="Clé secrète HMAC">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                            <i data-lucide="plus" class="icon-sm"></i> Ajouter
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Liste des webhooks --}}
        <div class="col-12 col-md-7">
            <div class="border rounded-3">
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                    <i data-lucide="list-checks" class="icon-sm text-muted"></i>
                    <h6 class="fw-medium mb-0 text-body">
                        Endpoints configurés ({{ $webhooks->count() }})
                    </h6>
                </div>
                @if($webhooks->isEmpty())
                    <div class="px-3 py-5 text-center">
                        <i data-lucide="link" class="icon-lg text-muted mb-2 d-block mx-auto"></i>
                        <p class="text-muted mb-0 small">Aucun webhook configuré.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr class="border-bottom">
                                    <th class="py-3 px-3 fw-medium">Nom</th>
                                    <th class="py-3 px-3 fw-medium">URL</th>
                                    <th class="py-3 px-3 fw-medium">Statut</th>
                                    <th class="py-3 px-3 fw-medium">Créé le</th>
                                    <th class="py-3 px-3 fw-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($webhooks as $webhook)
                                <tr>
                                    <td class="py-3 px-3 fw-medium text-body">{{ $webhook->name }}</td>
                                    <td class="py-3 px-3">
                                        <code class="small text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">
                                            {{ \Illuminate\Support\Str::limit($webhook->url, 40) }}
                                        </code>
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($webhook->is_active)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-semibold">Actif</span>
                                        @else
                                            <span class="badge bg-light text-muted border fw-semibold">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-muted small">{{ $webhook->created_at->diffForHumans() }}</td>
                                    <td class="py-3 px-3">
                                        <div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false">
                                            <button @click="open = !open"
                                                    class="btn btn-sm btn-light d-inline-flex align-items-center justify-content-center rounded-circle"
                                                    style="width:32px;height:32px;">
                                                <i data-lucide="more-horizontal" class="icon-sm"></i>
                                            </button>
                                            <div x-show="open" x-cloak
                                                 class="position-absolute end-0 mt-1 bg-white border rounded shadow"
                                                 style="z-index:50;min-width:140px;top:100%;">
                                                <button wire:click="delete({{ $webhook->id }})"
                                                        wire:confirm="Supprimer ce webhook ?"
                                                        class="btn btn-sm btn-link text-danger d-flex align-items-center gap-2 w-100 px-3 py-2 text-decoration-none">
                                                    <i data-lucide="trash-2" class="icon-sm"></i> Supprimer
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
